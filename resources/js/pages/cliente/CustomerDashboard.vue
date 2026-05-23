<script setup>
import ClienteAreaNav from '@/components/cliente/ClienteAreaNav.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const isLoggingOut = ref(false)

const displayName = computed(() => {
  const u = auth.user
  if (!u)
    return ''
  const full = [u.first_name, u.last_name].filter(Boolean).join(' ').trim()

  return full || u.email || ''
})

const logout = async () => {
  isLoggingOut.value = true
  try {
    if (auth.token) {
      await fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${auth.token}`,
        },
      })
    }
  } catch {
    // Ignorar error remoto y cerrar sesion local igualmente.
  } finally {
    auth.logout()
    isLoggingOut.value = false
    router.push('/')
  }
}
</script>

<template>
  <div class="cliente-dashboard-page bg-surface">
    <ClienteAreaNav current="dashboard" />

    <VContainer class="py-10 py-md-12">
      <VRow justify="center">
        <VCol
          cols="12"
          md="8"
          lg="6"
        >
          <VCard
            class="pa-8 welcome-card"
            elevation="3"
            rounded="lg"
          >
            <VCardTitle class="text-h4 font-weight-medium mb-2 text-primary">
              Tu espacio de cliente
            </VCardTitle>
            <VCardText class="text-body-1 text-medium-emphasis">
              <p class="mb-4">
                Bienvenido, {{ displayName }}! Aquí puedes ver tus mascotas
              </p>
              <VBtn
                color="primary"
                variant="flat"
                prepend-icon="ri-user-heart-line"
                class="me-2 mb-2"
                to="/cliente/perfil-mascotas"
              >
                Ir a perfil y mascotas
              </VBtn>
            </VCardText>
            <VCardActions class="pt-0 flex-wrap">
              <VSpacer />
              <VBtn
                color="error"
                variant="tonal"
                prepend-icon="ri-logout-box-r-line"
                :loading="isLoggingOut"
                @click="logout"
              >
                Cerrar sesión
              </VBtn>
            </VCardActions>
          </VCard>
        </VCol>
      </VRow>
    </VContainer>
  </div>
</template>

<style scoped>
.cliente-dashboard-page {
  min-height: 100vh;
}

.welcome-card {
  border: 1px solid rgba(var(--v-theme-primary), 0.08);
}
</style>
