<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'
import { useShopStore } from '@/stores/shop'
import AppointmentActionDialog from '@/components/admin/AppointmentActionDialog.vue'

const auth = useAuthStore()
const shop = useShopStore()
const router = useRouter()
const appointments = ref([])
const isLoading = ref(false)

const editDialog = ref(false)
const selectedAppointment = ref(null)

const statusMap = {
  confirmed: { label: 'Confirmada', color: 'success' },
  in_progress: { label: 'En curso', color: 'warning' },
  pending: { label: 'Pendiente', color: 'secondary' },
  finished: { label: 'Finalizada', color: 'info' },
  cancelled: { label: 'Cancelada', color: 'error' },
  paid: { label: 'Pagada', color: 'success' },
}

const fetchAppointments = async () => {
  isLoading.value = true
  try {
    const res = await apiFetch('appointments', { method: 'GET' }, auth.token)
    if (res.ok) {
      appointments.value = await res.json()
    }
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

const formatTime = fullTime => {
  if (!fullTime) return '00:00'
  const parts = String(fullTime).split(' ')
  const timePart = parts.length > 1 ? parts[1] : parts[0]
  
  return timePart.substring(0, 5)
}

function openShopForAppointment(appointmentId) {
  shop.setCheckoutAppointmentId(appointmentId)
  router.push('/empleado/shop')
}

function openEditDialog(appointment) {
  selectedAppointment.value = appointment
  editDialog.value = true
}

onMounted(fetchAppointments)
</script>

<template>
  <VContainer class="py-8">
    <VRow class="mb-4">
      <VCol cols="12">
        <h1 class="text-h5 font-weight-medium text-primary">
          Agenda global
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Vista operativa del día · recepción
        </p>
      </VCol>
    </VRow>

    <VCard
      rounded="lg"
      :loading="isLoading"
    >
      <VDataTable
        :items="appointments"
        :headers="[
          { title: 'Hora', key: 'start_time' },
          { title: 'Mascota', key: 'pet_name' },
          { title: 'Servicio', key: 'service_name' },
          { title: 'Estado', key: 'status' },
          { title: '', key: 'actions', sortable: false },
        ]"
        hide-default-footer
      >
        <template #item.start_time="{ item }">
          {{ formatTime(item.start_time) }}
        </template>
        <template #item.status="{ item }">
          <VChip
            size="small"
            :color="statusMap[item.status]?.color"
            variant="tonal"
          >
            {{ statusMap[item.status]?.label || item.status }}
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex flex-wrap ga-1">
            <VBtn
              size="small"
              variant="text"
              color="secondary"
              prepend-icon="ri-shopping-bag-3-line"
              @click="openShopForAppointment(item.id)"
            >
              Tienda
            </VBtn>
            <VBtn
              v-if="item.status === 'finished'"
              size="small"
              variant="tonal"
              color="primary"
              :to="`/empleado/checkout/${item.id}`"
            >
              Cobro
            </VBtn>
            <VBtn
              size="small"
              variant="elevated"
              color="primary"
              prepend-icon="ri-edit-line"
              @click="openEditDialog(item)"
            >
              Editar / Revisar
            </VBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AppointmentActionDialog
      v-model="editDialog"
      :appointment="selectedAppointment"
      @updated="fetchAppointments"
    />
  </VContainer>
</template>
