<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'
import CustomerPetGrid from '@/components/cliente/CustomerPetGrid.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const customerId = computed(() => Number(route.params.id))
const customerData = ref(null)
const loading = ref(false)

async function fetchCustomerDetail() {
  loading.value = true
  try {
    const res = await apiFetch(`customers/${customerId.value}`, { method: 'GET' }, auth.token)
    if (res.ok) {
      customerData.value = await res.json()
    }
  } catch (e) {
    console.error('Error fetching customer detail:', e)
  } finally {
    loading.value = false
  }
}

const owner = computed(() => {
  const d = customerData.value
  if (!d) return null
  
  const u = d.user
  
  return {
    id: d.id,
    ci: d.ci,
    fullName: `${u.first_name} ${u.last_name}`,
    email: u.email,
    phone: u.phone,
    status: d.status === 1 ? 'active' : 'blocked',
  }
})

const pets = computed(() => customerData.value?.pets || [])

const handleEditCustomer = () => {
  console.log('[handleEditCustomer]', customerId.value)
}

const handleBlockCustomer = () => {
  console.log('[handleBlockCustomer]', customerId.value)
}

const handleSavePet = async payload => {
  try {
    const method = payload.id ? 'PUT' : 'POST'
    const url = payload.id ? `pets/${payload.id}` : 'pets'
    
    const res = await apiFetch(url, {
      method,
      body: JSON.stringify(payload),
    }, auth.token)

    if (res.ok) {
      fetchCustomerDetail()
    } else {
      const data = await res.json()

      alert(data.message || 'Error al guardar mascota')
    }
  } catch (e) {
    console.error(e)
  }
}


const handleDeletePet = async pet => {
  if (!confirm('¿Seguro que deseas eliminar esta mascota?')) return
  try {
    const res = await apiFetch(`pets/${pet.id}`, { method: 'DELETE' }, auth.token)
    if (res.ok) fetchCustomerDetail()
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchCustomerDetail)
</script>


<template>
  <div>
    <VBtn
      variant="text"
      prepend-icon="ri-arrow-left-line"
      class="mb-4"
      @click="router.push({ name: 'admin-customers-index' })"
    >
      Volver al listado
    </VBtn>

    <template v-if="owner">
      <VRow class="mb-4">
        <VCol cols="12">
          <h2 class="text-h5 mb-1">
            Perfil del cliente
          </h2>
          <p class="text-body-2 text-medium-emphasis mb-0">
            ID #{{ customerId }} · gestión administrativa
          </p>
        </VCol>
      </VRow>

      <VRow class="mb-6">
        <VCol cols="12">
          <VCard
            rounded="lg"
            elevation="2"
            class="overflow-hidden"
          >
            <VRow no-gutters>
              <VCol
                cols="12"
                md="4"
                class="bg-primary d-flex align-center justify-center pa-8"
              >
                <div class="text-center text-white">
                  <VAvatar
                    color="white"
                    size="88"
                    class="text-primary mb-3"
                  >
                    <VIcon
                      icon="ri-user-settings-line"
                      size="44"
                    />
                  </VAvatar>
                  <div class="text-subtitle-2">
                    Cliente registrado
                  </div>
                </div>
              </VCol>
              <VCol
                cols="12"
                md="8"
                class="pa-6"
              >
                <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-4">
                  <div>
                    <div class="text-overline text-medium-emphasis">
                      Datos del dueño
                    </div>
                    <h3 class="text-h5 text-primary">
                      {{ owner.fullName }}
                    </h3>
                  </div>
                  <div class="d-flex flex-wrap ga-2">
                    <VChip
                      :color="owner.status === 'blocked' ? 'error' : 'success'"
                      variant="tonal"
                      size="small"
                    >
                      {{ owner.status === 'blocked' ? 'Bloqueado' : 'Activo' }}
                    </VChip>
                  </div>
                </div>
                <VRow dense>
                  <VCol
                    cols="12"
                    sm="6"
                  >
                    <div class="text-caption text-medium-emphasis">
                      CI
                    </div>
                    <div class="text-body-1 font-weight-medium">
                      {{ owner.ci }}
                    </div>
                  </VCol>
                  <VCol
                    cols="12"
                    sm="6"
                  >
                    <div class="text-caption text-medium-emphasis">
                      Teléfono
                    </div>
                    <div class="text-body-1 font-weight-medium">
                      {{ owner.phone }}
                    </div>
                  </VCol>
                  <VCol cols="12">
                    <div class="text-caption text-medium-emphasis">
                      Email
                    </div>
                    <div class="text-body-1 font-weight-medium">
                      {{ owner.email }}
                    </div>
                  </VCol>
                </VRow>
                <div class="d-flex flex-wrap ga-2 mt-4">
                  <VBtn
                    color="primary"
                    variant="tonal"
                    prepend-icon="ri-edit-line"
                    @click="handleEditCustomer"
                  >
                    Editar datos del cliente
                  </VBtn>
                  <VBtn
                    color="error"
                    variant="outlined"
                    prepend-icon="ri-forbid-line"
                    @click="handleBlockCustomer"
                  >
                    Bloquear cliente
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VCard>
        </VCol>
      </VRow>

      <CustomerPetGrid
        :pets="pets"
        :customer-id="owner.id"
        section-title="Mascotas del cliente"
        section-subtitle="Misma grilla que usa el perfil del cliente; aquí con permisos de administración."
        @save-pet="handleSavePet"
        @delete-pet="handleDeletePet"
      />
    </template>

    <VAlert
      v-else
      type="warning"
      variant="tonal"
      rounded="lg"
    >
      No hay datos de demostración para el cliente #{{ customerId }}.
      <VBtn
        class="mt-2"
        variant="tonal"
        @click="router.push({ name: 'admin-customers-index' })"
      >
        Volver al listado
      </VBtn>
    </VAlert>
  </div>
</template>
