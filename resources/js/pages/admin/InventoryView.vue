<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const search = ref('')
const products = ref([])
const loading = ref(false)

const rows = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) {
    return products.value
  }

  return products.value.filter(
    p =>
      String(p.name).toLowerCase().includes(q)
      || String(p.sku || '').toLowerCase().includes(q)
      || String(p.category || '').toLowerCase().includes(q),
  )
})

async function fetchProducts() {
  loading.value = true
  try {
    const res = await apiFetch('admin/products', { method: 'GET' }, auth.token)
    if (res.ok) {
      const data = await res.json()

      products.value = Array.isArray(data) ? data : (data.data ?? [])
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const isLow = row => row.stock <= row.min_stock

const headers = [
  { title: 'SKU', key: 'sku' },
  { title: 'Producto', key: 'name' },
  { title: 'Categoría', key: 'category' },
  { title: 'Stock', key: 'stock', align: 'end' },
  { title: 'Mín.', key: 'min_stock', align: 'end' },
  { title: 'Estado', key: 'alert', sortable: false },
]

onMounted(fetchProducts)
</script>

<template>
  <div>
    <VRow class="mb-4">
      <VCol cols="12">
        <h2 class="text-h5 mb-1">
          Inventario de Productos
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Control de stock y niveles mínimos en tiempo real desde la base de datos.
        </p>
      </VCol>
    </VRow>

    <VCard
      class="mb-4 pa-4"
      rounded="lg"
    >
      <VTextField
        v-model="search"
        label="Buscar producto"
        prepend-inner-icon="ri-search-line"
        variant="outlined"
        density="comfortable"
        hide-details
        clearable
      />
    </VCard>

    <VCard rounded="lg">
      <div
        v-if="loading"
        class="d-flex justify-center py-12"
      >
        <VProgressCircular
          indeterminate
          color="primary"
        />
      </div>
      <VDataTable
        v-else
        :headers="headers"
        :items="rows"
        item-value="id"
      >
        <template #item.stock="{ item }">
          <span :class="isLow(item) ? 'text-error font-weight-bold' : ''">
            {{ item.stock }} {{ item.unit || 'und' }}
          </span>
        </template>
        <template #item.alert="{ item }">
          <VChip
            v-if="isLow(item)"
            color="error"
            size="small"
            variant="flat"
          >
            Stock bajo
          </VChip>
          <VChip
            v-else
            color="success"
            size="small"
            variant="tonal"
          >
            OK
          </VChip>
        </template>
      </VDataTable>
    </VCard>
  </div>
</template>
