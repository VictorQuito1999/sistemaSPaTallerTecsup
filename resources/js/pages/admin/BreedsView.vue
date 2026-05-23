<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const items = ref([])
const categories = ref([])
const loading = ref(false)
const dialog = ref(false)
const editedIndex = ref(-1)

const editedItem = ref({
  id: null,
  name: '',
  category_id: null,
  size: 'medium',
  duration_factor: 1.0,
})

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre de Raza', key: 'name' },
  { title: 'Especie', key: 'category.name' },
  { title: 'Tamaño Std', key: 'size' },
  { title: 'Complejidad / Tiempo', key: 'complexity_label' },
  { title: 'Acciones', key: 'actions', sortable: false },
]

const complexityOptions = [
  { title: 'Pelo Corto / Fácil (Base)', value: 1.0 },
  { title: 'Pelo Largo / Medio (+20%)', value: 1.2 },
  { title: 'Complejo / Exótico (+50%)', value: 1.5 },
]

const sizeOptions = [
  { title: 'Muy Pequeño', value: 'extra_small' },
  { title: 'Pequeño', value: 'small' },
  { title: 'Mediano', value: 'medium' },
  { title: 'Grande', value: 'large' },
  { title: 'Muy Grande', value: 'extra_large' },
]


const itemsWithLabels = computed(() => items.value.map(item => {
  const complexity = complexityOptions.find(o => Number(o.value) === Number(item.duration_factor))
  
  return {
    ...item,
    complexity_label: complexity ? complexity.title : `${item.duration_factor}x`,
  }
}))

const formTitle = computed(() => editedIndex.value === -1 ? 'Nueva Raza' : 'Editar Raza')

async function fetchData() {
  loading.value = true
  try {
    const [resBreeds, resCats] = await Promise.all([
      apiFetch('breeds', { method: 'GET' }, auth.token),
      apiFetch('species-categories', { method: 'GET' }, auth.token),
    ])

    if (resBreeds.ok) items.value = await resBreeds.json()
    if (resCats.ok) categories.value = await resCats.json()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function editItem(item) {
  editedIndex.value = items.value.indexOf(item)
  editedItem.value = {
    id: item.id,
    name: item.name,
    category_id: item.category_id,
    size: item.size || 'medium',
    duration_factor: item.duration_factor || 1.0,
  }
  dialog.value = true
}

async function deleteItem(item) {
  if (!confirm('¿Seguro que deseas eliminar esta raza?')) return
  try {
    const res = await apiFetch(`breeds/${item.id}`, { method: 'DELETE' }, auth.token)
    if (res.ok) {
      fetchData()
    }
  } catch (e) {
    console.error(e)
  }
}

function close() {
  dialog.value = false
  nextTick(() => {
    editedItem.value = { id: null, name: '', category_id: null }
    editedIndex.value = -1
  })
}

async function save() {
  const method = editedItem.value.id ? 'PUT' : 'POST'
  const url = editedItem.value.id ? `breeds/${editedItem.value.id}` : 'breeds'
  
  try {
    const res = await apiFetch(url, {
      method,
      body: JSON.stringify(editedItem.value),
    }, auth.token)

    if (res.ok) {
      fetchData()
      close()
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchData)
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard
        title="Catálogo de Razas"
        subtitle="Gestiona las razas asociadas a cada especie."
      >
        <template #append>
          <VBtn
            color="primary"
            @click="dialog = true"
          >
            Nueva Raza
          </VBtn>
        </template>

        <VCardText>
          <VDataTable
            :headers="headers"
            :items="itemsWithLabels"
            :loading="loading"
            no-data-text="No hay razas registradas"
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
            <VRow dense>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="editedItem.category_id"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  label="Especie / Categoría *"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="editedItem.name"
                  label="Nombre de la Raza *"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="editedItem.size"
                  :items="sizeOptions"
                  label="Tamaño Estándar *"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="editedItem.duration_factor"
                  :items="complexityOptions"
                  label="Complejidad de la Raza *"
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
