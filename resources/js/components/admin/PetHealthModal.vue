<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  modelValue: Boolean,
  pet: Object,
})

const emit = defineEmits(['update:modelValue'])

const auth = useAuthStore()
const activeTab = ref(0)
const loading = ref(false)
const healthData = ref({ vaccinations: [], photos: [] })
const showAddVaccine = ref(false)

const vaccineForm = ref({
  vaccine_type: '',
  application_date: new Date().toISOString().substr(0, 10),
  expiry_date: '',
  clinic_name: '',
})

async function fetchHealth() {
  if (!props.pet?.id) return
  loading.value = true
  try {
    const res = await apiFetch(`pets/${props.pet.id}/health`, { method: 'GET' }, auth.token)
    if (res.ok) {
      healthData.value = await res.json()
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function addVaccine() {
  try {
    const res = await apiFetch(`pets/${props.pet.id}/vaccinations`, {
      method: 'POST',
      body: JSON.stringify(vaccineForm.value),
    }, auth.token)

    if (res.ok) {
      showAddVaccine.value = false
      fetchHealth()
      vaccineForm.value = { vaccine_type: '', application_date: new Date().toISOString().substr(0, 10), expiry_date: '', clinic_name: '' }
    }
  } catch (e) {
    console.error(e)
  }
}

async function onUploadPhoto(e) {
  const file = e.target.files[0]
  if (!file) return

  const formData = new FormData()

  formData.append('file', file)
  formData.append('type', 'general')

  try {
    const res = await fetch(`/api/pets/${props.pet.id}/photos`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${auth.token}` },
      body: formData,
    })

    if (res.ok) fetchHealth()
  } catch (e) {
    console.error(e)
  }
}

watch(() => props.modelValue, val => {
  if (val) fetchHealth()
})
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="800"
    @update:model-value="val => emit('update:modelValue', val)"
  >
    <VCard
      v-if="pet"
      rounded="lg"
    >
      <VCardTitle class="pa-4 d-flex align-center ga-3">
        <VIcon
          icon="ri-health-book-line"
          color="info"
        />
        <div>
          <div class="text-h6">
            Salud: {{ pet.name }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Historial de vacunas y documentos
          </div>
        </div>
        <VSpacer />
        <VBtn
          icon="ri-close-line"
          variant="text"
          density="compact"
          @click="emit('update:modelValue', false)"
        />
      </VCardTitle>

      <VTabs
        v-model="activeTab"
        color="info"
        grow
      >
        <VTab :value="0">
          Vacunas
        </VTab>
        <VTab :value="1">
          Fotos / Documentos
        </VTab>
      </VTabs>

      <VDivider />

      <VCardText
        class="pa-0"
        style="min-height: 400px;"
      >
        <VWindow v-model="activeTab">
          <!-- Vacunas -->
          <VWindowItem :value="0">
            <div class="pa-4">
              <div class="d-flex justify-space-between align-center mb-4">
                <span class="text-subtitle-1 font-weight-medium">Registro de Vacunación</span>
                <VBtn
                  size="small"
                  color="info"
                  prepend-icon="ri-add-line"
                  @click="showAddVaccine = !showAddVaccine"
                >
                  Nueva Vacuna
                </VBtn>
              </div>

              <VExpandTransition>
                <div
                  v-if="showAddVaccine"
                  class="border rounded pa-4 mb-4 bg-grey-lighten-4"
                >
                  <VRow dense>
                    <VCol
                      cols="12"
                      md="6"
                    >
                      <VTextField
                        v-model="vaccineForm.vaccine_type"
                        label="Tipo de Vacuna"
                        density="compact"
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      md="6"
                    >
                      <VTextField
                        v-model="vaccineForm.application_date"
                        label="Fecha Aplicación"
                        type="date"
                        density="compact"
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      md="6"
                    >
                      <VTextField
                        v-model="vaccineForm.clinic_name"
                        label="Clínica"
                        density="compact"
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      md="6"
                      class="d-flex align-end"
                    >
                      <VBtn
                        block
                        color="info"
                        @click="addVaccine"
                      >
                        Guardar
                      </VBtn>
                    </VCol>
                  </VRow>
                </div>
              </VExpandTransition>

              <VTable v-if="healthData.vaccinations.length">
                <thead>
                  <tr>
                    <th>Vacuna</th>
                    <th>Fecha</th>
                    <th>Clínica</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="v in healthData.vaccinations"
                    :key="v.id"
                  >
                    <td>{{ v.vaccine_type }}</td>
                    <td>{{ v.application_date }}</td>
                    <td>{{ v.clinic_name || '—' }}</td>
                  </tr>
                </tbody>
              </VTable>
              <div
                v-else
                class="text-center py-8 text-medium-emphasis"
              >
                <VIcon
                  icon="ri-syringe-line"
                  size="48"
                  class="mb-2"
                />
                <div>No hay registros de vacunas.</div>
              </div>
            </div>
          </VWindowItem>

          <!-- Fotos -->
          <VWindowItem :value="1">
            <div class="pa-4">
              <div class="d-flex justify-space-between align-center mb-4">
                <span class="text-subtitle-1 font-weight-medium">Fotos y Documentos</span>
                <VBtn
                  size="small"
                  color="secondary"
                  prepend-icon="ri-upload-2-line"
                  @click="$refs.fileInput.click()"
                >
                  Subir Archivo
                </VBtn>
                <input
                  ref="fileInput"
                  type="file"
                  hidden
                  accept="image/*"
                  @change="onUploadPhoto"
                >
              </div>

              <VRow v-if="healthData.photos.length">
                <VCol
                  v-for="p in healthData.photos"
                  :key="p.id"
                  cols="12"
                  sm="4"
                >
                  <VCard
                    border
                    flat
                  >
                    <VImg
                      :src="'/storage/' + p.file_path"
                      height="150"
                      cover
                    />
                    <VCardSubtitle class="pt-2">
                      {{ p.file_name }}
                    </VCardSubtitle>
                  </VCard>
                </VCol>
              </VRow>
              <div
                v-else
                class="text-center py-8 text-medium-emphasis"
              >
                <VIcon
                  icon="ri-image-line"
                  size="48"
                  class="mb-2"
                />
                <div>No hay fotos cargadas.</div>
              </div>
            </div>
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>
  </VDialog>
</template>
