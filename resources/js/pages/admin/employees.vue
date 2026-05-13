<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const employees = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const dialog = ref(false)
const saving = ref(false)

const headers = [
  { title: 'Nombre', key: 'name' },
  { title: 'Correo', key: 'email' },
  { title: 'Teléfono', key: 'phone' },
  { title: 'CI', key: 'ci' },
  { title: 'Turno', key: 'shift' },
  { title: 'Especialidad', key: 'specialty' },
  { title: 'Estado', key: 'status' },
  { title: 'Acciones', key: 'actions', sortable: false },
]

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  ci: '',
  specialty: 'Groomer',
  shift: 'mañana',
})

const fullName = row => [row.user?.first_name, row.user?.last_name].filter(Boolean).join(' ') || '—'

const statusLabel = row => row.user?.is_active ? 'Activo' : 'Inactivo'

const loadEmployees = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await apiFetch('admin/employees', { method: 'GET' }, auth.token)
    const data = await res.json()
    if (!res.ok) {
      throw new Error(data.message || 'No se pudo cargar el listado.')
    }
    employees.value = data
  }
  catch (e) {
    errorMessage.value = e.message
  }
  finally {
    isLoading.value = false
  }
}

const openCreate = () => {
  form.value = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    ci: '',
    specialty: 'Groomer',
    shift: 'mañana',
  }
  dialog.value = true
}

const saveEmployee = async () => {
  saving.value = true
  errorMessage.value = ''
  try {
    const res = await apiFetch('admin/employees', {
      method: 'POST',
      body: JSON.stringify(form.value),
    }, auth.token)

    const data = await res.json()
    if (!res.ok) {
      const errs = data.errors ? Object.values(data.errors).flat().join(' ') : ''
      throw new Error(data.message || errs || 'Error al guardar.')
    }
    dialog.value = false
    await loadEmployees()
  }
  catch (e) {
    errorMessage.value = e.message
  }
  finally {
    saving.value = false
  }
}

const deactivate = async item => {
  if (!confirm('¿Marcar empleado como inactivo?')) {
    return
  }
  errorMessage.value = ''
  try {
    const res = await apiFetch(`admin/employees/${item.id}/deactivate`, { method: 'POST' }, auth.token)
    const data = await res.json()
    if (!res.ok) {
      throw new Error(data.message || 'No se pudo desactivar.')
    }
    await loadEmployees()
  }
  catch (e) {
    errorMessage.value = e.message
  }
}

onMounted(loadEmployees)
</script>

<template>
  <div>
    <VRow class="mb-4">
      <VCol
        cols="12"
        class="d-flex align-center justify-space-between flex-wrap ga-2"
      >
        <div>
          <h2 class="text-h5 mb-1">
            Empleados
          </h2>
          <p class="text-medium-emphasis mb-0">
            Gestión de personal (peluqueros).
          </p>
        </div>
        <VBtn
          color="primary"
          @click="openCreate"
        >
          Nuevo empleado
        </VBtn>
      </VCol>
    </VRow>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      {{ errorMessage }}
    </VAlert>

    <VCard>
      <VDataTable
        :headers="headers"
        :items="employees"
        :loading="isLoading"
        item-value="id"
      >
        <template #item.name="{ item }">
          {{ fullName(item) }}
        </template>
        <template #item.email="{ item }">
          {{ item.user?.email || '—' }}
        </template>
        <template #item.phone="{ item }">
          {{ item.user?.phone || '—' }}
        </template>
        <template #item.status="{ item }">
          <VChip
            :color="item.user?.is_active ? 'success' : 'secondary'"
            size="small"
            variant="tonal"
          >
            {{ statusLabel(item) }}
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <VBtn
            v-if="item.user?.is_active"
            size="small"
            variant="text"
            color="error"
            @click="deactivate(item)"
          >
            Desactivar
          </VBtn>
        </template>
      </VDataTable>
    </VCard>

    <VDialog
      v-model="dialog"
      max-width="560"
    >
      <VCard>
        <VCardTitle>Nuevo empleado</VCardTitle>
        <VCardText>
          <VForm @submit.prevent="saveEmployee">
            <VTextField
              v-model="form.first_name"
              label="Nombre"
              class="mb-2"
              required
            />
            <VTextField
              v-model="form.last_name"
              label="Apellido"
              class="mb-2"
            />
            <VTextField
              v-model="form.email"
              label="Correo (activación)"
              type="email"
              class="mb-2"
              required
            />
            <VTextField
              v-model="form.phone"
              label="Teléfono"
              class="mb-2"
              required
            />
            <VTextField
              v-model="form.ci"
              label="CI"
              class="mb-2"
              required
            />
            <VSelect 
              v-model="form.specialty"
              label="Especialidad"
              class="mb-2"
              :items="[
                {title:'Recepsionista'},
                {title:'Groomer'}]"
            />
            <VSelect
              v-model="form.shift"
              label="Turno"
              :items="[
                { title: 'Mañana', value: 'mañana' },
                { title: 'Tarde', value: 'tarde' },
                { title: 'Noche', value: 'noche' },
              ]"
            />
            <VCardActions class="px-0 pt-4">
              <VSpacer />
              <VBtn
                variant="text"
                @click="dialog = false"
              >
                Cancelar
              </VBtn>
              <VBtn
                type="submit"
                color="primary"
                :loading="saving"
              >
                Guardar
              </VBtn>
            </VCardActions>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
