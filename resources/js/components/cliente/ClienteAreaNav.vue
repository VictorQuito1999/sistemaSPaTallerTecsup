<script setup>
import logo from '@images/logo.svg?raw'

defineProps({
  current: {
    type: String,
    default: 'dashboard',
    validator: v => ['dashboard', 'perfil', 'reservar'].includes(v),
  },
})
</script>

<template>
  <VAppBar
    flat
    density="comfortable"
    class="cliente-nav border-b border-opacity-100"
    color="surface"
  >
    <VContainer class="d-flex align-center py-2 flex-wrap ga-3">
      <RouterLink
        to="/cliente/dashboard"
        class="d-flex align-center text-decoration-none text-primary"
      >
        <span
          class="d-inline-flex me-2"
          v-html="logo"
        />
        <span class="font-weight-semibold text-body-1">Pet Spa · Cliente</span>
      </RouterLink>

      <VSpacer />

      <div class="d-flex flex-wrap ga-1">
        <VBtn
          :variant="current === 'dashboard' ? 'flat' : 'text'"
          :color="current === 'dashboard' ? 'primary' : 'default'"
          to="/cliente/dashboard"
          prepend-icon="ri-dashboard-line"
        >
          Inicio
        </VBtn>
        <VBtn
          :variant="current === 'perfil' ? 'flat' : 'text'"
          :color="current === 'perfil' ? 'primary' : 'default'"
          to="/cliente/perfil-mascotas"
          prepend-icon="ri-user-heart-line"
        >
          Perfil y mascotas
        </VBtn>
        <VBtn
          :variant="current === 'reservar' ? 'flat' : 'text'"
          :color="current === 'reservar' ? 'primary' : 'default'"
          to="/cliente/reservar"
          prepend-icon="ri-calendar-event-line"
        >
          Reservar Cita
        </VBtn>
      </div>
    </VContainer>
  </VAppBar>
</template>

<style scoped>
.cliente-nav {
  border-color: rgba(var(--v-theme-primary), 0.12) !important;
}
</style>
