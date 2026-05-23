<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'
import CustomerPetDialog from '@/components/admin/CustomerPetDialog.vue'

const auth = useAuthStore()
const router = useRouter()
const search = ref('')
const statusFilter = ref('all')
const customers = ref([])
const loading = ref(false)
const showDialog = ref(false)

const statusItems = [
  { title: 'Todos', value: 'all' },
  { title: 'Activo', value: 'active' },
  { title: 'Bloqueado', value: 'blocked' },
]

const headers = [
  { title: 'Nombre', key: 'full_name' },
  { title: 'CI', key: 'ci' },
  { title: 'Teléfono', key: 'phone' },
  { title: 'Mascotas', key: 'pets_count', align: 'center' },
  { title: 'Estado', key: 'status' },
  { title: '', key: 'actions', sortable: false, align: 'end' },
]

async function fetchCustomers() {
  loading.value = true
  try {
    const res = await apiFetch('customers', { method: 'GET' }, auth.token)
    if (res.ok) {
      customers.value = await res.json()
    }
  } catch (e) {
    console.error('Error fetching customers:', e)
  } finally {
    loading.value = false
  }
}

const rows = computed(() => {
  let list = [...customers.value]
  const q = search.value?.trim().toLowerCase()
  if (q) {
    list = list.filter(
      r =>
        r.full_name.toLowerCase().includes(q)
        || (r.ci && r.ci.toLowerCase().includes(q))
        || (r.phone && r.phone.toLowerCase().includes(q)),
    )
  }
  if (statusFilter.value !== 'all') {
    list = list.filter(r => r.status === statusFilter.value)
  }

  return list
})

const statusChip = row => ({
  color: row.status === 'blocked' ? 'error' : 'success',
  label: row.status === 'blocked' ? 'Bloqueado' : 'Activo',
})

const goProfile = id => {
  router.push({ name: 'admin-customers-detail', params: { id: String(id) } })
}

onMounted(fetchCustomers)
</script>

<template>
  <div>
    <VRow class="mb-4">
      <VCol
        cols="12"
        class="d-flex justify-space-between align-center"
      >
        <div>
          <h2 class="text-h5 mb-1">
            Clientes
          </h2>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Listado de clientes registrados en el sistema.
          </p>
        </div>
        <VBtn
          color="primary"
          prepend-icon="ri-user-add-line"
          @click="showDialog = true"
        >
          Nuevo Cliente
        </VBtn>
      </VCol>
    </VRow>

    <VCard
      class="mb-4 pa-4"
      rounded="lg"
    >
      <VRow dense>
        <VCol
          cols="12"
          md="6"
        >
          <VTextField
            v-model="search"
            label="Buscar"
            placeholder="Nombre, CI o teléfono"
            prepend-inner-icon="ri-search-line"
            variant="outlined"
            density="comfortable"
            hide-details
            clearable
          />
        </VCol>
        <VCol
          cols="12"
          md="4"
        >
          <VSelect
            v-model="statusFilter"
            :items="statusItems"
            item-title="title"
            item-value="value"
            label="Estado"
            variant="outlined"
            density="comfortable"
            hide-details
          />
        </VCol>
      </VRow>
    </VCard>

    <VCard
      rounded="lg"
      :loading="loading"
    >
      <VDataTable
        :headers="headers"
        :items="rows"
        item-value="id"
        class="text-no-wrap"
      >
        <template #item.status="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="statusChip(item).color"
          >
            {{ statusChip(item).label }}
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <VBtn
            color="primary"
            variant="tonal"
            size="small"
            prepend-icon="ri-eye-line"
            @click="goProfile(item.id)"
          >
            Ver perfil
          </VBtn>
        </template>
      </VDataTable>
    </VCard>

    <CustomerPetDialog
      v-model="showDialog"
      @saved="fetchCustomers"
    />
  </div>
</template>
