<script setup>
import ClienteAreaNav from '@/components/cliente/ClienteAreaNav.vue'
import CustomerPetGrid from '@/components/cliente/CustomerPetGrid.vue'
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const pets = computed(() => auth.user?.customer?.pets ?? [])

const owner = computed(() => {
  const u = auth.user
  if (!u) return { fullName: '', email: '', phone: '', ci: '' }
  
  return {
    id: u.customer?.id,
    ci: u.customer?.ci ?? 'N/A',
    fullName: [u.first_name, u.last_name].filter(Boolean).join(' ').trim() || u.email,
    email: u.email,
    phone: u.phone,
  }
})

async function refreshProfile() {
  try {
    const res = await apiFetch('auth/me', { method: 'GET' }, auth.token)
    if (res.ok) {
      const userData = await res.json()

      auth.user = userData // Actualiza el store con datos frescos
    }
  } catch (e) {
    console.error(e)
  }
}

const handleSavePet = async payload => {
  const method = payload.id ? 'PUT' : 'POST'
  const url = payload.id ? `pets/${payload.id}` : 'pets'
  
  try {
    const res = await apiFetch(url, {
      method,
      body: JSON.stringify(payload),
    }, auth.token)

    if (res.ok) {
      await refreshProfile()
    }
  } catch (e) {
    console.error(e)
  }
}

const handleDeletePet = async pet => {
  if (!confirm('¿Seguro que deseas eliminar esta mascota?')) return
  try {
    const res = await apiFetch(`pets/${pet.id}`, { method: 'DELETE' }, auth.token)
    if (res.ok) {
      await refreshProfile()
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  if (!auth.user?.customer) {
    refreshProfile()
  }
})
</script>

<template>
  <div class="cliente-perfil-page bg-surface">
    <ClienteAreaNav current="perfil" />

    <VContainer class="py-8 py-md-10">
      <VRow class="mb-6">
        <VCol cols="12">
          <h1 class="text-h4 font-weight-medium text-primary mb-1">
            Perfil y mascotas
          </h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Gestiona tus datos y el registro de tus compañeros peludos.
          </p>
        </VCol>
      </VRow>

      <!-- Dueño -->
      <VRow class="mb-8">
        <VCol cols="12">
          <VCard
            class="owner-card overflow-hidden"
            elevation="2"
            rounded="lg"
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
                      icon="ri-user-smile-line"
                      size="44"
                    />
                  </VAvatar>
                  <div class="text-subtitle-1 font-weight-medium">
                    Cliente
                  </div>
                </div>
              </VCol>
              <VCol
                cols="12"
                md="8"
                class="pa-6 pa-md-8"
              >
                <div class="text-overline text-medium-emphasis mb-1">
                  Datos del dueño
                </div>
                <h2 class="text-h5 font-weight-medium mb-4 text-primary">
                  {{ owner.fullName }}
                </h2>
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
              </VCol>
            </VRow>
          </VCard>
        </VCol>
      </VRow>

      <CustomerPetGrid
        :pets="pets"
        :customer-id="owner.id"
        section-title="Mis mascotas"
        section-subtitle="Aquí puedes ver y gestionar el registro de tus mascotas."
        @save-pet="handleSavePet"
        @delete-pet="handleDeletePet"
      />
    </VContainer>
  </div>
</template>

<style scoped>
.cliente-perfil-page {
  min-height: 100vh;
}

.owner-card {
  border: 1px solid rgba(var(--v-theme-primary), 0.08);
}
</style>
