<script setup>
import PetFormModal from '@/components/cliente/PetFormModal.vue'
import PetHealthModal from '@/components/admin/PetHealthModal.vue'
import { temperamentLabel, wMeta } from '@/utils/petDisplay'

const props = defineProps({
  pets: {
    type: Array,
    required: true,
  },
  customerId: {
    type: Number,
    default: null,
  },
  sectionTitle: {
    type: String,
    default: 'Mascotas',
  },
  sectionSubtitle: {
    type: String,
    default: '',
  },
  allowAdd: {
    type: Boolean,
    default: true,
  },
  allowEdit: {
    type: Boolean,
    default: true,
  },
  allowDelete: {
    type: Boolean,
    default: true,
  },
})


const emit = defineEmits(['savePet', 'deletePet'])


const calculateAge = birthDate => {
  if (!birthDate) return '—'
  const today = new Date()
  const birth = new Date(birthDate)
  let age = today.getFullYear() - birth.getFullYear()
  const m = today.getMonth() - birth.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  if (age < 0) return '—'
  if (age === 0) {
    const months = today.getMonth() - birth.getMonth() + (12 * (today.getFullYear() - birth.getFullYear()))
    
    return months <= 1 ? '1 mes' : `${months} meses`
  }
  
  return age === 1 ? '1 año' : `${age} años`
}


const modalOpen = ref(false)
const healthModalOpen = ref(false)
const editingPet = ref(null)
const selectedPet = ref(null)

const openCreate = () => {
  editingPet.value = null
  modalOpen.value = true
}

const openEdit = pet => {
  editingPet.value = pet
  modalOpen.value = true
}

const openHealth = pet => {
  selectedPet.value = pet
  healthModalOpen.value = true
}

const onSave = payload => {
  emit('savePet', payload)
}

const onDelete = pet => {
  emit('deletePet', pet)
}
</script>

<template>
  <div>
    <VRow
      class="mb-4 align-center"
      dense
    >
      <VCol
        cols="12"
        md="8"
      >
        <h3 class="text-h6 font-weight-medium">
          {{ sectionTitle }}
        </h3>
        <p
          v-if="sectionSubtitle"
          class="text-caption text-medium-emphasis mb-0"
        >
          {{ sectionSubtitle }}
        </p>
      </VCol>
      <VCol
        v-if="allowAdd"
        cols="12"
        md="4"
        class="d-flex justify-md-end"
      >
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openCreate"
        >
          Agregar mascota
        </VBtn>
      </VCol>
    </VRow>

    <VRow>
      <VCol
        v-for="pet in pets"
        :key="pet.id"
        cols="12"
        sm="6"
        lg="4"
      >
        <VHover v-slot="{ isHovering, props: hoverProps }">
          <VCard
            v-bind="hoverProps"
            class="pet-card h-100 d-flex flex-column transition-swing"
            :elevation="isHovering ? 10 : 2"
            rounded="lg"
          >
            <div class="pet-card-media pa-4 pb-0">
              <div class="pet-placeholder rounded-lg d-flex align-center justify-center">
                <VIcon
                  icon="ri-footprint-fill"
                  size="48"
                  class="text-primary-lighten-1"
                />
              </div>
            </div>
            <VCardTitle class="text-h6 pb-1">
              {{ pet.name }}
            </VCardTitle>
            <VCardSubtitle class="text-body-2 pb-2">
              {{ pet.breed?.category?.name ?? '—' }}
              <span class="text-medium-emphasis"> · </span>
              {{ pet.breed?.name ?? '—' }}
            </VCardSubtitle>

            <VCardText class="flex-grow-1 pt-0">
              <div
                v-if="pet.birth_date"
                class="d-flex align-center mb-3"
              >
                <VIcon
                  size="16"
                  class="me-1"
                  color="medium-emphasis"
                >
                  ri-calendar-line
                </VIcon>
                <span class="text-caption text-medium-emphasis">Edad: </span>
                <span class="text-caption font-weight-medium ms-1">{{ calculateAge(pet.birth_date) }}</span>
              </div>
              <div class="d-flex align-center flex-wrap ga-2 mb-3">
                <div
                  class="text-h6"
                  :class="wMeta(pet).textClass"
                >
                  {{ pet.weight_kg != null ? `${pet.weight_kg} kg` : '—' }}
                </div>
                <VChip
                  v-if="wMeta(pet).chip"
                  size="x-small"
                  variant="flat"
                  :color="wMeta(pet).chipColor"
                >
                  {{ wMeta(pet).chip }}
                </VChip>
              </div>
              <div class="text-caption text-medium-emphasis mb-1">
                Temperamento
              </div>
              <VChip
                size="small"
                variant="tonal"
                color="primary"
              >
                {{ temperamentLabel[pet.temperament] ?? pet.temperament }}
              </VChip>
            </VCardText>
            <VCardActions
              v-if="allowEdit || allowDelete"
              class="px-4 pb-4 pt-0"
            >
              <VBtn
                size="small"
                variant="tonal"
                color="info"
                prepend-icon="ri-health-book-line"
                @click="openHealth(pet)"
              >
                Salud
              </VBtn>
              <VBtn
                v-if="allowEdit"
                size="small"
                variant="tonal"
                color="primary"
                prepend-icon="ri-edit-line"
                @click="openEdit(pet)"
              >
                Editar
              </VBtn>
              <VBtn
                v-if="allowDelete"
                size="small"
                variant="text"
                color="error"
                prepend-icon="ri-delete-bin-line"
                @click="onDelete(pet)"
              >
                Eliminar
              </VBtn>
            </VCardActions>
          </VCard>
        </VHover>
      </VCol>
    </VRow>

    <PetFormModal
      v-model="modalOpen"
      :pet="editingPet"
      :customer-id="customerId"
      @save="onSave"
    />

    <PetHealthModal
      v-model="healthModalOpen"
      :pet="selectedPet"
    />
  </div>
</template>


<style scoped>
.pet-placeholder {
  height: 160px;
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.08) 0%,
    rgba(var(--v-theme-primary), 0.02) 100%
  );
  border: 1px dashed rgba(var(--v-theme-primary), 0.25);
}

.pet-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}
</style>
