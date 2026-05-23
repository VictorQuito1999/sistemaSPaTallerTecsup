<script setup>
import CustomerPetGrid from '@/components/cliente/CustomerPetGrid.vue'
import { getMockCustomerDetail } from '@/data/mockAdminCustomers'

const route = useRoute()
const id = computed(() => Number(route.params.id))

const detail = computed(() => getMockCustomerDetail(id.value))

const owner = computed(() => {
  const d = detail.value
  if (!d) {
    return null
  }
  const u = d.customer.user
  const full = [u.first_name, u.last_name].filter(Boolean).join(' ').trim()

  return {
    id: d.customer.id,
    ci: d.customer.ci,
    fullName: full || u.email,
    email: u.email,
    phone: u.phone,
  }
})

const pets = ref([])

watch(
  detail,
  d => {
    pets.value = d ? [...d.pets] : []
  },
  { immediate: true },
)
</script>

<template>
  <VContainer class="py-8">
    <VBtn
      variant="text"
      prepend-icon="ri-arrow-left-line"
      class="mb-4"
      to="/empleado/clientes"
    >
      Volver al listado
    </VBtn>

    <template v-if="owner">
      <h1 class="text-h5 mb-4 text-primary">
        Cliente · {{ owner.fullName }}
      </h1>
      <VCard
        rounded="lg"
        class="pa-6 mb-6"
      >
        <VRow dense>
          <VCol
            cols="12"
            sm="4"
          >
            <div class="text-caption text-medium-emphasis">
              CI
            </div>
            <div class="text-body-1">
              {{ owner.ci }}
            </div>
          </VCol>
          <VCol
            cols="12"
            sm="4"
          >
            <div class="text-caption text-medium-emphasis">
              Teléfono
            </div>
            <div class="text-body-1">
              {{ owner.phone }}
            </div>
          </VCol>
          <VCol
            cols="12"
            sm="4"
          >
            <div class="text-caption text-medium-emphasis">
              Email
            </div>
            <div class="text-body-1">
              {{ owner.email }}
            </div>
          </VCol>
        </VRow>
      </VCard>

      <CustomerPetGrid
        :pets="pets"
        :customer-id="owner.id"
        section-title="Mascotas"
        section-subtitle="Solo lectura en recepción (sin acciones de edición)."
        :allow-add="false"
        :allow-edit="false"
        :allow-delete="false"
      />
    </template>

    <VAlert
      v-else
      type="warning"
      variant="tonal"
    >
      Cliente no encontrado en datos de demostración.
    </VAlert>
  </VContainer>
</template>
