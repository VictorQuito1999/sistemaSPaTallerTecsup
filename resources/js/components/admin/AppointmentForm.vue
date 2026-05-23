<script setup>
import PetFormModal from '@/components/cliente/PetFormModal.vue'

import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'


const emit = defineEmits(['created', 'error'])

const auth = useAuthStore()

const showPetModal = ref(false)

/** Payload alineado con AppointmentRequest */
// ... rest of refs

async function handleSavePet(payload) {
  try {
    const res = await apiFetch('pets', {
      method: 'POST',
      body: JSON.stringify(payload),
    }, auth.token)

    if (res.ok) {
      const newPet = await res.json()

      await fetchDependencies()
      form.value.pet_id = newPet.id
    }
  } catch (e) {
    console.error(e)
  }
}

const form = ref({
  pet_id: null,
  service_id: null,
  employee_id: null,
  start_time: '',
  status: 'pending',
  notes: '',
  payment_type: null,
})

const customers = ref([])
const services = ref([])
const employees = ref([])
const selectedCustomerId = ref(null)
const loadingCustomers = ref(false)
const submitting = ref(false)
const errorMessage = ref('')
const showPricingHint = ref(false)

const statusOptions = [
  { title: 'Pendiente', value: 'pending' },
  { title: 'Confirmada', value: 'confirmed' },
  { title: 'Cancelada', value: 'cancelled' },
  { title: 'Finalizada', value: 'finished' },
]

const paymentOptions = [
  { title: 'Efectivo', value: 'cash' },
  { title: 'QR', value: 'qr' },
  { title: 'Transferencia', value: 'transfer' },
]

const petOptions = computed(() => {
  const customer = customers.value.find(c => c.id === selectedCustomerId.value)
  if (!customer?.pets?.length) {
    return []
  }

  return customer.pets.map(p => ({
    title: `${p.name}${p.weight_kg != null ? ` (${p.weight_kg} kg)` : ''}`,
    value: p.id,
  }))
})

const estimation = ref(null)
const loadingEstimation = ref(false)

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

watch(selectedCustomerId, () => {
  form.value.pet_id = null
})


/**
 * Carga clientes (y mascotas anidadas) desde la API.

 * Fallback: mocks locales con la misma forma esperada del backend.
 */
async function fetchDependencies() {
  loadingCustomers.value = true
  errorMessage.value = ''
  try {
    const [resCust, resServ, resEmp] = await Promise.all([
      apiFetch('customers', { method: 'GET' }, auth.token),
      apiFetch('services', { method: 'GET' }, auth.token),
      apiFetch('admin/employees?specialty=Groomer&is_active=true', { method: 'GET' }, auth.token),
    ])

    if (resCust.ok) {
      const data = await resCust.json()

      customers.value = Array.isArray(data) ? data : data.data ?? []
    }
    
    if (resServ.ok) {
      services.value = await resServ.json()
    }

    if (resEmp.ok) {
      const data = await resEmp.json()
      const allEmp = Array.isArray(data) ? data : data.data ?? []
      
      // La API ya devuelve solo Groomers si pasamos el query param, 
      // pero mantenemos el mapeo para el selector.
      employees.value = allEmp.map(e => ({
        id: e.id,
        name: `${e.user?.first_name} ${e.user?.last_name || ''} (Groomer)`,
      }))
    }

  }
  catch (e) {
    console.error('Error fetching dependencies:', e)
    errorMessage.value = 'Error al cargar datos del servidor.'
  }
  finally {
    loadingCustomers.value = false
  }
}


function buildPayload() {
  return {
    pet_id: form.value.pet_id,
    service_id: form.value.service_id,
    employee_id: form.value.employee_id || null,
    start_time: form.value.start_time,
    status: form.value.status,
    notes: form.value.notes || null,
    payment_type: form.value.payment_type || null,
  }
}

async function submitAppointment() {
  errorMessage.value = ''
  submitting.value = true

  const payload = buildPayload()

  try {

    const res = await apiFetch('appointments', {
      method: 'POST',
      body: JSON.stringify(payload),
    }, auth.token)

    const data = await res.json().catch(() => ({}))

    if (res.status === 422) {
      const msg = data.message
        || Object.values(data.errors || {}).flat().join(' ')
        || 'No se pudo validar la cita.'

      errorMessage.value = msg
      emit('error', msg)
      
      return
    }

    if (!res.ok) {
      throw new Error(data.message || 'Error al registrar la cita.')
    }

    emit('created', data)
    resetForm()
  }
  catch (e) {
    errorMessage.value = e.message || 'Error de conexión.'
    emit('error', errorMessage.value)
  }
  finally {
    submitting.value = false
  }
}

function resetForm() {
  selectedCustomerId.value = null
  form.value = {
    pet_id: null,
    service_id: null,
    employee_id: null,
    start_time: '',
    status: 'pending',
    notes: '',
    payment_type: null,
  }
  showPricingHint.value = false
}

onMounted(() => {
  fetchDependencies()
})

defineExpose({ fetchDependencies, resetForm })
</script>


<template>
  <VCard
    rounded="lg"
    class="pa-4"
  >
    <VCardTitle class="text-h6 pb-2">
      Nueva cita
    </VCardTitle>
    <VCardSubtitle class="mb-4">
      Campos alineados con AppointmentRequest del backend.
    </VCardSubtitle>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-4"
      closable
      @click:close="errorMessage = ''"
    >
      {{ errorMessage }}
    </VAlert>

    <VForm @submit.prevent="submitAppointment">
      <VRow dense>
        <VCol
          cols="12"
          md="6"
        >
          <VSelect
            v-model="selectedCustomerId"
            :items="customers"
            item-title="full_name"
            item-value="id"
            label="Cliente"
            :loading="loadingCustomers"
            clearable
            prepend-inner-icon="ri-user-line"
          />
        </VCol>
        <VCol
          cols="12"
          md="6"
        >
          <div class="d-flex align-center ga-2">
            <VSelect
              v-model="form.pet_id"
              :items="petOptions"
              label="Mascota"
              :disabled="!selectedCustomerId"
              clearable
              prepend-inner-icon="ri-footprint-line"
              class="flex-grow-1"
            />
            <VBtn
              v-if="selectedCustomerId"
              icon="ri-add-line"
              variant="tonal"
              color="primary"
              size="small"
              title="Registrar nueva mascota para este cliente"
              @click="showPetModal = true"
            />
          </div>
        </VCol>

        <VCol
          cols="12"
          md="6"
        >
          <VSelect
            v-model="form.service_id"
            :items="services"
            item-title="name"
            item-value="id"
            label="Servicio"
            clearable
            prepend-inner-icon="ri-scissors-line"
          />
        </VCol>
        <VCol
          cols="12"
          md="6"
        >
          <VSelect
            v-model="form.employee_id"
            :items="employees"
            item-title="name"
            item-value="id"
            label="Groomer (opcional)"
            clearable
            prepend-inner-icon="ri-team-line"
          />
        </VCol>
        <VCol cols="12">
          <VTextField
            v-model="form.start_time"
            label="Fecha y Hora de Inicio *"
            type="datetime-local"
            min="09:00"
            max="18:00"
            prepend-inner-icon="ri-calendar-event-line"
            persistent-hint
            hint="Selecciona el día y la hora exacta de la cita (Habilitado para el día de hoy)"
            variant="outlined"
            color="primary"
            class="datetime-large-input"
          />
        </VCol>

        <VCol
          cols="12"
          md="6"
        >
          <VSelect
            v-model="form.status"
            :items="statusOptions"
            label="Estado"
            prepend-inner-icon="ri-flag-line"
          />
        </VCol>
        <VCol
          cols="12"
          md="6"
        >
          <VSelect
            v-model="form.payment_type"
            :items="paymentOptions"
            label="Tipo de pago (opcional)"
            clearable
            prepend-inner-icon="ri-bank-card-line"
          />
        </VCol>
        <VCol cols="12">
          <VTextarea

            v-model="form.notes"
            label="Notas"
            rows="2"
            auto-grow
            maxlength="500"
            counter="500"
          />
        </VCol>

        <!-- Bloque de Estimación Dinámica -->
        <VCol cols="12">
          <VExpandTransition>
            <VCard
              v-if="estimation"
              variant="flat"
              color="grey-lighten-4"
              rounded="lg"
              class="pa-4 mb-2 border"
            >
              <div class="d-flex justify-space-between align-center mb-2">
                <span class="text-subtitle-2 text-medium-emphasis">Desglose de Costos</span>
                <VIcon
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
                <span class="text-subtitle-1 font-weight-bold">Total a cobrar:</span>
                <span class="text-h6 font-weight-bold text-primary">${{ estimation.price.total }}</span>
              </div>

              <div class="text-caption text-medium-emphasis mt-2 pt-2 border-t">
                Duración final estimada: <strong>{{ estimation.duration.total }} min</strong>
                <span class="ms-1">(Base: {{ estimation.duration.base }} min + Extra: {{ estimation.duration.surcharge }} min)</span>
              </div>
            </VCard>
          </VExpandTransition>

          <div
            v-if="loadingEstimation"
            class="d-flex align-center justify-center py-2"
          >
            <VProgressCircular
              indeterminate
              size="16"
              width="2"
              color="primary"
              class="me-2"
            />
            <span class="text-caption">Calculando estimación...</span>
          </div>
        </VCol>

        <VCol cols="12">
          <VBtn
            type="submit"
            color="primary"
            :loading="submitting"
            block
            size="large"
          >
            Registrar cita
          </VBtn>
        </VCol>
      </VRow>
    </VForm>

    <PetFormModal
      v-model="showPetModal"
      :customer-id="selectedCustomerId"
      @save="handleSavePet"
    />
  </VCard>
</template>

<style scoped>
.datetime-large-input :deep(input::-webkit-calendar-picker-indicator) {
  cursor: pointer;
  filter: invert(0.4) sepia(1) saturate(5) hue-rotate(175deg); /* Match primary color roughly */
  margin-left: 8px;
}

.datetime-large-input :deep(input) {
  font-size: 1.1rem;
  font-weight: 500;
  padding-top: 8px;
  padding-bottom: 8px;
}
</style>

