<script setup>
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const form = ref({
  email: route.query.email || auth.user?.email || '',
  password: '',
  password_confirmation: '',
})

const isSubmitting = ref(false)
const feedback = ref('')
const feedbackType = ref('info')

const submit = async () => {
  isSubmitting.value = true
  feedback.value = ''

  try {
    const response = await fetch('/api/auth/change-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(form.value),
    })

    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.message || 'No se pudo actualizar la contraseña.')
    }

    feedback.value = payload.message
    feedbackType.value = 'success'

    if (payload.token && payload.user?.role === 'customer') {
      auth.setBearerSession(payload.user, payload.token)
      router.push('/cliente/dashboard')
    }
    else {
      auth.setUser(payload.user)
      router.push(payload.user.role === 'admin' ? '/admin' : '/')
    }
  }
  catch (error) {
    feedback.value = error.message
    feedbackType.value = 'error'
  }
  finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <VContainer class="fill-height d-flex align-center justify-center">
    <VRow justify="center">
      <VCol
        cols="12"
        sm="8"
        md="6"
        lg="4"
      >
        <VCard class="pa-6">
          <VCardTitle class="text-h5 mb-1">
            Cambiar contraseña
          </VCardTitle>
          <VCardSubtitle class="mb-4">
            Debes establecer una nueva contraseña para continuar.
          </VCardSubtitle>

          <VCardText>
            <VForm @submit.prevent="submit">
              <VTextField
                v-model="form.email"
                label="Correo"
                type="email"
                class="mb-4"
              />
              <VTextField
                v-model="form.password"
                label="Nueva contraseña"
                type="password"
                class="mb-4"
              />
              <VTextField
                v-model="form.password_confirmation"
                label="Confirmar contraseña"
                type="password"
              />

              <VAlert
                v-if="feedback"
                :type="feedbackType"
                variant="tonal"
                class="my-4"
              >
                {{ feedback }}
              </VAlert>

              <VBtn
                type="submit"
                :loading="isSubmitting"
              >
                Actualizar contraseña
              </VBtn>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>
