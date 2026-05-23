<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()

const isReceptionist = computed(() => auth.user?.role === 'receptionist')
const isGroomer = computed(() => auth.user?.role === 'groomer')

const activeClass = path => {
  if (path === '/groomer/service') {
    return route.path.startsWith('/groomer/service/')
  }

  return route.path === path || route.path.startsWith(`${path}/`)
}

const linkClass = path => ({
  'text-primary font-weight-medium': activeClass(path),
  'text-medium-emphasis': !activeClass(path),
})
</script>

<template>
  <VAppBar
    flat
    density="comfortable"
    color="surface"
    class="staff-nav border-b"
  >
    <VContainer class="d-flex flex-wrap align-center py-2 ga-2">
      <RouterLink
        to="/empleado/dashboard"
        class="text-decoration-none text-primary d-flex align-center me-4"
      >
        <VIcon
          icon="ri-store-2-line"
          class="me-2"
        />
        <span class="font-weight-semibold">Pet Spa · Staff</span>
      </RouterLink>

      <div class="d-flex flex-wrap align-center ga-1">
        <VBtn
          to="/empleado/dashboard"
          variant="text"
          size="small"
          :class="linkClass('/empleado/dashboard')"
          prepend-icon="ri-dashboard-line"
        >
          Panel
        </VBtn>

        <template v-if="isReceptionist">
          <VBtn
            to="/empleado/agenda-global"
            variant="text"
            size="small"
            :class="linkClass('/empleado/agenda-global')"
            prepend-icon="ri-calendar-todo-line"
          >
            Agenda global
          </VBtn>
          <VBtn
            to="/empleado/clientes"
            variant="text"
            size="small"
            :class="linkClass('/empleado/clientes')"
            prepend-icon="ri-user-search-line"
          >
            Clientes
          </VBtn>
          <VBtn
            to="/empleado/shop"
            variant="text"
            size="small"
            :class="linkClass('/empleado/shop')"
            prepend-icon="ri-shopping-bag-3-line"
          >
            Tienda
          </VBtn>
        </template>

        <template v-if="isGroomer">
          <VBtn
            to="/groomer/today"
            variant="text"
            size="small"
            :class="linkClass('/groomer/today')"
            prepend-icon="ri-calendar-event-line"
          >
            Mis citas del día
          </VBtn>
          <VBtn
            :to="{ name: 'groomer-service-console', params: { id: '101' } }"
            variant="text"
            size="small"
            :class="linkClass('/groomer/service')"
            prepend-icon="ri-scissors-line"
          >
            Consola de trabajo
          </VBtn>
        </template>
      </div>

      <VSpacer />

      <span class="text-caption text-medium-emphasis d-none d-sm-inline">
        {{ auth.user?.first_name }} · {{ auth.user?.role }}
      </span>
    </VContainer>
  </VAppBar>
</template>

<style scoped>
.staff-nav {
  border-color: rgba(var(--v-theme-on-surface), 0.08) !important;
}
</style>
