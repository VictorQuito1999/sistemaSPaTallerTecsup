<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'
import {
  isStrongPassword,
  passwordStrengthLabel,
  passwordStrengthScore,
  strongPasswordViolations,
} from '@/utils/passwordStrength'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const showPassword = ref(false)
const showPasswordConfirm = ref(false)

const id = computed(() => route.query.id || '')
const expires = computed(() => route.query.expires ?? '')
const signature = computed(() => route.query.signature ?? '')

const password = ref('')
const passwordConfirmation = ref('')

const isSubmitting = ref(false)
const message = ref('')
const messageType = ref('info')

const score = computed(() => passwordStrengthScore(password.value))
const strengthLabel = computed(() => passwordStrengthLabel(score.value))
const violations = computed(() => strongPasswordViolations(password.value))
const policyOk = computed(() => isStrongPassword(password.value) && password.value === passwordConfirmation.value)

const strengthColor = computed(() => {
  if (score.value <= 2)
    return 'error'
  if (score.value === 3)
    return 'warning'
  
  return 'success'
})

const canSubmit = computed(() => {
  if (!password.value || !passwordConfirmation.value) {
    return false
  }
  
  return policyOk.value
})

const submit = async () => {
  message.value = ''
  if (!canSubmit.value) {
    message.value = 'Cumple la política de contraseña y confirma que ambos campos coinciden.'
    messageType.value = 'error'

    return
  }

  isSubmitting.value = true

  try {
    const res = await apiFetch('auth/employee/complete-activation', {
      method: 'POST',
      body: JSON.stringify({
        id: Number(id.value),
        expires: expires.value,
        signature: signature.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
      }),
    })

    const data = await res.json()
    if (!res.ok) {
      throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'No se pudo activar.')
    }
    message.value = data.message || 'Cuenta activada.'
    messageType.value = 'success'
    setTimeout(() => router.push('/login'), 2500)
  }
  catch (e) {
    message.value = e.message
    messageType.value = 'error'
  }
  finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  if (!id.value || expires.value === '' || !signature.value) {
    message.value = 'Enlace incompleto. Abre el enlace enviado por correo desde Pet Spa.'
    messageType.value = 'warning'
  }
})
</script>

<template>
  <VContainer class="fill-height d-flex align-center justify-center py-12">
    <VRow
      justify="center"
      class="w-100"
    >
      <VCol
        cols="12"
        sm="10"
        md="6"
        lg="5"
      >
        <VCard
          class="pa-6"
          elevation="4"
        >
          <VCardTitle class="text-h5 pb-2">
            Activar cuenta
          </VCardTitle>
          <VCardSubtitle class="pb-4 text-medium-emphasis">
            Configura tu contraseña para completar tu registro en Pet Spa.
          </VCardSubtitle>

          <VAlert
            v-if="message"
            :type="messageType"
            variant="tonal"
            class="mb-4"
          >
            {{ message }}
          </VAlert>

          <VForm
            v-if="id && expires !== '' && signature"
            @submit.prevent="submit"
          >
            <VTextField
              v-model="password"
              label="Nueva contraseña"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              class="mb-2"
              hint="Mínimo 8 caracteres, mayúscula, minúscula, número y símbolo"
              persistent-hint
              :append-inner-icon="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"
              @click:append-inner="showPassword = !showPassword"
            />
            <VTextField
              v-model="passwordConfirmation"
              label="Confirmar contraseña"
              :type="showPasswordConfirm ? 'text' : 'password'"
              autocomplete="new-password"
              class="mb-3"
              :append-inner-icon="showPasswordConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"
              @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
            />

            <div class="mb-2">
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
            </div>

            <VList
              v-if="violations.length"
              density="compact"
              class="bg-transparent py-0 mb-4"
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
              v-else-if="password"
              class="text-success text-caption mb-4 d-flex align-center ga-1"
            >
              <VIcon
                icon="ri-checkbox-circle-line"
                size="18"
              />
              Cumple la política de contraseña
            </div>

            <VAlert
              v-if="password && passwordConfirmation && password !== passwordConfirmation"
              type="warning"
              variant="tonal"
              density="compact"
              class="mb-4"
            >
              Las contraseñas no coinciden.
            </VAlert>

            <VBtn
              type="submit"
              color="primary"
              block
              size="large"
              :disabled="!canSubmit"
              :loading="isSubmitting"
            >
              Activar cuenta
            </VBtn>
          </VForm>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>
