<template>
  <div
    class="layout-wrapper layout-staff"
    data-allow-mismatch
  >
    <StaffAreaNav />
    <RouterView />
  </div>
</template>

<script setup>
import StaffAreaNav from '@/components/empleado/StaffAreaNav.vue'
</script>

<style scoped>
.layout-staff {
  display: flex;
  flex-direction: column;
  min-block-size: 100vh;
}
</style>
