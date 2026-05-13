<script setup>
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
  <VContainer class="fill-height py-12">
    <VRow justify="center">
      <VCol
        cols="12"
        md="8"
        lg="6"
      >
        <VCard
          class="pa-8"
          elevation="3"
        >
          <VCardTitle class="text-h4 font-weight-medium mb-2">
            Tu espacio de cliente
          </VCardTitle>
          <VCardText class="text-body-1 text-medium-emphasis">
            <p class="mb-0">
              Bienvenido, {{ displayName }}! Aquí puedes ver tus mascotas
            </p>
          </VCardText>
          <VCardActions class="pt-0">
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
</template>
