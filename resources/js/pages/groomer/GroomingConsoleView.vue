<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const serviceId = computed(() => Number(route.params.id))
const service = ref(null)
const isLoading = ref(true)
const isSubmitting = ref(false)
const errorMessage = ref('')

const checklistState = ref({})
const suppliesUsed = ref({})
const sessionNotes = ref('')

const elapsedSeconds = ref(0)
let timerId = null

async function loadConsoleData() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await apiFetch(`appointments/${serviceId.value}/grooming-console`, { method: 'GET' }, auth.token)
    if (res.ok) {
      const data = await res.json()

      service.value = {
        id: data.appointment_id,
        petName: data.pet_name,
        petWeight: data.pet_weight,
        petBreed: data.pet_breed,
        customerName: data.customer_name,
        serviceName: data.service_name,
        estimatedMinutes: data.estimated_minutes,
        checklist: data.checklist,
        suppliesCatalog: data.supplies_catalog,
        notes: data.notes,
      }

      // Inicializar estados de checklists e insumos
      const ck = {}
      const su = {}
      if (service.value.checklist) {
        service.value.checklist.forEach(c => {
          ck[c.id] = false
        })
      }
      if (service.value.suppliesCatalog) {
        service.value.suppliesCatalog.forEach(x => {
          su[x.id] = false
        })
      }
      checklistState.value = ck
      suppliesUsed.value = su
    } else {
      throw new Error('Error al cargar los detalles de la sesión de estética.')
    }
  } catch (e) {
    console.error(e)
    errorMessage.value = e.message
  } finally {
    isLoading.value = false
  }
}

const allChecklistDone = computed(() => {
  const s = service.value
  if (!s || !s.checklist || !s.checklist.length) {
    return true
  }

  return s.checklist.every(c => checklistState.value[c.id])
})

onMounted(() => {
  loadConsoleData()
  timerId = window.setInterval(() => {
    elapsedSeconds.value += 1
  }, 1000)
})

onUnmounted(() => {
  if (timerId) {
    clearInterval(timerId)
  }
})

const estimatedSeconds = computed(() => (service.value?.estimatedMinutes ?? 60) * 60)

const timerPercent = computed(() =>
  Math.min(100, (elapsedSeconds.value / estimatedSeconds.value) * 100),
)

const timerColor = computed(() => {
  if (elapsedSeconds.value > estimatedSeconds.value) {
    return 'error'
  }
  if (elapsedSeconds.value > estimatedSeconds.value * 0.75) {
    return 'warning'
  }

  return 'primary'
})

const formatDuration = secs => {
  const m = Math.floor(secs / 60)
  const s = secs % 60

  return `${m}m ${String(s).padStart(2, '0')}s`
}

const finalize = async () => {
  if (!allChecklistDone.value) return

  isSubmitting.value = true
  errorMessage.value = ''
  
  // Mapear checklist con su estado marcado
  const checklistPayload = service.value.checklist.map(c => ({
    id: c.id,
    label: c.label,
    completed: !!checklistState.value[c.id],
  }))

  // Mapear insumos consumidos
  const suppliesPayload = Object.keys(suppliesUsed.value)
    .filter(id => suppliesUsed.value[id])
    .map(id => Number(id))

  const payload = {
    checklist: checklistPayload,
    actual_duration_min: Math.ceil(elapsedSeconds.value / 60),
    notes: sessionNotes.value,
    supplies: suppliesPayload,
  }

  try {
    const res = await apiFetch(`appointments/${serviceId.value}/finish-grooming`, {
      method: 'POST',
      body: JSON.stringify(payload),
    }, auth.token)

    if (res.ok) {
      router.push('/groomer/today')
    } else {
      const data = await res.json()
      throw new Error(data.message || 'Error al finalizar el servicio.')
    }
  } catch (e) {
    console.error(e)
    errorMessage.value = e.message
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <VContainer class="py-8">
    <div
      v-if="isLoading"
      class="d-flex flex-column align-center justify-center py-12"
    >
      <VProgressCircular
        indeterminate
        color="primary"
        size="64"
        class="mb-4"
      />
      <div class="text-body-1 text-medium-emphasis">
        Cargando consola de trabajo...
      </div>
    </div>

    <div
      v-else-if="errorMessage"
      class="py-6"
    >
      <VAlert
        type="error"
        variant="tonal"
        class="mb-4"
      >
        {{ errorMessage }}
      </VAlert>
      <VBtn
        color="primary"
        to="/groomer/today"
      >
        Volver a la agenda
      </VBtn>
    </div>

    <template v-else-if="service">
      <VRow class="mb-4">
        <VCol cols="12">
          <h1 class="text-h5 font-weight-medium text-primary">
            Consola de trabajo
          </h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Cita #{{ serviceId }} · {{ service.petName }} — {{ service.serviceName }}
          </p>
        </VCol>
      </VRow>

      <VRow>
        <VCol
          cols="12"
          lg="4"
        >
          <VCard
            rounded="lg"
            class="pa-6 mb-4"
          >
            <div class="text-subtitle-2 mb-3 d-flex align-center ga-2">
              <VIcon
                icon="ri-timer-line"
                color="primary"
              />
              Tiempo Transcurrido
            </div>
            <div class="text-h4 mb-2 font-weight-bold">
              {{ formatDuration(elapsedSeconds) }}
            </div>
            <div class="text-caption text-medium-emphasis mb-3">
              Estimado: {{ service.estimatedMinutes }} min
            </div>
            <VProgressLinear
              :model-value="timerPercent"
              :color="timerColor"
              height="12"
              rounded
            />
            <VAlert
              v-if="elapsedSeconds > estimatedSeconds"
              type="warning"
              variant="tonal"
              density="compact"
              class="mt-4"
            >
              Tiempo superado vs estimado
            </VAlert>
          </VCard>

          <VCard
            rounded="lg"
            class="pa-6 mb-4"
          >
            <div class="text-subtitle-2 mb-3 d-flex align-center ga-2">
              <VIcon
                icon="ri-baidu-line"
                color="primary"
              />
              Información de Mascota
            </div>
            <div class="text-body-2 mb-1">
              <strong>Nombre:</strong> {{ service.petName }}
            </div>
            <div class="text-body-2 mb-1">
              <strong>Raza:</strong> {{ service.petBreed || 'Sin especificar' }}
            </div>
            <div class="text-body-2 mb-1">
              <strong>Peso:</strong> {{ service.petWeight ? `${service.petWeight} kg` : 'Sin especificar' }}
            </div>
            <div class="text-body-2">
              <strong>Dueño:</strong> {{ service.customerName }}
            </div>
          </VCard>
        </VCol>

        <VCol
          cols="12"
          lg="8"
        >
          <VCard
            rounded="lg"
            class="pa-6 mb-4"
          >
            <div class="text-subtitle-1 font-weight-medium mb-4">
              Checklist obligatorio
            </div>
            <VList class="bg-transparent pa-0">
              <VListItem
                v-for="c in service.checklist"
                :key="c.id"
                class="px-0 border-b py-3"
              >
                <template #prepend>
                  <VCheckbox
                    v-model="checklistState[c.id]"
                    hide-details
                    density="compact"
                    color="primary"
                  />
                </template>
                <VListItemTitle :class="checklistState[c.id] ? 'text-decoration-line-through text-medium-emphasis' : ''">
                  {{ c.label }}
                </VListItemTitle>
              </VListItem>
            </VList>
          </VCard>

          <VCard
            rounded="lg"
            class="pa-6 mb-4"
          >
            <div class="text-subtitle-1 font-weight-medium mb-2">
              Insumos utilizados (Inventario Real)
            </div>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Marca los productos empleados de tu stock actual para descontar del inventario.
            </p>
            <VRow
              v-if="service.suppliesCatalog && service.suppliesCatalog.length"
              dense
            >
              <VCol
                v-for="s in service.suppliesCatalog"
                :key="s.id"
                cols="12"
                sm="6"
              >
                <VCheckbox
                  v-model="suppliesUsed[s.id]"
                  hide-details
                  density="comfortable"
                  color="primary"
                >
                  <template #label>
                    <div>
                      <span>{{ s.name }}</span>
                      <span class="text-caption text-medium-emphasis ms-2">
                        ({{ s.stock }} {{ s.unit }} disp.)
                      </span>
                    </div>
                  </template>
                </VCheckbox>
              </VCol>
            </VRow>
            <div
              v-else
              class="text-body-2 text-medium-emphasis py-2"
            >
              No hay insumos registrados en el inventario.
            </div>
          </VCard>

          <VCard
            rounded="lg"
            class="pa-6 mb-4"
          >
            <div class="text-subtitle-1 font-weight-medium mb-3">
              Observaciones y Notas de la Sesión
            </div>
            <VTextarea
              v-model="sessionNotes"
              label="Notas para el expediente médico"
              rows="3"
              placeholder="Detalles sobre el comportamiento de la mascota, estado de la piel o recomendaciones para el dueño."
              variant="outlined"
              hide-details
            />
          </VCard>

          <VBtn
            color="success"
            size="large"
            block
            prepend-icon="ri-check-double-line"
            :disabled="!allChecklistDone || isSubmitting"
            :loading="isSubmitting"
            @click="finalize"
          >
            Finalizar servicio
          </VBtn>
          <p
            v-if="!allChecklistDone"
            class="text-caption text-medium-emphasis mt-2"
          >
            Completa todas las tareas del checklist para habilitar finalizar.
          </p>
        </VCol>
      </VRow>
    </template>
  </VContainer>
</template>

<style scoped>
.hover-elevation-4:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
}
</style>
