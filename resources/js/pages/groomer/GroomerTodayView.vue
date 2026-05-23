<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const appointments = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const activeTab = ref(0)
const todayDate = new Date()
const localDateStr = new Date(todayDate.getTime() - (todayDate.getTimezoneOffset() * 60000)).toISOString().split('T')[0]
const selectedDate = ref(localDateStr)
const currentMonth = ref(todayDate.getMonth())
const currentYear = ref(todayDate.getFullYear())
const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const currentMonthName = computed(() => monthNames[currentMonth.value])

function prevMonth() {
  if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value--; } else { currentMonth.value--; }
}
function nextMonth() {
  if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++; } else { currentMonth.value++; }
}
function selectDay(dateStr) {
  selectedDate.value = dateStr
  activeTab.value = 0
}

const fetchAppointments = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await apiFetch('employee/appointments', { method: 'GET' }, auth.token)
    if (res.ok) {
      appointments.value = await res.json()
    } else {
      throw new Error('No se pudieron cargar las citas.')
    }
  } catch (e) {
    console.error(e)
    errorMessage.value = e.message
  } finally {
    isLoading.value = false
  }
}

const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month, 1).getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  
  const days = []
  const startOffset = firstDay === 0 ? 6 : firstDay - 1 
  
  for (let i = 0; i < startOffset; i++) { days.push({ empty: true }) }
  
  for (let i = 1; i <= daysInMonth; i++) {
    const dStr = `${year}-${String(month+1).padStart(2, '0')}-${String(i).padStart(2, '0')}`
    const dayAppointments = appointments.value.filter(a => String(a.appointment_date).split('T')[0] === dStr)
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

const selectedDateAppointments = computed(() => {
    return appointments.value.filter(a => String(a.appointment_date).split('T')[0] === selectedDate.value)
})

const formatDate = dateStr => {
  const date = new Date(dateStr + 'T00:00:00')
  return date.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })
}

const formatTime = fullTime => {
  if (!fullTime) return '00:00'
  const parts = String(fullTime).split(' ')
  const timePart = parts.length > 1 ? parts[1] : parts[0]
  return timePart.substring(0, 5)
}

onMounted(fetchAppointments)
</script>

<template>
  <VContainer class="py-8">
    <VRow class="mb-6">
      <VCol cols="12">
        <h1 class="text-h5 font-weight-bold text-primary mb-1">
          Mi Agenda Semanal
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Visualiza y gestiona tus citas para los próximos 7 días.
        </p>
      </VCol>
    </VRow>

    <div
      v-if="isLoading"
      class="d-flex justify-center py-12"
    >
      <VProgressCircular
        indeterminate
        color="primary"
        size="64"
      />
    </div>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-6"
    >
      {{ errorMessage }}
    </VAlert>

    <div v-if="!isLoading">
      <VCard rounded="lg" class="pa-4 bg-transparent" elevation="0">
        <VTabs v-model="activeTab" color="primary" class="mb-4">
          <VTab :value="0">Vista Diaria</VTab>
          <VTab :value="1">Calendario Mensual</VTab>
        </VTabs>

        <VWindow v-model="activeTab">
          <!-- PESTAÑA 0: VISTA DIARIA -->
          <VWindowItem :value="0">
            <div class="d-flex align-center justify-space-between mb-4 mt-2">
              <span class="text-subtitle-1 font-weight-bold text-uppercase text-primary letter-spacing-1">
                {{ formatDate(selectedDate) }}
              </span>
              <VBtn v-if="selectedDate !== localDateStr" size="small" variant="text" color="primary" @click="selectDay(localDateStr)">Ir a Hoy</VBtn>
            </div>
            
            <VRow v-if="selectedDateAppointments.length">
              <VCol
                v-for="a in selectedDateAppointments"
                :key="a.id"
                cols="12"
                md="6"
                lg="4"
              >
                <VCard
                  rounded="xl"
                  elevation="2"
                  class="pa-5 h-100 d-flex flex-column transition-swing hover-elevation-4"
                  :class="a.status === 'finished' ? 'bg-grey-lighten-4' : ''"
                >
                  <div class="d-flex justify-space-between align-center mb-3">
                    <VChip
                      size="small"
                      :color="a.status === 'pending' ? 'primary' : 'success'"
                      variant="elevated"
                      class="font-weight-bold"
                    >
                      <VIcon start icon="ri-time-line" size="14" />
                      {{ formatTime(a.start_time) }}
                    </VChip>
                    
                    <VBtn
                      v-if="a.status !== 'finished'"
                      size="small"
                      variant="flat"
                      color="primary"
                      rounded="pill"
                      :to="`/groomer/service/${a.id}`"
                    >
                      Atender
                    </VBtn>
                    <VIcon
                      v-else
                      icon="ri-checkbox-circle-line"
                      color="success"
                    />
                  </div>

                  <div class="text-h6 font-weight-bold mb-1">
                    {{ a.pet_name }}
                    <span
                      v-if="a.pet_weight"
                      class="text-body-2 font-weight-medium text-medium-emphasis ms-1"
                    >
                      ({{ a.pet_weight }} kg)
                    </span>
                  </div>
                  
                  <div class="text-body-2 font-weight-medium text-primary mb-2">
                    {{ a.service_name }}
                  </div>
                  
                  <div class="d-flex align-center text-caption text-medium-emphasis mb-1">
                    <VIcon start icon="ri-baidu-line" size="14" class="me-1" />
                    Raza: {{ a.pet_breed || '—' }}
                  </div>
                  
                  <div class="d-flex align-center text-caption text-medium-emphasis mb-3">
                    <VIcon start icon="ri-user-smile-line" size="14" class="me-1" />
                    Dueño: {{ a.customer_name || '—' }}
                  </div>

                  <VAlert
                    v-if="a.notes"
                    color="warning"
                    variant="tonal"
                    density="compact"
                    rounded="lg"
                    class="text-caption pa-2 mb-4"
                  >
                    <template #prepend>
                      <VIcon icon="ri-sticky-note-line" size="16" />
                    </template>
                    {{ a.notes }}
                  </VAlert>

                  <VSpacer />
                  
                  <VBtn
                    variant="outlined"
                    block
                    rounded="pill"
                    size="small"
                    color="secondary"
                    :to="`/groomer/appointments/${a.id}/pet`"
                  >
                    Ver Ficha Médica
                  </VBtn>
                </VCard>
              </VCol>
            </VRow>
            
            <div v-else class="py-8">
              <VCard variant="outlined" class="pa-12 d-flex flex-column align-center justify-center border-dashed rounded-xl">
                <VIcon icon="ri-calendar-todo-line" size="64" color="primary" class="mb-4 opacity-20" />
                <div class="text-h6 font-weight-bold text-medium-emphasis">Sin citas programadas</div>
                <div class="text-body-2 text-medium-emphasis">No tienes tareas pendientes para este día.</div>
              </VCard>
            </div>
          </VWindowItem>

          <!-- PESTAÑA 1: CALENDARIO MENSUAL -->
          <VWindowItem :value="1">
            <div class="d-flex justify-space-between align-center mb-4 mt-2">
              <VBtn icon="ri-arrow-left-s-line" variant="text" @click="prevMonth" />
              <span class="text-subtitle-1 font-weight-bold text-uppercase">{{ currentMonthName }} {{ currentYear }}</span>
              <VBtn icon="ri-arrow-right-s-line" variant="text" @click="nextMonth" />
            </div>
            
            <div class="calendar-grid">
              <div class="calendar-header text-caption text-medium-emphasis text-center py-2" v-for="d in ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']" :key="d">
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
                <span v-if="!day.empty" class="day-number">{{ day.date }}</span>
                <div v-if="!day.empty && day.appointments?.length" class="day-indicators mt-1 d-flex flex-column ga-1 align-center">
                  <VChip v-if="day.hasPending" size="x-small" color="error" variant="flat">Pendientes</VChip>
                  <VChip v-else size="x-small" color="primary" variant="flat">{{ day.appointments.length }} citas</VChip>
                </div>
              </div>
            </div>
          </VWindowItem>
        </VWindow>
      </VCard>
    </div>
  </VContainer>
</template>

<style scoped>
.letter-spacing-1 {
  letter-spacing: 1px;
}
.hover-elevation-4:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
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
