<script setup>
import { useAuthStore } from '@/stores/auth'
import { ref } from 'vue'

const auth = useAuthStore()

// ─── DATOS MOCKEADOS ──────────────────────────────────────────
// 🔁 Cuando tengas el API listo, reemplaza esto por la llamada real
// y descomenta el bloque loadMetrics() de abajo
const metrics = ref({
  appointments_today: 12,
  new_customers: 5,
  active_groomers: 3,
  pending_approvals: 2,          // nuevo campo sugerido en el backend
  total_services_month: 96,
  top_services: [                // nuevo campo sugerido
    { name: 'Baño completo',   count: 34 },
    { name: 'Corte + baño',    count: 25 },
    { name: 'Corte estético',  count: 18 },
    { name: 'Corte de uñas',   count: 12 },
    { name: 'Desparasitación', count: 7  },
  ],
})

const appointments = ref([       // 🔁 vendrá de otro endpoint o del mismo
  { initials: 'LR', name: 'Luna Rodríguez',  pet: 'Golden Retriever', service: 'Baño completo', time: '09:00', status: 'confirmed' },
  { initials: 'MP', name: 'Max Pérez',       pet: 'Bulldog',          service: 'Corte + uñas', time: '10:30', status: 'in_progress' },
  { initials: 'BG', name: 'Bella García',    pet: 'Poodle',           service: 'Baño + corte', time: '12:00', status: 'confirmed' },
  { initials: 'RM', name: 'Rocky Martínez',  pet: 'Labrador',         service: 'Solo baño',    time: '14:00', status: 'pending' },
  { initials: 'CL', name: 'Coco López',      pet: 'Shih Tzu',         service: 'Corte estético',time: '16:30', status: 'confirmed' },
])
// ─────────────────────────────────────────────────────────────

const isLoading = ref(false)

/* 🔁 DESCOMENTAR cuando el API esté listo:
const loadMetrics = async () => {
  isLoading.value = true
  try {
    const response = await apiFetch('admin/dashboard-metrics', { method: 'GET' }, auth.token)
    if (!response.ok) throw new Error('No se pudieron cargar las métricas.')
    metrics.value = await response.json()
  } catch {
    console.error('Error cargando métricas')
  } finally {
    isLoading.value = false
  }
}
onMounted(loadMetrics)
*/

// Helpers
const maxService = computed(() =>
  Math.max(...metrics.value.top_services.map(s => s.count))
)

const statusMap = {
  confirmed:   { label: 'Confirmada',    color: 'success' },
  in_progress: { label: 'En proceso',    color: 'warning' },
  pending:     { label: 'Por confirmar', color: 'error'   },
}

const avatarColors = ['deep-purple', 'teal', 'orange', 'deep-orange', 'pink']
</script>

<template>
  <VRow>
    <!-- Header -->
    <VCol cols="12">
      <h2 class="text-h5 mb-1">Dashboard administrativo</h2>
      <p class="text-medium-emphasis">Resumen operativo de PetSpa V1.</p>
    </VCol>

    <!-- ── Tarjetas métricas ── -->
    <VCol cols="12" sm="6" md="3">
      <VCard :loading="isLoading">
        <VCardText class="d-flex align-center gap-4">
          <VAvatar color="deep-purple" variant="tonal" size="46" rounded>
            <VIcon icon="tabler-calendar-event" />
          </VAvatar>
          <div>
            <div class="text-overline">Citas hoy</div>
            <div class="text-h4">{{ metrics.appointments_today }}</div>
            <VChip size="x-small" color="success" class="mt-1">+3 vs ayer</VChip>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6" md="3">
      <VCard :loading="isLoading">
        <VCardText class="d-flex align-center gap-4">
          <VAvatar color="success" variant="tonal" size="46" rounded>
            <VIcon icon="tabler-users" />
          </VAvatar>
          <div>
            <div class="text-overline">Clientes nuevos</div>
            <div class="text-h4">{{ metrics.new_customers }}</div>
            <VChip size="x-small" color="success" class="mt-1">Este mes</VChip>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6" md="3">
      <VCard :loading="isLoading">
        <VCardText class="d-flex align-center gap-4">
          <VAvatar color="warning" variant="tonal" size="46" rounded>
            <VIcon icon="tabler-scissors" />
          </VAvatar>
          <div>
            <div class="text-overline">Peluqueros activos</div>
            <div class="text-h4">{{ metrics.active_groomers }}</div>
            <VChip size="x-small" color="warning" class="mt-1">1 en descanso</VChip>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6" md="3">
      <VCard :loading="isLoading">
        <VCardText class="d-flex align-center gap-4">
          <VAvatar color="error" variant="tonal" size="46" rounded>
            <VIcon icon="tabler-clock-hour-4" />
          </VAvatar>
          <div>
            <div class="text-overline">Pendientes</div>
            <div class="text-h4">{{ metrics.pending_approvals }}</div>
            <VChip size="x-small" color="error" class="mt-1">Por confirmar</VChip>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- ── Citas del día ── -->
    <VCol cols="12" md="6">
      <VCard :loading="isLoading">
        <VCardItem>
          <VCardTitle>Citas de hoy</VCardTitle>
          <VCardSubtitle>Próximas 5 citas programadas</VCardSubtitle>
        </VCardItem>
        <VCardText>
          <!-- 🔁 Reemplazar :items con el array del API -->
          <VList lines="two">
            <VListItem
              v-for="(appt, i) in appointments"
              :key="i"
            >
              <template #prepend>
                <VAvatar :color="avatarColors[i % avatarColors.length]" variant="tonal" size="36">
                  {{ appt.initials }}
                </VAvatar>
              </template>
              <VListItemTitle>{{ appt.name }}</VListItemTitle>
              <VListItemSubtitle>{{ appt.pet }} · {{ appt.service }}</VListItemSubtitle>
              <template #append>
                <div class="text-right">
                  <div class="text-caption mb-1">{{ appt.time }}</div>
                  <VChip size="x-small" :color="statusMap[appt.status].color">
                    {{ statusMap[appt.status].label }}
                  </VChip>
                </div>
              </template>
            </VListItem>
          </VList>
        </VCardText>
      </VCard>
    </VCol>

    <!-- ── Servicios top ── -->
    <VCol cols="12" md="6">
      <VCard :loading="isLoading">
        <VCardItem>
          <VCardTitle>Servicios más solicitados</VCardTitle>
          <VCardSubtitle>Top servicios del mes actual</VCardSubtitle>
        </VCardItem>
        <VCardText>
          <!-- 🔁 Reemplazar con metrics.top_services[] del API -->
          <div
            v-for="(svc, i) in metrics.top_services"
            :key="i"
            class="mb-4"
          >
            <div class="d-flex justify-space-between mb-1">
              <span class="text-body-2">{{ svc.name }}</span>
              <span class="text-body-2 font-weight-medium">{{ svc.count }}</span>
            </div>
            <VProgressLinear
              :model-value="(svc.count / maxService) * 100"
              color="primary"
              rounded
              height="6"
            />
          </div>
          <VDivider class="my-3" />
          <div class="d-flex justify-space-between text-body-2">
            <span class="text-medium-emphasis">Total este mes</span>
            <!-- 🔁 metrics.total_services_month -->
            <span class="font-weight-medium">{{ metrics.total_services_month }} servicios</span>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
