<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const logs = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const page = ref(1)

const paginationMeta = ref({
  currentPage: 1,
  perPage: 50,
  total: 0,
  lastPage: 1,
})

const headers = [
  { title: 'Evento', key: 'action', sortable: false, width: '32%' },
  { title: 'Usuario', key: 'userIdentity', sortable: false },
  { title: 'Fecha', key: 'displayOccurredAt', sortable: false },
  { title: 'IP', key: 'ip', sortable: false },
  { title: 'User-Agent', key: 'displayUserAgent', sortable: false },
]

const chipColor = category => {
  switch (category) {
  case 'success':
    return 'success'
  case 'login':
    return 'primary'
  case 'password':
    return 'warning'
  default:
    return 'secondary'
  }
}

const chipLabel = category => {
  switch (category) {
  case 'success':
    return 'Éxito'
  case 'login':
    return 'Acceso'
  case 'password':
    return 'Contraseña'
  default:
    return 'Otro'
  }
}

const formatOccurredAt = raw => {
  if (!raw)
    return '—'
  const d = new Date(String(raw).replace(' ', 'T'))

  return Number.isNaN(d.getTime())
    ? raw
    : d.toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'medium' })
}

const truncate = (text, max = 72) => {
  const s = String(text || '')
  if (s.length <= max)
    return s

  return `${s.slice(0, max)}…`
}

const loadLogs = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = new URLSearchParams({
      page: String(page.value),
      ['per_page']: String(paginationMeta.value.perPage),
    })

    const res = await apiFetch(`admin/audit-logs?${params}`, { method: 'GET' }, auth.token)
    const data = await res.json()
    if (!res.ok) {
      throw new Error(data.message || 'No se pudieron cargar los registros de auditoría.')
    }

    const m = data.meta
    if (m) {
      paginationMeta.value = {
        currentPage: m.current_page,
        perPage: m.per_page,
        total: m.total,
        lastPage: m.last_page,
      }
    }

    logs.value = (data.data || []).map(row => ({
      id: row.id,
      action: row.action,
      eventCategory: row.event_category,
      userIdentity: row.user_identity,
      ip: row.ip,
      userAgent: row.user_agent,
      displayOccurredAt: formatOccurredAt(row.occurred_at),
      displayUserAgent: truncate(row.user_agent, 80),
    }))
  }
  catch (e) {
    errorMessage.value = e.message
    logs.value = []
  }
  finally {
    isLoading.value = false
  }
}

const refresh = async () => {
  await loadLogs()
}

watch(page, () => {
  loadLogs()
})

onMounted(loadLogs)
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
            Auditoría y trazabilidad
          </h2>
          <p class="text-medium-emphasis mb-0">
            Registros recientes desde <code>storage/logs/audit.log</code> (ventana limitada en memoria).
          </p>
        </div>
        <VBtn
          color="primary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="refresh"
        >
          Refrescar
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
        :items="logs"
        :loading="isLoading"
        item-value="id"
        class="audit-log-table"
      >
        <template #item.action="{ item }">
          <div class="d-flex flex-column align-start ga-1 py-1">
            <VChip
              size="small"
              variant="tonal"
              :color="chipColor(item.eventCategory)"
            >
              {{ chipLabel(item.eventCategory) }}
            </VChip>
            <span class="text-body-2 text-wrap">{{ item.action }}</span>
          </div>
        </template>

        <template #item.userIdentity="{ item }">
          <span class="text-body-2 font-weight-medium">{{ item.userIdentity }}</span>
        </template>

        <template #item.displayUserAgent="{ item }">
          <span
            class="text-caption text-medium-emphasis d-inline-block text-truncate ua-cell"
            :title="item.userAgent || ''"
          >{{ item.displayUserAgent }}</span>
        </template>

        <template #bottom>
          <VDivider />
          <div class="d-flex align-center justify-space-between flex-wrap ga-3 pa-4">
            <span class="text-caption text-medium-emphasis">
              Mostrando página {{ paginationMeta.currentPage }} de {{ paginationMeta.lastPage }}
              ({{ paginationMeta.total }} eventos en ventana cargada)
            </span>
            <VPagination
              v-model="page"
              :length="paginationMeta.lastPage"
              :total-visible="7"
              rounded
              density="comfortable"
            />
          </div>
        </template>
      </VDataTable>
    </VCard>
  </div>
</template>

<style scoped>
.audit-log-table :deep(.ua-cell) {
  max-width: 220px;
}
</style>
