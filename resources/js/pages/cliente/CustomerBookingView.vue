<script setup>
import ClienteAreaNav from '@/components/cliente/ClienteAreaNav.vue'
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const services = ref([])
const appointments = ref([])
const loading = ref(false)
const submitting = ref(false)
const error = ref('')

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const form = ref({
  pet_id: null,
  service_id: null,
  start_time: '',
  notes: '',
})

const estimation = ref(null)
const loadingEstimation = ref(false)

const pets = computed(() => auth.user?.customer?.pets ?? [])

const minDateTime = computed(() => {
  const now = new Date()
  const yyyy = now.getFullYear()
  const mm = String(now.getMonth() + 1).padStart(2, '0')
  const dd = String(now.getDate()).padStart(2, '0')
  const hh = String(now.getHours()).padStart(2, '0')
  const min = String(now.getMinutes()).padStart(2, '0')
  
  return `${yyyy}-${mm}-${dd}T${hh}:${min}`
})

async function fetchEstimation() {
  if (!form.value.pet_id || !form.value.service_id) {
    estimation.value = null
    return
  }
  loadingEstimation.value = true
  try {
    const res = await apiFetch('appointments/estimate', {
      method: 'POST',
      body: JSON.stringify({
        service_id: form.value.service_id,
        pet_id: form.value.pet_id,
      }),
    }, auth.token)

    if (res.ok) {
      estimation.value = await res.json()
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingEstimation.value = false
  }
}

watch([() => form.value.pet_id, () => form.value.service_id], () => {
  fetchEstimation()
})

function showNotification(msg, color = 'success') {
  snackbarText.value = msg
  snackbarColor.value = color
  snackbar.value = true
}

async function refreshProfile() {
  try {
    const res = await apiFetch('auth/me', { method: 'GET' }, auth.token)
    if (res.ok) {
      const userData = await res.json()

      auth.user = userData
    }
  } catch (e) {
    console.error(e)
  }
}

async function fetchDependencies() {
  loading.value = true
  try {
    const [resServ, resApp] = await Promise.all([
      apiFetch('services', { method: 'GET' }, auth.token),
      apiFetch('customer/appointments', { method: 'GET' }, auth.token),
    ])

    if (resServ.ok) {
      services.value = await resServ.json()
    }
    if (resApp.ok) {
      appointments.value = await resApp.json()
    }
  } catch (e) {
    console.error(e)
    showNotification('Error al cargar datos del servidor.', 'error')
  } finally {
    loading.value = false
  }
}

async function handleBooking() {
  if (!form.value.pet_id || !form.value.service_id || !form.value.start_time) {
    showNotification('Por favor, completa todos los campos requeridos.', 'warning')
    
    return
  }

  submitting.value = true
  error.value = ''

  try {
    const res = await apiFetch('customer/appointments/request', {
      method: 'POST',
      body: JSON.stringify({
        pet_id: form.value.pet_id,
        service_id: form.value.service_id,
        start_time: form.value.start_time,
        notes: form.value.notes,
      }),
    }, auth.token)

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      const msg = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Error al enviar la solicitud.'

      error.value = msg
      showNotification(msg, 'error')
      
      return
    }

    showNotification('¡Su solicitud ha sido enviada con éxito y está en espera de aprobación por parte del Spa!', 'success')
    
    // Reset Form
    form.value = {
      pet_id: null,
      service_id: null,
      start_time: '',
      notes: '',
    }

    // Refresh Appointments List
    fetchDependencies()
  } catch (e) {
    error.value = 'Error de conexión con el servidor.'
    showNotification(error.value, 'error')
  } finally {
    submitting.value = false
  }
}

function getStatusColor(status) {
  switch (status) {
  case 'pending': return 'warning'
  case 'confirmed': return 'info'
  case 'paid': return 'success'
  case 'cancelled': return 'error'
  case 'finished': return 'secondary'
  default: return 'default'
  }
}

function getStatusLabel(status) {
  switch (status) {
  case 'pending': return 'En revisión'
  case 'confirmed': return 'Confirmada'
  case 'paid': return 'Pagada'
  case 'cancelled': return 'Cancelada'
  case 'finished': return 'Finalizada'
  default: return status
  }
}

onMounted(() => {
  refreshProfile()
  fetchDependencies()
})
</script>

<template>
  <div class="cliente-reservar-page bg-surface">
    <ClienteAreaNav current="reservar" />

    <VContainer class="py-8 py-md-12">
      <!-- Encabezado -->
      <VRow class="mb-6">
        <VCol cols="12">
          <div class="d-flex align-center ga-3 mb-2">
            <VIcon
              icon="ri-calendar-event-line"
              color="primary"
              size="36"
            />
            <h1 class="text-h4 font-weight-medium text-primary mb-0">
              Solicitar Reserva de Cita
            </h1>
          </div>
          <p class="text-body-1 text-medium-emphasis mb-0">
            Agenda el próximo día de spa para tu mascota. Nuestro equipo revisará y confirmará tu cita a la brevedad.
          </p>
        </VCol>
      </VRow>

      <!-- Si no tiene mascotas registradas -->
      <VRow v-if="!pets.length && !loading">
        <VCol cols="12">
          <VCard
            variant="tonal"
            color="warning"
            class="pa-6 text-center rounded-lg"
          >
            <VIcon
              icon="ri-error-warning-line"
              size="48"
              class="mb-3"
            />
            <h3 class="text-h6 font-weight-bold mb-2">
              No tienes mascotas registradas
            </h3>
            <p class="text-body-2 mb-4 max-w-600 mx-auto">
              Para poder solicitar una cita en el Spa, primero necesitas registrar al menos una mascota en tu perfil.
            </p>
            <VBtn
              color="warning"
              variant="flat"
              to="/cliente/perfil-mascotas"
              prepend-icon="ri-add-line"
            >
              Registrar mascota ahora
            </VBtn>
          </VCard>
        </VCol>
      </VRow>

      <VRow v-else>
        <!-- Formulario (Izquierda) -->
        <VCol
          cols="12"
          md="5"
          class="mb-6 mb-md-0"
        >
          <VCard
            elevation="3"
            rounded="lg"
            class="booking-card overflow-hidden"
          >
            <div class="booking-card-header bg-primary pa-6 text-white d-flex align-center ga-2">
              <VIcon
                icon="ri-edit-box-line"
                size="24"
              />
              <span class="text-h6 font-weight-bold">Nueva Solicitud</span>
            </div>

            <VCardText class="pa-6">
              <VAlert
                v-if="error"
                type="error"
                variant="tonal"
                closable
                class="mb-4"
                @click:close="error = ''"
              >
                {{ error }}
              </VAlert>

              <VForm @submit.prevent="handleBooking">
                <!-- Mascota -->
                <VSelect
                  v-model="form.pet_id"
                  :items="pets"
                  item-title="name"
                  item-value="id"
                  label="Selecciona tu Mascota"
                  placeholder="¿Quién nos visitará?"
                  prepend-inner-icon="ri-baidu-line"
                  required
                  variant="outlined"
                  class="mb-4"
                />

                <!-- Servicio -->
                <VSelect
                  v-model="form.service_id"
                  :items="services"
                  item-title="name"
                  item-value="id"
                  label="Servicio solicitado"
                  placeholder="Elige un servicio del Spa"
                  prepend-inner-icon="ri-scissors-cut-line"
                  required
                  variant="outlined"
                  class="mb-4"
                />

                <!-- Fecha y Hora -->
                <VTextField
                  v-model="form.start_time"
                  label="Fecha y hora sugerida"
                  type="datetime-local"
                  :min="minDateTime"
                  prepend-inner-icon="ri-time-line"
                  required
                  variant="outlined"
                  class="mb-4"
                  persistent-hint
                  hint="Horario laboral: Lunes a Viernes de 09:00 a 18:00 y Sábados de 09:00 a 12:00"
                />

                <!-- Comentarios -->
                <VTextarea
                  v-model="form.notes"
                  label="Notas o indicaciones especiales"
                  placeholder="Escribe aquí si tu mascota tiene miedo a la secadora, alergias, o alguna condición especial."
                  prepend-inner-icon="ri-sticky-note-line"
                  variant="outlined"
                  rows="3"
                  class="mb-4"
                />

                <!-- Bloque de Estimación Dinámica -->
                <VExpandTransition>
                  <VCard
                    v-if="estimation"
                    variant="flat"
                    color="grey-lighten-4"
                    rounded="lg"
                    class="pa-4 mb-4 border"
                  >
                    <div class="d-flex justify-space-between align-center mb-2">
                      <span class="text-subtitle-2 text-medium-emphasis">Desglose de Costos (Estimado)</span>
                      <VProgressCircular v-if="loadingEstimation" indeterminate size="20" width="2" color="primary" />
                      <VIcon
                        v-else
                        icon="ri-magic-line"
                        color="primary"
                        size="20"
                      />
                    </div>

                    <div class="d-flex justify-space-between text-body-2 mb-1">
                      <span>Precio Base:</span>
                      <span>${{ estimation.price.base }}</span>
                    </div>

                    <div
                      v-if="estimation.price.surcharge > 0"
                      class="d-flex justify-space-between text-body-2 text-warning font-weight-medium mb-1"
                    >
                      <span>Recargo por tamaño/raza:</span>
                      <span>+${{ estimation.price.surcharge }}</span>
                    </div>

                    <VDivider class="my-2" />

                    <div class="d-flex justify-space-between align-center">
                      <span class="text-subtitle-1 font-weight-bold">Total estimado a pagar:</span>
                      <span class="text-h6 font-weight-bold text-primary">${{ estimation.price.total }}</span>
                    </div>

                    <div class="text-caption text-medium-emphasis mt-2 pt-2 border-t">
                      Duración final estimada: <strong>{{ estimation.duration.total }} min</strong>
                      <span class="ms-1">(Puede variar según el comportamiento de la mascota)</span>
                    </div>
                  </VCard>
                </VExpandTransition>

                <!-- Botón de Envío -->
                <VBtn
                  type="submit"
                  color="primary"
                  block
                  size="large"
                  :loading="submitting"
                  prepend-icon="ri-send-plane-line"
                  class="submit-booking-btn"
                >
                  Enviar Solicitud
                </VBtn>
              </VForm>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Historial de Solicitudes (Derecha) -->
        <VCol
          cols="12"
          md="7"
        >
          <VCard
            elevation="3"
            rounded="lg"
            class="list-card"
          >
            <VCardTitle class="pa-6 border-b d-flex justify-space-between align-center">
              <span class="text-h6 font-weight-bold text-primary d-flex align-center ga-2">
                <VIcon icon="ri-history-line" /> Mis Reservas y Solicitudes
              </span>
              <VBtn
                icon="ri-refresh-line"
                variant="text"
                size="small"
                :loading="loading"
                @click="fetchDependencies"
              />
            </VCardTitle>

            <!-- Cargando -->
            <VCardText
              v-if="loading && !appointments.length"
              class="text-center py-12"
            >
              <VProgressCircular
                indeterminate
                color="primary"
                size="48"
              />
            </VCardText>

            <!-- Vacío -->
            <VCardText
              v-else-if="!appointments.length"
              class="text-center py-12 text-medium-emphasis"
            >
              <VIcon
                icon="ri-calendar-close-line"
                size="48"
                class="mb-2"
              />
              <p class="text-body-1 font-weight-medium">
                No has solicitado citas aún.
              </p>
              <p class="text-body-2">
                Completa el formulario de la izquierda para enviar tu primera solicitud.
              </p>
            </VCardText>

            <!-- Tabla de Citas -->
            <div
              v-else
              class="table-responsive"
            >
              <VTable>
                <thead>
                  <tr>
                    <th>Mascota</th>
                    <th>Servicio</th>
                    <th>Fecha sugerida</th>
                    <th>Estado</th>
                    <th>Notas</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="app in appointments"
                    :key="app.id"
                    class="booking-row"
                  >
                    <td>
                      <span class="font-weight-medium text-primary">{{ app.pet_name }}</span>
                    </td>
                    <td>{{ app.service_name }}</td>
                    <td>{{ app.start_time }}</td>
                    <td>
                      <VChip
                        :color="getStatusColor(app.status)"
                        size="small"
                        class="font-weight-medium text-uppercase"
                      >
                        {{ getStatusLabel(app.status) }}
                      </VChip>
                    </td>
                    <td>
                      <div
                        class="text-truncate notes-text"
                        style="max-width: 150px;"
                        :title="app.notes"
                      >
                        {{ app.notes || '-' }}
                      </div>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>
          </VCard>
        </VCol>
      </VRow>
    </VContainer>

    <!-- Snackbar de avisos -->
    <VSnackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="5000"
      location="top end"
    >
      {{ snackbarText }}
    </VSnackbar>
  </div>
</template>

<style scoped>
.cliente-reservar-page {
  min-height: 100vh;
}

.max-w-600 {
  max-width: 600px;
}

.booking-card {
  border: 1px solid rgba(var(--v-theme-primary), 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.booking-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
}

.booking-card-header {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.85) 100%);
}

.list-card {
  border: 1px solid rgba(var(--v-theme-primary), 0.08);
}

.booking-row {
  transition: background-color 0.2s ease;
}

.booking-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.02);
}

.notes-text {
  font-size: 0.85rem;
  color: rgba(0, 0, 0, 0.6);
}
</style>
