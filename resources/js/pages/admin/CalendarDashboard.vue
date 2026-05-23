<script setup>
import AppointmentForm from '@/components/admin/AppointmentForm.vue'
import AppointmentActionDialog from '@/components/admin/AppointmentActionDialog.vue'
import {
  appointmentStatusColors,
  appointmentStatusLabels,
} from '@/data/mockAppointments'
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const appointmentFormRef = ref(null)

const editDialog = ref(false)
const selectedAppointment = ref(null)

function openEditDialog(appointment) {
  selectedAppointment.value = appointment
  editDialog.value = true
}

const activeTab = ref(0)
const todayDate = new Date()

// Ajustar fecha a zona horaria local ignorando horas
const localDateStr = new Date(todayDate.getTime() - (todayDate.getTimezoneOffset() * 60000)).toISOString().split('T')[0]

const selectedDate = ref(localDateStr)
const currentMonth = ref(todayDate.getMonth())
const currentYear = ref(todayDate.getFullYear())

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const currentMonthName = computed(() => monthNames[currentMonth.value])

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

function selectDay(dateStr) {
  selectedDate.value = dateStr
  activeTab.value = 0
}

const hours = Array.from({ length: 11 }, (_, i) => 8 + i)
const appointments = ref([])

const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month, 1).getDay() // 0 is Sunday
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  
  const days = []
  const startOffset = firstDay === 0 ? 6 : firstDay - 1 // Make Monday index 0
  
  for (let i = 0; i < startOffset; i++) {
    days.push({ empty: true })
  }
  
  for (let i = 1; i <= daysInMonth; i++) {
    const dStr = `${year}-${String(month+1).padStart(2, '0')}-${String(i).padStart(2, '0')}`
    
    const dayAppointments = appointments.value.filter(a => {
      const aptDate = String(a.appointment_date).split('T')[0]
      
      return aptDate === dStr
    })

    const hasPending = dayAppointments.some(a => a.status === 'pending')
    
    days.push({
      date: i,
      dateStr: dStr,
      empty: false,
      isToday: dStr === localDateStr,
      isSelected: dStr === selectedDate.value,
      appointments: dayAppointments,
      hasPending: hasPending,
    })
  }
  
  return days
})

const loadingAppointments = ref(false)
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const formatRange = (startTime, endTime) => {
  const s = new Date(String(startTime).replace(' ', 'T'))
  const e = new Date(String(endTime).replace(' ', 'T'))
  const opt = { hour: '2-digit', minute: '2-digit' }

  if (Number.isNaN(s.getTime())) {
    return `${startTime} – ${endTime}`
  }

  return `${s.toLocaleTimeString('es-ES', opt)} – ${e.toLocaleTimeString('es-ES', opt)}`
}

const hourFromStartTime = startTime => {
  const m = String(startTime).match(/(\d{2}):(\d{2})/)
  if (!m) {
    return null
  }

  return Number(m[1])
}

const appointmentsForHour = hour => {
  return appointments.value.filter(a => {
    const aptDate = String(a.appointment_date).split('T')[0]
    
    return aptDate === selectedDate.value && hourFromStartTime(a.start_time) === hour
  })
}

const upcomingAppointments = computed(() => {
  return appointments.value.filter(a => {
    const aptDate = String(a.appointment_date).split('T')[0]
    
    return aptDate >= localDateStr
  })
})

const statusColor = status => appointmentStatusColors[status] || 'secondary'

const statusLabel = status => appointmentStatusLabels[status] || status

async function fetchAppointments() {
  loadingAppointments.value = true
  try {
    const res = await apiFetch('appointments', { method: 'GET' }, auth.token)
    if (res.ok) {
      const data = await res.json()

      appointments.value = Array.isArray(data) ? data : []
    }
  }
  catch (e) {
    console.error('Error fetching appointments:', e)
    appointments.value = []
  }

  finally {
    loadingAppointments.value = false
  }
}

function onAppointmentCreated(created) {
  if (created?.start_time) {
    appointments.value = [...appointments.value, created]
  }
  snackbarText.value = 'Cita registrada correctamente.'
  snackbarColor.value = 'success'
  snackbar.value = true
  fetchAppointments()
}

function onAppointmentError(message) {
  snackbarText.value = message || 'No se pudo registrar la cita.'
  snackbarColor.value = 'error'
  snackbar.value = true
}

onMounted(() => {
  fetchAppointments()
})

// --- MÓDULO DE BLOQUEO DE HORARIOS ---
const blocksDialog = ref(false)
const blockTab = ref(0)
const timeBlocks = ref([])
const loadingBlocks = ref(false)
const savingBlock = ref(false)
const groomers = ref([])

const newBlock = ref({
  reason: '',
  start_time: '',
  end_time: '',
  is_global: true,
  employee_id: null,
})

const blockHeaders = [
  { title: 'Motivo', key: 'reason' },
  { title: 'Tipo', key: 'is_global' },
  { title: 'Inicio', key: 'start_time' },
  { title: 'Fin', key: 'end_time' },
  { title: 'Groomer', key: 'employee.user.first_name', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function fetchBlocks() {
  loadingBlocks.value = true
  try {
    const res = await apiFetch('time-blocks', { method: 'GET' }, auth.token)
    if (res.ok) {
      timeBlocks.value = await res.json()
    }
  } catch (e) {
    console.error('Error fetching time blocks:', e)
  } finally {
    loadingBlocks.value = false
  }
}

async function fetchGroomers() {
  try {
    const res = await apiFetch('admin/employees?specialty=Groomer&is_active=true', { method: 'GET' }, auth.token)
    if (res.ok) {
      const data = await res.json()

      groomers.value = data.map(g => ({
        id: g.id,
        name: `${g.user.first_name} ${g.user.last_name}`,
      }))
    }
  } catch (e) {
    console.error('Error fetching groomers:', e)
  }
}

function openBlocksDialog() {
  blocksDialog.value = true
  blockTab.value = 0
  fetchBlocks()
  fetchGroomers()
}

async function createBlock() {
  savingBlock.value = true
  try {
    const payload = {
      ...newBlock.value,
      start_time: newBlock.value.start_time.replace('T', ' '),
      end_time: newBlock.value.end_time.replace('T', ' '),
    }

    const res = await apiFetch('time-blocks', {
      method: 'POST',
      body: JSON.stringify(payload),
    }, auth.token)

    if (res.ok) {
      snackbarText.value = 'Bloqueo registrado correctamente.'
      snackbarColor.value = 'success'
      snackbar.value = true
      newBlock.value = { reason: '', start_time: '', end_time: '', is_global: true, employee_id: null }
      blockTab.value = 0
      fetchBlocks()
    } else {
      const data = await res.json()

      alert(data.message || 'Error al registrar bloqueo')
    }
  } catch (e) {
    console.error(e)
  } finally {
    savingBlock.value = false
  }
}

async function deleteBlock(id) {
  if (!confirm('¿Seguro que deseas eliminar este bloqueo de horario?')) return
  try {
    const res = await apiFetch(`time-blocks/${id}`, { method: 'DELETE' }, auth.token)
    if (res.ok) {
      snackbarText.value = 'Bloqueo eliminado y horario reabierto.'
      snackbarColor.value = 'success'
      snackbar.value = true
      fetchBlocks()
    }
  } catch (e) {
    console.error(e)
  }
}

const formatDateTime = dt => {
  const d = new Date(String(dt).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return dt
  
  return d.toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' })
}
</script>

<template>
  <div>
    <VRow class="mb-4">
      <VCol cols="12">
        <h2 class="text-h5 mb-1">
          Agenda
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-2">
          Citas con <code>start_time</code> y <code>end_time</code> calculados por el backend. Colores por estado.
        </p>
        <div class="d-flex flex-wrap ga-2 mb-2">
          <VChip
            size="small"
            color="warning"
            variant="tonal"
          >
            pending
          </VChip>
          <VChip
            size="small"
            color="info"
            variant="tonal"
          >
            confirmed
          </VChip>
          <VChip
            size="small"
            color="error"
            variant="tonal"
          >
            cancelled
          </VChip>
          <VChip
            size="small"
            color="success"
            variant="tonal"
          >
            finished
          </VChip>
        </div>
        <div class="d-flex flex-wrap ga-2 align-center mt-2">
          <div class="d-flex flex-wrap ga-2">
            <VBtn
              size="small"
              variant="tonal"
              color="primary"
              prepend-icon="ri-shopping-bag-3-line"
              to="/admin/shop"
            >
              Tienda
            </VBtn>
            <VBtn
              size="small"
              variant="outlined"
              color="primary"
              prepend-icon="ri-bank-card-line"
              to="/admin/checkout/501"
            >
              Cobro demo (501)
            </VBtn>
          </div>
          <VBtn
            size="small"
            variant="elevated"
            color="error"
            prepend-icon="ri-calendar-close-line"
            @click="openBlocksDialog"
          >
            Bloqueos de Horario
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <VRow>
      <VCol
        cols="12"
        lg="5"
        xl="4"
      >
        <AppointmentForm
          ref="appointmentFormRef"
          @created="onAppointmentCreated"
          @error="onAppointmentError"
        />
      </VCol>

      <VCol
        cols="12"
        lg="7"
        xl="8"
      >
        <VCard
          rounded="lg"
          class="pa-4"
          :loading="loadingAppointments"
        >
          <VTabs
            v-model="activeTab"
            color="primary"
            class="mb-4"
          >
            <VTab :value="0">
              Vista Diaria
            </VTab>
            <VTab :value="1">
              Calendario Mensual
            </VTab>
          </VTabs>

          <VWindow v-model="activeTab">
            <!-- PESTAÑA 0: VISTA DIARIA (SLOTS) -->
            <VWindowItem :value="0">
              <div class="text-subtitle-2 mb-4 text-primary d-flex align-center justify-space-between">
                <span>Slots por hora - {{ selectedDate === localDateStr ? 'Hoy' : selectedDate }}</span>
                <VBtn
                  v-if="selectedDate !== localDateStr"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="selectDay(localDateStr)"
                >
                  Ir a Hoy
                </VBtn>
              </div>
              <VRow
                v-for="h in hours"
                :key="h"
                dense
                class="slot-row align-center mb-1"
              >
                <VCol
                  cols="2"
                  sm="1"
                  class="text-caption text-medium-emphasis text-end pe-2"
                >
                  {{ String(h).padStart(2, '0') }}:00
                </VCol>
                <VCol
                  cols="10"
                  sm="11"
                >
                  <VSheet
                    v-if="!appointmentsForHour(h).length"
                    rounded
                    class="slot-cell pa-3"
                    variant="outlined"
                  >
                    <span class="text-body-2 text-medium-emphasis">Disponible</span>
                  </VSheet>
                  <div
                    v-else
                    class="d-flex flex-column ga-2"
                  >
                    <VSheet
                      v-for="apt in appointmentsForHour(h)"
                      :key="apt.id"
                      rounded
                      class="slot-cell pa-3 cursor-pointer"
                      :color="statusColor(apt.status)"
                      variant="tonal"
                      @click="openEditDialog(apt)"
                    >
                      <div class="d-flex flex-wrap align-center justify-space-between ga-2">
                        <span class="text-body-2 font-weight-medium">
                          {{ apt.pet_name || 'Mascota' }} · {{ apt.service_name || 'Servicio' }}
                        </span>
                        <VChip
                          size="x-small"
                          :color="statusColor(apt.status)"
                          variant="flat"
                        >
                          {{ statusLabel(apt.status) }}
                        </VChip>
                      </div>
                      <div class="text-caption mt-1">
                        {{ formatRange(apt.start_time, apt.end_time) }}
                      </div>
                    </VSheet>
                  </div>
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- PESTAÑA 1: CALENDARIO MENSUAL -->
            <VWindowItem :value="1">
              <div class="d-flex justify-space-between align-center mb-4">
                <VBtn
                  icon="ri-arrow-left-s-line"
                  variant="text"
                  @click="prevMonth"
                />
                <span class="text-subtitle-1 font-weight-bold text-uppercase">{{ currentMonthName }} {{ currentYear }}</span>
                <VBtn
                  icon="ri-arrow-right-s-line"
                  variant="text"
                  @click="nextMonth"
                />
              </div>
              
              <div class="calendar-grid">
                <div
                  v-for="d in ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']"
                  :key="d"
                  class="calendar-header text-caption text-medium-emphasis text-center py-2"
                >
                  {{ d }}
                </div>
                
                <div 
                  v-for="(day, index) in calendarDays" 
                  :key="index"
                  class="calendar-day"
                  :class="{ 
                    'empty-day': day.empty, 
                    'is-today': day.isToday, 
                    'is-selected': day.isSelected 
                  }"
                  @click="!day.empty && selectDay(day.dateStr)"
                >
                  <span
                    v-if="!day.empty"
                    class="day-number"
                  >{{ day.date }}</span>
                  <div
                    v-if="!day.empty && day.appointments?.length"
                    class="day-indicators mt-1 d-flex flex-column ga-1 align-center"
                  >
                    <VChip
                      v-if="day.hasPending"
                      size="x-small"
                      color="error"
                      variant="flat"
                    >
                      Pendientes
                    </VChip>
                    <VChip
                      v-else
                      size="x-small"
                      color="primary"
                      variant="flat"
                    >
                      {{ day.appointments.length }} citas
                    </VChip>
                  </div>
                </div>
              </div>
            </VWindowItem>
          </VWindow>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        lg="12"
      >
        <VCard
          rounded="lg"
          class="pa-4"
        >
          <div class="text-subtitle-1 font-weight-medium mb-4 d-flex align-center ga-2">
            <VIcon
              icon="ri-calendar-todo-line"
              color="primary"
            />
            Próximas citas
          </div>
          <VList
            v-if="appointments.length"
            lines="three"
            class="pa-0 bg-transparent"
          >
            <VListItem
              v-for="apt in appointments"
              :key="apt.id"
              class="px-0 border-b py-4 cursor-pointer"
              @click="openEditDialog(apt)"
            >
              <template #prepend>
                <VAvatar
                  :color="statusColor(apt.status)"
                  variant="tonal"
                  size="40"
                >
                  <VIcon icon="ri-calendar-event-line" />
                </VAvatar>
              </template>
              <VListItemTitle class="font-weight-medium">
                {{ apt.pet_name }}
                <VChip
                  class="ms-2"
                  size="x-small"
                  :color="statusColor(apt.status)"
                >
                  {{ statusLabel(apt.status) }}
                </VChip>
              </VListItemTitle>
              <VListItemSubtitle class="text-wrap">
                {{ apt.service_name }}
              </VListItemSubtitle>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ formatRange(apt.start_time, apt.end_time) }}
              </div>
            </VListItem>
          </VList>
          <VAlert
            v-else
            type="info"
            variant="tonal"
          >
            No hay citas programadas. Crea una desde el formulario.
          </VAlert>
        </VCard>
      </VCol>
    </VRow>

    <!-- Diálogo de Bloqueos de Horario -->
    <VDialog
      v-model="blocksDialog"
      max-width="600"
    >
      <VCard rounded="lg">
        <VCardTitle class="pa-4 d-flex justify-space-between align-center bg-error text-white">
          <span class="text-h6">Gestión de Bloqueos de Horario</span>
          <VBtn
            icon
            variant="text"
            color="white"
            @click="blocksDialog = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VCardText class="pa-4">
          <VTabs
            v-model="blockTab"
            color="error"
            class="mb-4"
          >
            <VTab :value="0">
              Ver Bloqueos Activos
            </VTab>
            <VTab :value="1">
              Registrar Bloqueo
            </VTab>
          </VTabs>

          <VWindow v-model="blockTab">
            <!-- Pestaña 1: Listado -->
            <VWindowItem :value="0">
              <VDataTable
                :headers="blockHeaders"
                :items="timeBlocks"
                :loading="loadingBlocks"
                no-data-text="No hay bloqueos registrados actualmente"
                class="elevation-0"
              >
                <template #item.is_global="{ item }">
                  <VChip
                    size="small"
                    :color="item.is_global ? 'error' : 'warning'"
                    variant="tonal"
                  >
                    {{ item.is_global ? 'Global' : 'Groomer' }}
                  </VChip>
                </template>
                <template #item.start_time="{ item }">
                  {{ formatDateTime(item.start_time) }}
                </template>
                <template #item.end_time="{ item }">
                  {{ formatDateTime(item.end_time) }}
                </template>
                <template #item.actions="{ item }">
                  <VBtn
                    icon="ri-delete-bin-line"
                    variant="text"
                    color="error"
                    density="comfortable"
                    @click="deleteBlock(item.id)"
                  />
                </template>
              </VDataTable>
            </VWindowItem>

            <!-- Pestaña 2: Crear -->
            <VWindowItem :value="1">
              <VForm @submit.prevent="createBlock">
                <VTextField
                  v-model="newBlock.reason"
                  label="Motivo / Razón *"
                  placeholder="ej. Mantenimiento de instalaciones, Feriado nacional, Ausencia médica"
                  variant="outlined"
                  required
                  class="mb-4"
                />

                <VRow
                  dense
                  class="mb-4"
                >
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="newBlock.start_time"
                      label="Fecha/Hora de Inicio *"
                      type="datetime-local"
                      variant="outlined"
                      required
                    />
                  </VCol>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="newBlock.end_time"
                      label="Fecha/Hora de Fin *"
                      type="datetime-local"
                      variant="outlined"
                      required
                    />
                  </VCol>
                </VRow>

                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis mb-2">
                    Alcance del Bloqueo *
                  </div>
                  <VRadioGroup
                    v-model="newBlock.is_global"
                    inline
                    hide-details
                  >
                    <VRadio
                      label="Todo el Spa (Global)"
                      :value="true"
                    />
                    <VRadio
                      label="Peluquero Específico (Groomer)"
                      :value="false"
                    />
                  </VRadioGroup>
                </div>

                <VSelect
                  v-if="!newBlock.is_global"
                  v-model="newBlock.employee_id"
                  :items="groomers"
                  item-title="name"
                  item-value="id"
                  label="Seleccionar Groomer *"
                  variant="outlined"
                  required
                  class="mb-6"
                />

                <div class="d-flex justify-end ga-2">
                  <VBtn
                    variant="text"
                    color="secondary"
                    @click="blocksDialog = false"
                  >
                    Cancelar
                  </VBtn>
                  <VBtn
                    type="submit"
                    color="error"
                    :loading="savingBlock"
                  >
                    Registrar Bloqueo
                  </VBtn>
                </div>
              </VForm>
            </VWindowItem>
          </VWindow>
        </VCardText>
      </VCard>
    </VDialog>

    <VSnackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="5000"
      location="top end"
    >
      {{ snackbarText }}
    </VSnackbar>

    <AppointmentActionDialog
      v-model="editDialog"
      :appointment="selectedAppointment"
      @updated="fetchAppointments"
    />
  </div>
</template>

<style scoped>
.slot-cell {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
}

.calendar-day {
  aspect-ratio: 1;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 8px;
  padding: 8px;
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease;
  cursor: pointer;
}

.calendar-day:hover:not(.empty-day) {
  background: rgba(var(--v-theme-on-surface), 0.04);
}

.calendar-day.empty-day {
  border: none;
  background: transparent;
  cursor: default;
}

.calendar-day.is-today {
  border: 2px solid rgb(var(--v-theme-primary));
}

.calendar-day.is-selected {
  background: rgba(var(--v-theme-primary), 0.1);
}

.day-number {
  font-size: 0.875rem;
  font-weight: 500;
}
</style>
