<script setup>
import { useTheme } from 'vuetify'
import {
  isStrongPassword,
  passwordStrengthLabel,
  passwordStrengthScore,
  strongPasswordViolations,
} from '@/utils/passwordStrength'
import logo from '@images/logo.svg?raw'
import authV1MaskDark from '@images/pages/auth-v1-mask-dark.png'
import authV1MaskLight from '@images/pages/auth-v1-mask-light.png'
import authV1Tree2 from '@images/pages/auth-v1-tree-2.png'
import authV1Tree from '@images/pages/auth-v1-tree.png'

const router = useRouter()
const isSubmitting = ref(false)
const errorMessage = ref('')

const form = ref({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  password: '',
})

const vuetifyTheme = useTheme()
const authThemeMask = computed(() => vuetifyTheme.global.name.value === 'light' ? authV1MaskLight : authV1MaskDark)
const isPasswordVisible = ref(false)

const score = computed(() => passwordStrengthScore(form.value.password))
const strengthLabel = computed(() => passwordStrengthLabel(score.value))
const violations = computed(() => strongPasswordViolations(form.value.password))

const strengthColor = computed(() => {
  if (score.value <= 2)
    return 'error'
  if (score.value === 3)
    return 'warning'
  
  return 'success'
})

const canSubmit = computed(() => {
  const f = form.value
  if (!f.firstName?.trim() || !f.lastName?.trim() || !f.email?.trim() || !f.phone?.trim() || !f.password) {
    return false
  }

  return isStrongPassword(f.password)
})

const submit = async () => {
  errorMessage.value = ''
  if (!canSubmit.value) {
    errorMessage.value = 'Completa todos los campos y usa una contraseña segura (mínimo 8 caracteres, mayúscula, minúscula, número y símbolo).'

    return
  }

  isSubmitting.value = true

  try {
    const registerPayload = {
      email: form.value.email,
      phone: form.value.phone,
      password: form.value.password,
    }

    registerPayload['first_name'] = form.value.firstName
    registerPayload['last_name'] = form.value.lastName

    const response = await fetch('/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(registerPayload),
    })

    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.message || 'No se pudo registrar el cliente.')
    }

    return router.push({ path: '/verify-email', query: { email: form.value.email } })
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

      <VCardText class="pt-2">
        <h4 class="text-h4 mb-1">
          Registro de cliente
        </h4>
        <p class="mb-0">
          Crea tu cuenta para reservar servicios en el spa.
        </p>
      </VCardText>

      <VCardText>
        <VForm @submit.prevent="submit">
          <VRow>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.firstName"
                label="Nombre"
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.lastName"
                label="Apellido"
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="form.email"
                label="Correo"
                type="email"
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="form.phone"
                label="Teléfono"
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="form.password"
                label="Contraseña"
                hint="Mínimo 8 caracteres, mayúscula, minúscula, número y símbolo"
                persistent-hint
                :type="isPasswordVisible ? 'text' : 'password'"
                :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
              />
            </VCol>

            <VCol cols="12">
              <div class="text-caption text-medium-emphasis d-flex justify-space-between align-center mb-1">
                <span>Fuerza: {{ strengthLabel }}</span>
                <span>{{ score }}/5</span>
              </div>
              <VProgressLinear
                :model-value="(score / 5) * 100"
                :color="strengthColor"
                height="10"
                rounded
              />
            </VCol>

            <VCol cols="12">
              <VList
                v-if="violations.length"
                density="compact"
                class="bg-transparent py-0"
              >
                <VListSubheader class="px-0 text-caption">
                  Requisitos
                </VListSubheader>
                <VListItem
                  v-for="(item, idx) in violations"
                  :key="idx"
                  class="px-0 min-h-0"
                  density="compact"
                >
                  <template #prepend>
                    <VIcon
                      icon="ri-close-circle-line"
                      color="error"
                      size="18"
                    />
                  </template>
                  <VListItemTitle class="text-body-2">
                    {{ item }}
                  </VListItemTitle>
                </VListItem>
              </VList>
              <div
                v-else-if="form.password"
                class="text-success text-caption d-flex align-center ga-1"
              >
                <VIcon
                  icon="ri-checkbox-circle-line"
                  size="18"
                />
                Cumple la política de contraseña
              </div>
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
                :disabled="!canSubmit"
              >
                Registrar cliente
              </VBtn>
            </VCol>

            <VCol
              cols="12"
              class="text-center text-base"
            >
              <span>¿Ya tienes cuenta?</span>
              <RouterLink
                class="text-primary ms-2"
                to="/cliente/login"
              >
                Inicia sesión
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
