<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const isReceptionist = computed(() => auth.user?.role === 'receptionist')
const isGroomer = computed(() => auth.user?.role === 'groomer')

const displayName = computed(() => {
  const n = `${auth.user?.first_name || ''} ${auth.user?.last_name || ''}`.trim()

  return n || auth.user?.email || 'Empleado'
})

const logout = () => {
  auth.logout()
  router.push('/login')
}

const myAppointments = ref([])
const loadingAppointments = ref(false)

const fetchMyAppointments = async () => {
  if (!isGroomer.value) return
  loadingAppointments.value = true
  try {
    const res = await apiFetch('employee/appointments', { method: 'GET' }, auth.token)
    if (res.ok) {
      const data = await res.json()


      // Tomar las primeras 3 para los enlaces rápidos
      myAppointments.value = data.slice(0, 3)
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingAppointments.value = false
  }
}

onMounted(fetchMyAppointments)

const groomerPetSamples = computed(() => {
  return myAppointments.value.map(a => ({
    id: a.id,
    label: `${a.pet_name} — ${a.service_name}`,
  }))
})
</script>

<template>
  <VContainer class="fill-height py-12">
    <VRow justify="center">
      <VCol
        cols="12"
        md="10"
        lg="8"
      >
        <VCard class="pa-8">
          <VCardTitle class="text-h4 mb-2">
            Hola, {{ displayName }}
          </VCardTitle>
          <VCardSubtitle class="text-body-1 mb-6">
            Panel de empleado · rol: {{ auth.user?.role }}
          </VCardSubtitle>

          <VCard
            v-if="isReceptionist"
            variant="outlined"
            rounded="lg"
            class="pa-4 mb-6"
          >
            <div class="text-subtitle-2 mb-3 d-flex align-center ga-2">
              <VIcon
                icon="ri-calendar-todo-line"
                color="primary"
              />
              Recepción (demo)
            </div>
            <p class="text-body-2 text-medium-emphasis mb-3">
              Agenda global, clientes y cobro con datos mock.
            </p>
            <div class="d-flex flex-wrap ga-2">
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                to="/empleado/agenda-global"
              >
                Agenda global
              </VBtn>
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                to="/empleado/clientes"
              >
                Clientes
              </VBtn>
              <VBtn
                size="small"
                variant="outlined"
                color="secondary"
                to="/empleado/checkout/501"
              >
                Cobro demo (501)
              </VBtn>
            </div>
          </VCard>

          <VCard
            v-if="isGroomer"
            variant="outlined"
            rounded="lg"
            class="pa-4 mb-6"
          >
            <div class="text-subtitle-2 mb-3 d-flex align-center ga-2">
              <VIcon
                icon="ri-scissors-cut-line"
                color="primary"
              />
              Groomer (demo)
            </div>
            <p class="text-body-2 text-medium-emphasis mb-3">
              Citas del día, consola de trabajo y ficha mascota (solo lectura).
            </p>
            <div class="d-flex flex-wrap ga-2 mb-3">
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                to="/groomer/today"
              >
                Mis citas del día
              </VBtn>
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                :to="{ name: 'groomer-service-console', params: { id: '101' } }"
              >
                Consola de trabajo
              </VBtn>
            </div>
            <p class="text-caption text-medium-emphasis mb-2">
              Ficha mascota antes de la cita:
            </p>
            <div class="d-flex flex-wrap ga-2">
              <VBtn
                v-for="s in groomerPetSamples"
                :key="s.id"
                size="small"
                variant="outlined"
                color="primary"
                :to="`/groomer/appointments/${s.id}/pet`"
              >
                {{ s.label }}
              </VBtn>
            </div>
          </VCard>

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
