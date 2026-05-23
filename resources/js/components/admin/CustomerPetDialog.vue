<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  modelValue: Boolean,
})

const emit = defineEmits(['update:modelValue', 'saved'])

const auth = useAuthStore()
const activeTab = ref(0)
const loading = ref(false)
const breeds = ref([])
const categories = ref([])
const error = ref('')


const customerForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  ci: '',
})

const petForm = ref({
  name: '',
  breed_id: null,
  gender: 'male',
  size: 'medium',
  birth_date: '',
  temperament: 'friendly',
})

const genderOptions = [
  { title: 'Macho', value: 'male' },
  { title: 'Hembra', value: 'female' },
]

const sizeItems = [
  { title: 'Pequeño', value: 'small' },
  { title: 'Mediano', value: 'medium' },
  { title: 'Grande', value: 'large' },
  { title: 'Gigante', value: 'giant' },
]

const temperamentOptions = [
  { title: 'Tranquilo', value: 'calm' },
  { title: 'Nervioso', value: 'nervous' },
  { title: 'Agresivo', value: 'aggressive' },
  { title: 'Amigable', value: 'friendly' },
]

async function fetchData() {
  try {
    const [resCat, resBreed] = await Promise.all([
      apiFetch('species-categories', { method: 'GET' }, auth.token),
      apiFetch('breeds', { method: 'GET' }, auth.token),
    ])

    if (resCat.ok) categories.value = await resCat.json()
    if (resBreed.ok) breeds.value = await resBreed.json()
  } catch (e) {
    console.error('Error fetching data:', e)
  }
}

async function saveAll() {
  loading.value = true
  error.value = ''
  try {
    // 1. Crear Cliente
    const resCust = await apiFetch('customers', {
      method: 'POST',
      body: JSON.stringify(customerForm.value),
    }, auth.token)

    if (!resCust.ok) {
      const data = await resCust.json()
      throw new Error(data.message || 'Error al crear cliente')
    }

    const customer = await resCust.json()

    // 2. Crear Mascota vinculada
    const resPet = await apiFetch('pets', {
      method: 'POST',
      body: JSON.stringify({
        ...petForm.value,
        birth_date: petForm.value.birth_date || null,
        customer_id: customer.id,
      }),
    }, auth.token)

    if (!resPet.ok) {
      const data = await resPet.json()
      throw new Error(data.message || 'Error al crear mascota')
    }

    emit('saved')
    close()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function close() {
  emit('update:modelValue', false)
  resetForms()
}

function resetForms() {
  activeTab.value = 0
  customerForm.value = { first_name: '', last_name: '', email: '', phone: '', ci: '' }
  petForm.value = { name: '', breed_id: null, gender: 'male', size: 'medium', birth_date: '', temperament: 'friendly' }
  error.value = ''
}

onMounted(fetchData)
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="600"
    persistent
    @update:model-value="val => emit('update:modelValue', val)"
  >
    <VCard rounded="lg">
      <VCardTitle class="pa-4 d-flex justify-space-between align-center">
        <span class="text-h6">Nuevo Cliente + Mascota</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          density="compact"
          @click="close"
        />
      </VCardTitle>

      <VTabs
        v-model="activeTab"
        fixed-tabs
        color="primary"
      >
        <VTab :value="0">
          Cliente
        </VTab>
        <VTab :value="1">
          Mascota
        </VTab>
      </VTabs>

      <VDivider />

      <VAlert
        v-if="error"
        type="error"
        variant="tonal"
        class="ma-4"
        closable
      >
        {{ error }}
      </VAlert>

      <VCardText class="pa-4">
        <VWindow v-model="activeTab">
          <!-- Paso 1: Cliente -->
          <VWindowItem :value="0">
            <VRow dense>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="customerForm.first_name"
                  label="Nombres"
                  required
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="customerForm.last_name"
                  label="Apellidos"
                  required
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="customerForm.email"
                  label="Email"
                  type="email"
                  required
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="customerForm.phone"
                  label="Teléfono"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="customerForm.ci"
                  label="CI / Documento"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <!-- Paso 2: Mascota -->
          <VWindowItem :value="1">
            <VRow dense>
              <VCol cols="12">
                <VTextField
                  v-model="petForm.name"
                  label="Nombre de la Mascota"
                  required
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="petForm.breed_id"
                  :items="breeds"
                  item-title="name"
                  item-value="id"
                  label="Raza"
                  required
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="petForm.gender"
                  :items="genderOptions"
                  label="Género"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="petForm.birth_date"
                  label="Fecha de nacimiento"
                  type="date"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="petForm.size"
                  :items="sizeItems"
                  label="Tamaño"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="petForm.temperament"
                  :items="temperamentOptions"
                  label="Temperamento"
                />
              </VCol>
            </VRow>
          </VWindowItem>
        </VWindow>
      </VCardText>


      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          v-if="activeTab > 0"
          variant="outlined"
          @click="activeTab--"
        >
          Anterior
        </VBtn>
        
        <VBtn
          v-if="activeTab === 0"
          color="primary"
          @click="activeTab++"
        >
          Siguiente
        </VBtn>
        
        <VBtn
          v-else
          color="primary"
          :loading="loading"
          @click="saveAll"
        >
          Guardar Todo
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
