<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  pet: {
    type: Object,
    default: null,
  },
  customerId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'save'])
const auth = useAuthStore()

const isOpen = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const categories = ref([])
const breeds = ref([])
const loading = ref(false)

const form = ref({
  name: '',
  categoryId: null,
  breedId: null,
  gender: 'male',
  size: 'medium',
  birth_date: '',
  allergies: '',
  temperament: 'friendly',
})

const categoryItems = computed(() =>
  categories.value.map(c => ({
    title: c.name || c.label,
    value: c.id,
  })),
)

const breedItems = computed(() => {
  const cid = form.value.categoryId
  if (!cid) return []
  
  return breeds.value
    .filter(b => b.category_id === cid)
    .map(b => ({ title: b.name, value: b.id }))
})

const sizeItems = [
  { title: 'Pequeño (ej. Chihuahua, Yorkie)', value: 'small' },
  { title: 'Mediano (ej. Beagle, Cocker)', value: 'medium' },
  { title: 'Grande (ej. Golden, Pastor)', value: 'large' },
  { title: 'Gigante (ej. Gran Danés, San Bernardo)', value: 'giant' },
]

const temperamentItems = [
  { title: 'Tranquilo', value: 'calm' },
  { title: 'Nervioso', value: 'nervous' },
  { title: 'Agresivo', value: 'aggressive' },
  { title: 'Amigable', value: 'friendly' },
]

async function fetchData() {
  loading.value = true
  try {
    const [resCat, resBreed] = await Promise.all([
      apiFetch('species-categories', { method: 'GET' }, auth.token),
      apiFetch('breeds', { method: 'GET' }, auth.token),
    ])

    if (resCat.ok) categories.value = await resCat.json()
    if (resBreed.ok) breeds.value = await resBreed.json()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    categoryId: null,
    breedId: null,
    gender: 'male',
    size: 'medium',
    birth_date: '',
    allergies: '',
    temperament: 'friendly',
  }
}

const hydrateFromPet = pet => {
  if (!pet) {
    resetForm()
    
    return
  }
  
  // Mapeo inverso de peso a tamaño para visualización
  let petSize = 'medium'
  const w = Number(pet.weight_kg)
  if (w <= 5) petSize = 'small'
  else if (w <= 15) petSize = 'medium'
  else if (w <= 30) petSize = 'large'
  else petSize = 'giant'

  form.value = {
    name: pet.name ?? '',
    categoryId: pet.breed?.category_id ?? pet.category_id ?? null,
    breedId: pet.breed_id ?? null,
    gender: pet.gender ?? 'male',
    size: petSize,
    birth_date: pet.birth_date ?? '',
    allergies: pet.allergies ?? '',
    temperament: pet.temperament ?? 'friendly',
  }
}

watch(() => props.modelValue, val => {
  if (val) {
    fetchData()
    hydrateFromPet(props.pet)
  }
})

const close = () => {
  isOpen.value = false
}

const submit = () => {
  const payload = {
    id: props.pet?.id ?? null,
    customer_id: props.pet?.customer_id ?? props.customerId,
    name: form.value.name.trim(),
    breed_id: form.value.breedId,
    gender: form.value.gender,
    size: form.value.size, // El backend mapeará esto a peso
    birth_date: form.value.birth_date || null,
    allergies: form.value.allergies.trim() || null,
    temperament: form.value.temperament,
    is_active: true,
  }

  emit('save', payload)
  close()
}

const title = computed(() => (props.pet ? 'Editar mascota' : 'Nueva mascota'))
const canSubmit = computed(() => form.value.name && form.value.breedId)
</script>

<template>
  <VDialog
    v-model="isOpen"
    max-width="560"
    scrollable
  >
    <VCard
      rounded="lg"
      :loading="loading"
    >
      <VCardTitle class="d-flex align-center justify-space-between py-4 px-6 bg-primary text-white">
        <span class="text-h6 font-weight-medium">{{ title }}</span>
        <VBtn
          icon
          variant="text"
          color="white"
          @click="close"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VCardText class="pa-6">
        <VForm @submit.prevent="submit">
          <VTextField
            v-model="form.name"
            label="Nombre de la mascota *"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            required
            hide-details="auto"
          />

          <VSelect
            v-model="form.categoryId"
            :items="categoryItems"
            label="Especie *"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details="auto"
            required
          />

          <VSelect
            v-model="form.breedId"
            :items="breedItems"
            label="Raza *"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            :disabled="!form.categoryId"
            hide-details="auto"
            required
          />

          <div class="mb-4">
            <div class="text-caption text-medium-emphasis mb-2">
              Género *
            </div>
            <VRadioGroup
              v-model="form.gender"
              inline
              hide-details
            >
              <VRadio
                label="Macho"
                value="male"
              />
              <VRadio
                label="Hembra"
                value="female"
              />
            </VRadioGroup>
          </div>

          <VTextField
            v-model="form.birth_date"
            label="Fecha de nacimiento"
            type="date"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details="auto"
          />

          <VSelect
            v-model="form.size"
            :items="sizeItems"
            label="Tamaño *"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details="auto"
            required
            hint="Esto definirá el peso estimado para el cálculo de tiempo"
            persistent-hint
          />

          <VTextarea
            v-model="form.allergies"
            label="Alergias / Observaciones"
            variant="outlined"
            rows="2"
            class="mb-4"
            hide-details="auto"
          />

          <VSelect
            v-model="form.temperament"
            :items="temperamentItems"
            label="Temperamento *"
            variant="outlined"
            density="comfortable"
            class="mb-6"
            hide-details="auto"
            required
          />

          <div class="d-flex justify-end ga-2">
            <VBtn
              variant="text"
              color="secondary"
              @click="close"
            >
              Cancelar
            </VBtn>
            <VBtn
              type="submit"
              color="primary"
              :disabled="!canSubmit"
            >
              Guardar
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

