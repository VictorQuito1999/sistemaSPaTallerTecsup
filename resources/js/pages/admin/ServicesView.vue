<script setup>
import { useAuthStore } from '@/stores/auth'
import { apiFetch } from '@/utils/apiFetch'

const auth = useAuthStore()

const items = ref([])
const loading = ref(false)
const dialog = ref(false)
const editedIndex = ref(-1)

const editedItem = ref({
  id: null,
  name: '',
  type: 'grooming',
  base_duration_min: 30,
  base_price: 0,
  is_active: true,
})

const defaultItem = {
  id: null,
  name: '',
  type: 'grooming',
  base_duration_min: 30,
  base_price: 0,
  is_active: true,
}

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Nombre', key: 'name' },
  { title: 'Tipo', key: 'type' },
  { title: 'Duración (min)', key: 'base_duration_min' },
  { title: 'Precio Base', key: 'base_price' },
  { title: 'Activo', key: 'is_active' },
  { title: 'Acciones', key: 'actions', sortable: false },
]

const serviceTypes = [
  { title: 'Peluquería / Grooming', value: 'grooming' },
  { title: 'Baño / SPA', value: 'bath' },
  { title: 'Consulta / Vet', value: 'consultation' },
  { title: 'Otro', value: 'other' },
]

const formTitle = computed(() => editedIndex.value === -1 ? 'Nuevo Servicio' : 'Editar Servicio')

async function fetchData() {
  loading.value = true
  try {
    const res = await apiFetch('services', { method: 'GET' }, auth.token)
    if (res.ok) items.value = await res.json()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function editItem(item) {
  editedIndex.value = items.value.indexOf(item)
  editedItem.value = { ...item }
  dialog.value = true
}

async function deleteItem(item) {
  if (!confirm('¿Seguro que deseas eliminar este servicio?')) return
  try {
    const res = await apiFetch(`services/${item.id}`, { method: 'DELETE' }, auth.token)
    if (res.ok) fetchData()
  } catch (e) {
    console.error(e)
  }
}

function close() {
  dialog.value = false
  nextTick(() => {
    editedItem.value = { ...defaultItem }
    editedIndex.value = -1
  })
}

async function save() {
  const method = editedIndex.value > -1 ? 'PUT' : 'POST'
  const url = editedIndex.value > -1 ? `services/${editedItem.value.id}` : 'services'

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
  <VContainer>
    <VRow class="mb-4 align-center">
      <VCol
        cols="12"
        md="8"
      >
        <h1 class="text-h4 font-weight-medium text-primary">
          Servicios del SPA
        </h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          Configura los precios y tiempos base de cada servicio.
        </p>
      </VCol>
      <VCol
        cols="12"
        md="4"
        class="text-md-end"
      >
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="dialog = true"
        >
          Nuevo Servicio
        </VBtn>
      </VCol>
    </VRow>

    <VCard
      rounded="lg"
      elevation="2"
    >
      <VDataTable
        :headers="headers"
        :items="items"
        :loading="loading"
        no-data-text="No hay servicios registrados"
      >
        <template #[`item.base_price`]="{ item }">
          ${{ Number(item.base_price).toFixed(2) }}
        </template>
        <template #[`item.is_active`]="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'error'"
            size="small"
          >
            {{ item.is_active ? 'Sí' : 'No' }}
          </VChip>
        </template>
        <template #[`item.actions`]="{ item }">
          <VIcon
            size="small"
            class="me-2"
            color="info"
            @click="editItem(item)"
          >
            ri-edit-line
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
    </VCard>

    <VDialog
      v-model="dialog"
      max-width="600px"
      persistent
    >
      <VCard rounded="lg">
        <VCardTitle class="pa-4">
          <span class="text-h5 font-weight-bold text-primary">{{ formTitle }}</span>
        </VCardTitle>
        <VCardText>
          <VContainer>
            <VRow dense>
              <VCol cols="12">
                <VTextField
                  v-model="editedItem.name"
                  label="Nombre del Servicio *"
                  variant="outlined"
                  required
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="editedItem.type"
                  :items="serviceTypes"
                  label="Tipo *"
                  variant="outlined"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model.number="editedItem.base_duration_min"
                  type="number"
                  label="Duración Base (min) *"
                  variant="outlined"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model.number="editedItem.base_price"
                  type="number"
                  label="Precio Base *"
                  variant="outlined"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
                class="d-flex align-center"
              >
                <VSwitch
                  v-model="editedItem.is_active"
                  label="Servicio Activo"
                  color="primary"
                  hide-details
                />
              </VCol>
            </VRow>
          </VContainer>
        </VCardText>
        <VCardActions class="pa-4 pt-0">
          <VSpacer />
          <VBtn
            color="error"
            variant="text"
            @click="close"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            variant="flat"
            @click="save"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VContainer>
</template>
