<template>
  <div class="h-100 d-flex align-center justify-md-space-between justify-center">
    <!-- 👉 Footer: left content -->
    <span class="d-flex align-center">
      &copy;
      {{ new Date().getFullYear() }}
      PetSpa V1
      <VIcon
        icon="ri-heart-line"
        color="error"
        size="1.25rem"
        class="mx-1"
      />
      Admin Panel
    </span>
    <!-- 👉 Footer: right content -->
    <span class="d-md-flex gap-x-4 text-primary d-none">
      <a href="/admin">Dashboard</a>
      <a href="/">Landing</a>
    </span>
  </div>
</template>
