<script setup>
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const email = ref(route.query.email || auth.user?.email || '')
const otpCode = ref('')
const isSubmitting = ref(false)
const isResending = ref(false)
const feedback = ref('')
const feedbackType = ref('info')

const otpInputRules = [
  v => /^\d{6}$/.test((v || '').trim()) || 'Ingresa un codigo de 6 digitos.',
]

const canVerify = computed(() => /^\d{6}$/.test(otpCode.value.trim()))

const verify = async () => {
  if (!canVerify.value) {
    feedback.value = 'El codigo OTP debe tener exactamente 6 digitos.'
    feedbackType.value = 'error'

    return
  }

  isSubmitting.value = true
  feedback.value = ''

  try {
    const response = await fetch('/api/auth/verify-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email: email.value, ['otp_code']: otpCode.value }),
    })

    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.message || 'No se pudo verificar el código.')
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

const resendCode = async () => {
  isResending.value = true
  feedback.value = ''

  try {
    const response = await fetch('/api/auth/resend-otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email: email.value }),
    })

    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.message || 'No se pudo reenviar el código.')
    }

    feedback.value = payload.message
    feedbackType.value = 'success'
  }
  catch (error) {
    feedback.value = error.message
    feedbackType.value = 'error'
  }
  finally {
    isResending.value = false
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
            Verificar correo
          </VCardTitle>
          <VCardSubtitle class="mb-4">
            Ingresa el código OTP de 6 dígitos enviado a tu correo.
          </VCardSubtitle>

          <VCardText>
            <VForm @submit.prevent="verify">
              <VTextField
                v-model="email"
                label="Correo"
                class="mb-4"
                type="email"
              />
              <VTextField
                v-model="otpCode"
                label="Código OTP"
                maxlength="6"
                minlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                :rules="otpInputRules"
              />

              <VAlert
                v-if="feedback"
                :type="feedbackType"
                variant="tonal"
                class="my-4"
              >
                {{ feedback }}
              </VAlert>

              <div class="d-flex ga-3 mt-4">
                <VBtn
                  type="submit"
                  :loading="isSubmitting"
                  :disabled="!canVerify"
                >
                  Verificar
                </VBtn>
                <VBtn
                  variant="tonal"
                  :loading="isResending"
                  @click="resendCode"
                >
                  Reenviar código
                </VBtn>
              </div>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>
