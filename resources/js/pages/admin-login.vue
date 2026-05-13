<script setup>
import { useTheme } from 'vuetify'
import { useAuthStore } from '@/stores/auth'
import logo from '@images/logo.svg?raw'
import authV1MaskDark from '@images/pages/auth-v1-mask-dark.png'
import authV1MaskLight from '@images/pages/auth-v1-mask-light.png'
import authV1Tree2 from '@images/pages/auth-v1-tree-2.png'
import authV1Tree from '@images/pages/auth-v1-tree.png'

const router = useRouter()
const auth = useAuthStore()
const errorMessage = ref('')
const isSubmitting = ref(false)
const step = ref(1) // 1 credenciales, 2 desafío 2FA
const mode = ref('') // setup | challenge

const form = ref({
  email: '',
  password: '',
})

const pendingTwoFactorToken = ref('')
const otp = ref('')

const vuetifyTheme = useTheme()
const authThemeMask = computed(() => vuetifyTheme.global.name.value === 'light' ? authV1MaskLight : authV1MaskDark)
const isPasswordVisible = ref(false)

const qrSvgInline = ref('')

const credentialSubmit = async () => {
  errorMessage.value = ''
  isSubmitting.value = true
  qrSvgInline.value = ''

  try {
    const response = await fetch('/api/auth/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(form.value),
    })

    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.message || 'No se pudo iniciar sesión.')
    }

    if (payload.must_change_password && payload.user) {
      auth.setUser(payload.user)
      
      return router.push({ path: '/change-password', query: { email: payload.user.email } })
    }

    if (payload.requires_google2fa_setup) {
      mode.value = 'setup'
      step.value = 2
      pendingTwoFactorToken.value = payload.pending_two_factor_token
      qrSvgInline.value = payload.qr_svg_inline || ''
      
      return
    }

    if (payload.requires_google2fa_challenge) {
      mode.value = 'challenge'
      step.value = 2
      pendingTwoFactorToken.value = payload.pending_two_factor_token
    }
  }
  catch (error) {
    errorMessage.value = error.message
  }
  finally {
    isSubmitting.value = false
  }
}

const completeTwoFactor = async () => {
  errorMessage.value = ''
  isSubmitting.value = true

  const path = mode.value === 'setup'
    ? '/api/auth/admin/google2fa/setup/confirm'
    : '/api/auth/admin/google2fa/challenge'

  try {
    const response = await fetch(path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        pending_two_factor_token: pendingTwoFactorToken.value,
        otp: otp.value,
      }),
    })

    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.message || 'Código incorrecto.')
    }

    auth.setAdminSession(payload.user, payload.token)
    
    return router.push('/admin')
  }
  catch (error) {
    errorMessage.value = error.message
  }
  finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <VCard
      class="auth-card pa-4 pt-7"
      max-width="520"
    >
      <VCardItem class="justify-center">
        <RouterLink
          to="/"
          class="d-flex align-center gap-3"
        >
          <div
            class="d-flex"
            v-html="logo"
          />
          <h2 class="font-weight-medium text-2xl text-uppercase">
            PetSpa V1
          </h2>
        </RouterLink>
      </VCardItem>

      <VCardText
        v-if="step === 1"
        class="pt-2"
      >
        <h4 class="text-h4 mb-1">
          Acceso administrativo
        </h4>
        <p class="mb-4">
          Credenciales (Google 2FA en el siguiente paso).
        </p>

        <VForm @submit.prevent="credentialSubmit">
          <VTextField
            v-model="form.email"
            label="Correo"
            type="email"
            class="mb-4"
          />
          <VTextField
            v-model="form.password"
            label="Contraseña"
            :type="isPasswordVisible ? 'text' : 'password'"
            :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
            class="mb-4"
            @click:append-inner="isPasswordVisible = !isPasswordVisible"
          />
          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ errorMessage }}
          </VAlert>
          <VBtn
            block
            type="submit"
            color="primary"
            :loading="isSubmitting"
          >
            Continuar
          </VBtn>
        </VForm>
      </VCardText>

      <VCardText
        v-else
        class="pt-2"
      >
        <h4 class="text-h4 mb-1">
          {{ mode === 'setup' ? 'Configura Google Authenticator' : 'Código de verificación' }}
        </h4>
        <p class="mb-4">
          {{ mode === 'setup'
            ? 'Escanea el QR y confirma con un código de 6 dígitos.'
            : 'Introduce el código de 6 dígitos desde tu app Authenticator.'
          }}
        </p>

        <div
          v-if="mode === 'setup' && qrSvgInline"
          class="mb-4 d-flex justify-center"
          v-html="qrSvgInline"
        />

        <VForm @submit.prevent="completeTwoFactor">
          <VTextField
            v-model="otp"
            label="Código (6 dígitos)"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="one-time-code"
            class="mb-4"
          />
          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ errorMessage }}
          </VAlert>
          <VBtn
            block
            color="primary"
            type="submit"
            :loading="isSubmitting"
          >
            Verificar y entrar
          </VBtn>
          <VBtn
            block
            variant="text"
            class="mt-2"
            @click="step = 1"
          >
            Volver
          </VBtn>
        </VForm>
      </VCardText>
    </VCard>

    <VImg
      class="auth-footer-start-tree d-none d-md-block"
      :src="authV1Tree"
      :width="250"
    />
    <VImg
      :src="authV1Tree2"
      class="auth-footer-end-tree d-none d-md-block"
      :width="350"
    />
    <VImg
      class="auth-footer-mask d-none d-md-block"
      :src="authThemeMask"
    />
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
