<script setup>
import { useTheme } from 'vuetify'
import { useAuthStore } from '@/stores/auth'
import logo from '@images/logo.svg?raw'
import authV1MaskDark from '@images/pages/auth-v1-mask-dark.png'
import authV1MaskLight from '@images/pages/auth-v1-mask-light.png'
import authV1Tree2 from '@images/pages/auth-v1-tree-2.png'
import authV1Tree from '@images/pages/auth-v1-tree.png'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const errorMessage = ref('')
const isSubmitting = ref(false)

const form = ref({
  email: route.query.email || '',
  password: '',
})

const vuetifyTheme = useTheme()
const authThemeMask = computed(() => vuetifyTheme.global.name.value === 'light' ? authV1MaskLight : authV1MaskDark)
const isPasswordVisible = ref(false)

const oauthError = computed(() => {
  const raw = route.query.oauth_error
  if (raw == null || raw === '')
    return ''
  try {
    return decodeURIComponent(String(raw))
  }
  catch {
    return String(raw)
  }
})

onMounted(() => {
  if (oauthError.value) {
    errorMessage.value = oauthError.value
  }
})

const submit = async () => {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(form.value),
    })

    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.message || 'No se pudo iniciar sesión.')
    }

    if (payload.must_change_password) {
      auth.setUser(payload.user)

      return router.push({ path: '/change-password', query: { email: payload.user.email } })
    }

    if (payload.requires_verification) {
      auth.setUser(payload.user)

      return router.push({ path: '/verify-email', query: { email: payload.user.email } })
    }

    if (payload.token) {
      auth.setBearerSession(payload.user, payload.token)
    }
    else {
      auth.setUser(payload.user)
    }

    return router.push('/cliente/dashboard')
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
          Iniciar sesión
        </h4>
        <p class="mb-0">
          Accede a tu cuenta de cliente.
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
                placeholder="········"
                :type="isPasswordVisible ? 'text' : 'password'"
                autocomplete="password"
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
                :loading="isSubmitting"
              >
                Entrar
              </VBtn>
            </VCol>

            <VCol cols="12">
              <div class="d-flex align-center ga-3 my-1">
                <VDivider />
                <span class="text-caption text-medium-emphasis flex-shrink-0">o continúa con</span>
                <VDivider />
              </div>
            </VCol>

            <VCol cols="12">
              <a
                href="/auth/google/redirect"
                class="text-decoration-none d-block"
              >
                <VBtn
                  block
                  variant="outlined"
                  color="secondary"
                  class="google-sign-in-btn border-opacity-100"
                  elevation="0"
                >
                  <span class="d-inline-flex align-center ga-2">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="20"
                      height="20"
                      viewBox="0 0 48 48"
                      aria-hidden="true"
                    >
                      <path
                        fill="#FFC107"
                        d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"
                      />
                      <path
                        fill="#FF3D00"
                        d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"
                      />
                      <path
                        fill="#4CAF50"
                        d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.222 0-9.673-3.341-11.322-8h-6.787C8.848 39.217 15.842 44 24 44z"
                      />
                      <path
                        fill="#1976D2"
                        d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"
                      />
                    </svg>
                    Continuar con Google
                  </span>
                </VBtn>
              </a>
            </VCol>

            <VCol
              cols="12"
              class="text-center text-base"
            >
              <span>¿No tienes cuenta?</span>
              <RouterLink
                class="text-primary ms-2"
                to="/register"
              >
                Regístrate
              </RouterLink>
            </VCol>

            <VCol
              cols="12"
              class="text-center text-base"
            >
              <RouterLink
                class="text-primary"
                to="/login"
              >
                ¿Personal del spa? Entrar aquí
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
