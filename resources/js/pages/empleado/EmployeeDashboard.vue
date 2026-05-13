<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const displayName = computed(() => {
  const n = `${auth.user?.first_name || ''} ${auth.user?.last_name || ''}`.trim()

  return n || auth.user?.email || 'Empleado'
})

const logout = () => {
  auth.logout()
  router.push('/login')
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
        <VCard class="pa-8">
          <VCardTitle class="text-h4 mb-2">
            Hola, {{ displayName }}
          </VCardTitle>
          <VCardSubtitle class="text-body-1 mb-6">
            Panel de empleado · rol: {{ auth.user?.role }}
          </VCardSubtitle>
          <VBtn
            color="primary"
            variant="tonal"
            @click="logout"
          >
            Cerrar sesión
          </VBtn>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>
