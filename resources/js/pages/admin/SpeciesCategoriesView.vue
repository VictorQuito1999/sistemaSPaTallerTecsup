<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const items = ref([])
const loading = ref(false)
const dialog = ref(false)
const editedIndex = ref(-1)

const editedItem = ref({
  id: null,
  name: '',
})

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre / Especie', key: 'name' },
  { title: 'Acciones', key: 'actions', sortable: false },
]

const formTitle = computed(() => editedIndex.value === -1 ? 'Nueva Especie' : 'Editar Especie')

async function fetchItems() {
  loading.value = true
  try {
    const res = await apiFetch('species-categories', { method: 'GET' }, auth.token)
    if (res.ok) {
      items.value = await res.json()
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function editItem(item) {
  editedIndex.value = items.value.indexOf(item)
  editedItem.value = Object.assign({}, item)
  dialog.value = true
}

async function deleteItem(item) {
  if (!confirm('¿Seguro que deseas eliminar esta especie?')) return
  try {
    const res = await apiFetch(`species-categories/${item.id}`, { method: 'DELETE' }, auth.token)
    if (res.ok) {
      fetchItems()
    }
  } catch (e) {
    console.error(e)
  }
}

function close() {
  dialog.value = false
  nextTick(() => {
    editedItem.value = { id: null, label: '' }
    editedIndex.value = -1
  })
}

async function save() {
  const method = editedItem.value.id ? 'PUT' : 'POST'
  const url = editedItem.value.id ? `species-categories/${editedItem.value.id}` : 'species-categories'
  
  try {
    const res = await apiFetch(url, {
      method,
      body: JSON.stringify(editedItem.value),
    }, auth.token)

    if (res.ok) {
      fetchItems()
      close()
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchItems)
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard
        title="Especies y Categorías"
        subtitle="Gestiona las especies de mascotas soportadas en el sistema."
      >
        <template #append>
          <VBtn
            color="primary"
            @click="dialog = true"
          >
            Nueva Especie
          </VBtn>
        </template>

        <VCardText>
          <VDataTable
            :headers="headers"
            :items="items"
            :loading="loading"
            no-data-text="No hay especies registradas"
          >
            <template #item.actions="{ item }">
              <VIcon
                size="small"
                class="me-2"
                @click="editItem(item)"
              >
                ri-pencil-line
              </VIcon>
              <VIcon
                size="small"
                color="error"
                @click="deleteItem(item)"
              >
                ri-delete-bin-line
              </VIcon>
            </template>
          </VDataTable>
        </VCardText>
      </VCard>
    </VCol>

    <VDialog
      v-model="dialog"
      max-width="500px"
    >
      <VCard>
        <VCardTitle>
          <span class="text-h5">{{ formTitle }}</span>
        </VCardTitle>

        <VCardText>
          <VContainer>
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="editedItem.name"
                  label="Nombre de la Especie (ej. Canino, Felino)"
                  variant="outlined"
                  required
                />
              </VCol>
            </VRow>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="text"
            @click="close"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            variant="elevated"
            @click="save"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VRow>
</template>
