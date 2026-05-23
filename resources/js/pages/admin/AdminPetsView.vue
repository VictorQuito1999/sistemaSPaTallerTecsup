<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'
import PetFormModal from '@/components/cliente/PetFormModal.vue'

const auth = useAuthStore()
const items = ref([])
const loading = ref(false)
const petModal = ref(false)
const selectedPet = ref(null)

const headers = [
  { title: 'Mascota', key: 'name' },
  { title: 'Dueño', key: 'customer.full_name' },
  { title: 'Especie', key: 'breed.category.name' },
  { title: 'Raza', key: 'breed.name' },
  { title: 'Peso (kg)', key: 'weight_kg' },
  { title: 'Estado', key: 'is_active' },
  { title: 'Acciones', key: 'actions', sortable: false },
]


async function fetchPets() {
  loading.value = true
  try {
    const res = await apiFetch('pets', { method: 'GET' }, auth.token)
    if (res.ok) {
      items.value = await res.json()
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function openEdit(pet) {
  selectedPet.value = pet
  petModal.value = true
}

async function handleSave(payload) {
  const method = payload.id ? 'PUT' : 'POST'
  const url = payload.id ? `pets/${payload.id}` : 'pets'
  
  try {
    const res = await apiFetch(url, {
      method,
      body: JSON.stringify(payload),
    }, auth.token)

    if (res.ok) {
      fetchPets()
    }
  } catch (e) {
    console.error(e)
  }
}

async function deletePet(pet) {
  if (!confirm('¿Seguro que deseas eliminar esta mascota?')) return
  try {
    const res = await apiFetch(`pets/${pet.id}`, { method: 'DELETE' }, auth.token)
    if (res.ok) fetchPets()
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchPets)
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard
        title="Listado General de Mascotas"
        subtitle="Visualiza y gestiona todas las mascotas registradas en el sistema."
      >
        <VCardText>
          <VDataTable
            :headers="headers"
            :items="items"
            :loading="loading"
          >
            <template #item.is_active="{ item }">
              <VChip
                :color="item.is_active ? 'success' : 'error'"
                size="small"
                label
              >
                {{ item.is_active ? 'Activo' : 'Inactivo' }}
              </VChip>
            </template>

            <template #item.actions="{ item }">
              <VIcon
                size="small"
                class="me-2"
                @click="openEdit(item)"
              >
                ri-pencil-line
              </VIcon>
              <VIcon
                size="small"
                color="error"
                @click="deletePet(item)"
              >
                ri-delete-bin-line
              </VIcon>
            </template>
          </VDataTable>
        </VCardText>
      </VCard>
    </VCol>

    <PetFormModal
      v-model="petModal"
      :pet="selectedPet"
      @save="handleSave"
    />
  </VRow>
</template>
