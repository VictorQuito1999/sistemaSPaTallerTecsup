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

const form = ref({
  email: '',
  password: '',
})

const vuetifyTheme = useTheme()
const authThemeMask = computed(() => vuetifyTheme.global.name.value === 'light' ? authV1MaskLight : authV1MaskDark)
const isPasswordVisible = ref(false)

const submit = async () => {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    const response = await fetch('/api/auth/employee/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(form.value),
    })

    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.message || 'No se pudo iniciar sesión.')
    }

    auth.setBearerSession(payload.user, payload.token)

    return router.push('/empleado/dashboard')
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
      max-width="448"
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

      <VCardText class="pt-2">
        <h4 class="text-h4 mb-1">
          Acceso personal
        </h4>
        <p class="mb-0">
          Peluquería y recepción (sin 2FA).
        </p>
      </VCardText>

      <VCardText>
        <VForm @submit.prevent="submit">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.email"
                label="Correo"
                type="email"
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="form.password"
                label="Contraseña"
                autocomplete="current-password"
                :type="isPasswordVisible ? 'text' : 'password'"
                :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
              />
            </VCol>

            <VCol
              v-if="errorMessage"
              cols="12"
            >
              <VAlert
                type="error"
                variant="tonal"
              >
                {{ errorMessage }}
              </VAlert>
            </VCol>

            <VCol cols="12">
              <VBtn
                block
                type="submit"
                color="primary"
                :loading="isSubmitting"
              >
                Entrar
              </VBtn>
            </VCol>

            <VCol
              cols="12"
              class="text-center text-base"
            >
              <span>¿Eres cliente?</span>
              <RouterLink
                class="text-primary ms-2"
                to="/cliente/login"
              >
                Inicia sesión aquí
              </RouterLink>
            </VCol>
          </VRow>
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
