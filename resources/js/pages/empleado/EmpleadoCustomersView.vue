<script setup>
import { mockStaffCustomers } from '@/data/mockOperations'

const search = ref('')

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) {
    return mockStaffCustomers
  }

  return mockStaffCustomers.filter(
    c =>
      c.fullName.toLowerCase().includes(q)
      || c.ci.toLowerCase().includes(q)
      || c.phone.includes(q),
  )
})

const headers = [
  { title: 'Nombre', key: 'fullName' },
  { title: 'CI', key: 'ci' },
  { title: 'Teléfono', key: 'phone' },
  { title: 'Mascotas', key: 'petsCount', align: 'center' },
  { title: '', key: 'actions', sortable: false },
]
</script>

<template>
  <VContainer class="py-8">
    <VRow class="mb-4">
      <VCol cols="12">
        <h1 class="text-h5 font-weight-medium text-primary">
          Clientes
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Búsqueda rápida (mock) · recepción
        </p>
      </VCol>
    </VRow>

    <VCard
      class="mb-4 pa-4"
      rounded="lg"
    >
      <VTextField
        v-model="search"
        label="Buscar"
        prepend-inner-icon="ri-search-line"
        variant="outlined"
        density="comfortable"
        hide-details
        clearable
      />
    </VCard>

    <VCard rounded="lg">
      <VDataTable
        :headers="headers"
        :items="filtered"
        item-value="id"
        hide-default-footer
      >
        <template #item.actions="{ item }">
          <VBtn
            size="small"
            variant="tonal"
            :to="`/empleado/clientes/${item.id}`"
          >
            Ver ficha
          </VBtn>
        </template>
      </VDataTable>
    </VCard>
  </VContainer>
</template>
