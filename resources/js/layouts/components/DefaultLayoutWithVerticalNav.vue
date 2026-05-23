<script setup>
import NavItems from '@/layouts/components/NavItems.vue'
import logo from '@images/logo.svg?raw'
import VerticalNavLayout from '@layouts/components/VerticalNavLayout.vue'

// Components
import Footer from '@/layouts/components/Footer.vue'
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import { useAuthStore } from '@/stores/auth'
import { apiFetch } from '@/utils/apiFetch'

const auth = useAuthStore()
const homePath = computed(() => auth.isAdmin ? '/admin' : '/')

const pendingCount = ref(0)
const isNotificationDrawerOpen = ref(false)
const loadingNotifications = ref(false)
const pendingAppointments = ref([])

const fetchPendingCount = async () => {
  try {
    const res = await apiFetch('appointments/pending-count', { method: 'GET' }, auth.token)
    if (res.ok) {
      const data = await res.json()

      pendingCount.value = data.count || 0
    }
  } catch (e) {
    console.error('Error fetching pending count', e)
  }
}

const openNotificationsDrawer = async () => {
  isNotificationDrawerOpen.value = true
  loadingNotifications.value = true
  try {
    const res = await apiFetch('appointments/pending', { method: 'GET' }, auth.token)
    if (res.ok) {
      pendingAppointments.value = await res.json()
      pendingCount.value = pendingAppointments.value.length
    }
  } catch (e) {
    console.error('Error fetching pending list', e)
  } finally {
    loadingNotifications.value = false
  }
}

onMounted(() => {
  fetchPendingCount()

  // Optional: Poll every 60 seconds
  setInterval(fetchPendingCount, 60000)
})
</script>

<template>
  <VerticalNavLayout>
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <!-- 👉 Vertical nav toggle in overlay mode -->
        <IconBtn
          class="ms-n3 d-lg-none"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon icon="ri-menu-line" />
        </IconBtn>

        <!-- 👉 Search -->
        <div
          class="d-flex align-center cursor-pointer"
          style="user-select: none;"
        >
          <!-- 👉 Search Trigger button -->
          <IconBtn>
            <VIcon icon="ri-search-line" />
          </IconBtn>

          <span class="d-none d-md-flex align-center text-disabled">
            <span class="me-3">Search</span>
            <span class="meta-key">&#8984;K</span>
          </span>
        </div>

        <VSpacer />

        <IconBtn @click="openNotificationsDrawer">
          <VBadge
            v-if="pendingCount > 0"
            :content="pendingCount"
            color="error"
            offset-x="2"
            offset-y="2"
          >
            <VIcon icon="ri-notification-line" />
          </VBadge>
          <VIcon
            v-else
            icon="ri-notification-line"
          />
        </IconBtn>

        <NavbarThemeSwitcher class="me-2" />

        <UserProfile />
      </div>
    </template>

    <template #vertical-nav-header="{ toggleIsOverlayNavActive }">
      <RouterLink
        :to="homePath"
        class="app-logo app-title-wrapper"
      >
        <!-- eslint-disable vue/no-v-html -->
        <div
          class="d-flex"
          v-html="logo"
        />
        <!-- eslint-enable -->

        <h1 class="font-weight-medium leading-normal text-xl text-uppercase">
          PetSpa V1
        </h1>
      </RouterLink>

      <IconBtn
        class="d-block d-lg-none"
        @click="toggleIsOverlayNavActive(false)"
      >
        <VIcon icon="ri-close-line" />
      </IconBtn>
    </template>

    <template #vertical-nav-content>
      <NavItems />
    </template>

    <!-- 👉 Pages -->
    <slot />

    <!-- Panel Lateral de Notificaciones -->
    <VNavigationDrawer
      v-model="isNotificationDrawerOpen"
      location="right"
      temporary
      width="350"
      class="elevation-1"
    >
      <div class="pa-4 d-flex align-center justify-space-between bg-primary">
        <span class="text-subtitle-1 font-weight-bold text-white">Solicitudes de Cita</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="white"
          @click="isNotificationDrawerOpen = false"
        />
      </div>

      <div
        v-if="loadingNotifications"
        class="pa-4 text-center"
      >
        <VProgressCircular
          indeterminate
          color="primary"
        />
      </div>
      <div
        v-else-if="!pendingAppointments.length"
        class="pa-4 text-center text-medium-emphasis"
      >
        No hay solicitudes pendientes.
      </div>
      <VList
        v-else
        lines="two"
      >
        <VListItem
          v-for="apt in pendingAppointments"
          :key="apt.id"
          class="border-b"
          :to="auth.isAdmin ? '/admin/calendar' : '/empleado/agenda-global'"
          @click="isNotificationDrawerOpen = false"
        >
          <VListItemTitle class="font-weight-medium text-body-2">
            {{ apt.pet_name }} · {{ apt.service_name }}
          </VListItemTitle>
          <VListItemSubtitle class="text-caption mt-1">
            {{ String(apt.appointment_date).split('T')[0] }} a las {{ String(apt.start_time).split(' ')[1] ? String(apt.start_time).split(' ')[1].substring(0,5) : String(apt.start_time).substring(0,5) }}
          </VListItemSubtitle>
          <template #append>
            <VIcon
              icon="ri-arrow-right-s-line"
              size="small"
              class="text-disabled"
            />
          </template>
        </VListItem>
      </VList>
    </VNavigationDrawer>

    <!-- 👉 Footer -->
    <template #footer>
      <Footer />
    </template>
  </VerticalNavLayout>
</template>

<style lang="scss" scoped>
.meta-key {
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  block-size: 1.5625rem;
  line-height: 1.3125rem;
  padding-block: 0.125rem;
  padding-inline: 0.25rem;
}

.app-logo {
  display: flex;
  align-items: center;
  column-gap: 0.75rem;

  .app-logo-title {
    font-size: 1.25rem;
    font-weight: 500;
    line-height: 1.75rem;
    text-transform: uppercase;
  }
}
</style>
