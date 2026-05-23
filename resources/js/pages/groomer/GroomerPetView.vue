<script setup>
import { getMockAppointmentPet } from '@/data/mockAdminCustomers'
import { temperamentLabel, weightTone } from '@/utils/petDisplay'

const route = useRoute()
const router = useRouter()

const appointmentId = computed(() => route.params.id)

const ctx = computed(() => getMockAppointmentPet(appointmentId.value))

const pet = computed(() => ctx.value?.pet ?? null)

const allergiesDisplay = computed(() => {
  const a = pet.value?.allergies
  if (a == null || String(a).trim() === '') {
    return 'Sin alergias registradas'
  }

  return a
})

const weightStyle = computed(() => weightTone(pet.value?.weight_kg))
</script>

<template>
  <div class="groomer-pet-page bg-surface">
    <VContainer
      v-if="ctx && pet"
      class="py-6"
    >
      <VBtn
        variant="text"
        prepend-icon="ri-arrow-left-line"
        class="mb-4"
        to="/groomer/today"
      >
        Mis citas del día
      </VBtn>
      <VRow class="mb-6">
        <VCol cols="12">
          <h1 class="text-h5 font-weight-medium text-primary">
            Mascota a atender
          </h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Cita #{{ appointmentId }} · {{ ctx.serviceName }} · {{ ctx.ownerName }} ·
            {{ new Date(ctx.scheduledAt).toLocaleString('es-ES', { dateStyle: 'medium', timeStyle: 'short' }) }}
          </p>
        </VCol>
      </VRow>

      <VRow class="mb-4">
        <VCol
          cols="12"
          md="4"
        >
          <VCard
            rounded="lg"
            class="pa-6 text-center h-100"
            color="primary"
            variant="tonal"
          >
            <div class="pet-hero rounded-lg mb-4 d-flex align-center justify-center bg-surface">
              <VIcon
                icon="ri-footprint-fill"
                size="64"
                color="primary"
              />
            </div>
            <div class="text-h5 font-weight-bold">
              {{ pet.name }}
            </div>
            <div class="text-body-2 text-medium-emphasis">
              {{ pet.category?.label }} · {{ pet.breed?.name }}
            </div>
          </VCard>
        </VCol>
        <VCol
          cols="12"
          md="8"
        >
          <VRow dense>
            <VCol cols="12">
              <VCard
                rounded="lg"
                class="pa-6 mb-4 highlight-card"
                color="error"
                variant="tonal"
              >
                <div class="text-overline mb-1">
                  Alergias
                </div>
                <div class="text-h6 font-weight-medium">
                  {{ allergiesDisplay }}
                </div>
              </VCard>
            </VCol>
            <VCol
              cols="12"
              sm="6"
            >
              <VCard
                rounded="lg"
                class="pa-6 h-100 highlight-card"
                color="primary"
                variant="tonal"
              >
                <div class="text-overline mb-1">
                  Temperamento
                </div>
                <div class="text-h4 font-weight-bold">
                  {{ temperamentLabel[pet.temperament] ?? pet.temperament }}
                </div>
              </VCard>
            </VCol>
            <VCol
              cols="12"
              sm="6"
            >
              <VCard
                rounded="lg"
                class="pa-6 h-100 highlight-card"
                :color="['error', 'warning'].includes(weightStyle.chipColor) ? weightStyle.chipColor : 'secondary'"
                variant="tonal"
              >
                <div class="text-overline mb-1">
                  Peso
                </div>
                <div
                  class="text-h4 font-weight-bold"
                  :class="weightStyle.textClass"
                >
                  {{ pet.weight_kg != null ? `${pet.weight_kg} kg` : '—' }}
                </div>
                <VChip
                  v-if="weightStyle.chip"
                  class="mt-2"
                  size="small"
                  variant="flat"
                  :color="weightStyle.chipColor"
                >
                  {{ weightStyle.chip }}
                </VChip>
              </VCard>
            </VCol>
          </VRow>
        </VCol>
      </VRow>

      <VAlert
        type="info"
        variant="tonal"
        rounded="lg"
        density="comfortable"
      >
        Vista solo lectura: no se puede editar desde aquí. Revisa alergias y temperamento antes de manipular al animal.
      </VAlert>
    </VContainer>

    <VContainer
      v-else
      class="py-12"
    >
      <VAlert
        type="warning"
        variant="tonal"
      >
        No hay datos de demostración para la cita #{{ appointmentId }}.
        <div class="mt-2">
          <VBtn
            variant="tonal"
            @click="router.push('/groomer/today')"
          >
            Volver a mis citas
          </VBtn>
        </div>
      </VAlert>
    </VContainer>
  </div>
</template>

<style scoped>
.groomer-pet-page {
  min-height: 100vh;
}

.pet-hero {
  min-height: 140px;
  border: 1px dashed rgba(var(--v-theme-primary), 0.35);
}

.highlight-card {
  min-height: 120px;
}
</style>
