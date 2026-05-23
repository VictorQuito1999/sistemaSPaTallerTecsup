<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  modelValue: Boolean,
  appointment: Object,
})

const emit = defineEmits(['update:modelValue', 'updated'])

const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const error = ref('')

const form = ref({
  status: 'pending',
  employee_id: null,
  start_time: '',
  notes: '',
})

const employees = ref([])

const statusOptions = [
  { title: 'Pendiente (En revisión)', value: 'pending' },
  { title: 'Confirmada', value: 'confirmed' },
  { title: 'En curso', value: 'in_progress' },
  { title: 'Cancelada', value: 'cancelled' },
]

watch(() => props.modelValue, val => {
  if (val && props.appointment) {
    let timePart = '00:00'
    if (props.appointment.start_time) {
      // Backend returns start_time as "YYYY-MM-DD HH:mm"
      const parts = props.appointment.start_time.split(' ')

      timePart = parts.length > 1 ? parts[1].substring(0, 5) : parts[0].substring(0, 5)
    }
    form.value = {
      status: props.appointment.status,
      employee_id: props.appointment.employee_id,
      start_time: `${props.appointment.appointment_date}T${timePart}`,
      notes: props.appointment.notes || '',
    }
    fetchEmployees()
  }
})

async function fetchEmployees() {
  loading.value = true
  try {
    const res = await apiFetch('admin/employees?specialty=Groomer&is_active=true', { method: 'GET' }, auth.token)
    if (res.ok) {
      const data = await res.json()

      employees.value = data.map(e => ({
        id: e.id,
        name: `${e.user?.first_name} ${e.user?.last_name || ''}`,
      }))
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function saveChanges() {
  saving.value = true
  error.value = ''
  
  try {
    const payload = {
      status: form.value.status,
      employee_id: form.value.employee_id,
      start_time: form.value.start_time.replace('T', ' '),
      notes: form.value.notes,
    }

    const res = await apiFetch(`appointments/${props.appointment.id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    }, auth.token)

    if (res.ok) {
      emit('updated')
      emit('update:modelValue', false)
    } else {
      const data = await res.json()

      error.value = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Error al actualizar.'
    }
  } catch(e) {
    error.value = 'Error de conexión.'
  } finally {
    saving.value = false
  }
}

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="500"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard
      v-if="appointment"
      rounded="lg"
    >
      <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between">
        <span>Revisar Cita / Solicitud</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          color="white"
          @click="close"
        />
      </VCardTitle>

      <VCardText class="pa-4">
        <div class="d-flex flex-column ga-1 mb-6">
          <div class="text-subtitle-2 font-weight-bold">
            Mascota: <span class="font-weight-regular">{{ appointment.pet_name }}</span>
          </div>
          <div class="text-subtitle-2 font-weight-bold">
            Servicio: <span class="font-weight-regular">{{ appointment.service_name }}</span>
          </div>
        </div>

        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          closable
          @click:close="error = ''"
        >
          {{ error }}
        </VAlert>

        <VForm @submit.prevent="saveChanges">
          <VSelect
            v-model="form.status"
            :items="statusOptions"
            label="Estado"
            variant="outlined"
            class="mb-4"
          />

          <VSelect
            v-model="form.employee_id"
            :items="employees"
            item-title="name"
            item-value="id"
            label="Asignar Groomer"
            variant="outlined"
            :loading="loading"
            class="mb-4"
          />

          <VTextField
            v-model="form.start_time"
            label="Fecha y Hora sugerida de Cita"
            type="datetime-local"
            variant="outlined"
            class="mb-4"
          />

          <VTextarea
            v-model="form.notes"
            label="Notas / Comentarios"
            variant="outlined"
            rows="3"
            class="mb-4"
          />

          <div class="d-flex justify-end ga-2">
            <VBtn
              variant="text"
              color="secondary"
              @click="close"
            >
              Cancelar
            </VBtn>
            <VBtn
              type="submit"
              color="primary"
              :loading="saving"
            >
              Guardar Cambios
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
