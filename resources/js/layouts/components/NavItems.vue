<script setup>
import VerticalNavGroup from '@layouts/components/VerticalNavGroup.vue'; // si tu template lo soporta
import VerticalNavLink from '@layouts/components/VerticalNavLink.vue';
</script>

<template>
  <!-- ========== SECCIÓN PRINCIPAL ========== -->
  <VerticalNavLink
    :item="{
      title: 'Dashboard',
      icon: 'ri-home-smile-line',
      to: '/admin',
    }"
  />

  <!-- ========== GESTIÓN DE NEGOCIO ========== -->
  <VerticalNavGroup :item="{ title: 'Gestión', icon: 'ri-briefcase-line' }">
    <VerticalNavLink
      :item="{
        title: 'Clientes',
        icon: 'ri-user-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Mascotas',
        icon: 'ri-paw-print-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Empleados',
        icon: 'ri-team-line',
        to: '/admin/employees',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Servicios',
        icon: 'ri-scissors-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Citas / Agenda',
        icon: 'ri-calendar-line',
        to: '',
      }"
    />
  </VerticalNavGroup>

  <!-- CMS  -->
  <VerticalNavGroup :item="{ title: 'Contenido Web', icon: 'ri-global-line' }">
    <VerticalNavLink
      :item="{
        title: 'Páginas',
        icon: 'ri-file-copy-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Blog / Noticias',
        icon: 'ri-article-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Galería de Fotos',
        icon: 'ri-image-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Testimonios',
        icon: 'ri-chat-quote-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Promociones',
        icon: 'ri-discount-percent-line',
        to: '',
      }"
    />
  </VerticalNavGroup>

  <!-- ========== REPORTES Y ESTADÍSTICAS ========== -->
  <VerticalNavGroup :item="{ title: 'Reportes', icon: 'ri-bar-chart-2-line' }">
    <VerticalNavLink
      :item="{
        title: 'Reporte de Ventas',
        icon: 'ri-line-chart-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Reporte de Citas',
        icon: 'ri-calendar-check-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Reporte de Clientes',
        icon: 'ri-group-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Dashboard Avanzado',
        icon: 'ri-bar-chart-line',
        to: '',
      }"
    />
  </VerticalNavGroup>

  <!-- ========== SEGURIDAD Y SISTEMA ========== -->
  <VerticalNavGroup :item="{ title: 'Seguridad', icon: 'ri-lock-line' }">
    <VerticalNavLink
      :item="{
        title: 'Auditoría / Trazabilidad',
        icon: 'ri-shield-keyhole-line',
        to: '/admin/logs-trazabilidad',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Bitácora de Accesos',
        icon: 'ri-history-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Respaldos',
        icon: 'ri-database-backup-line',
        to: '',
      }"
    />
  </VerticalNavGroup>

  <!-- ========== CONFIGURACIÓN ========== -->
  <VerticalNavGroup :item="{ title: 'Ajustes', icon: 'ri-settings-3-line' }">
    <VerticalNavLink
      :item="{
        title: 'Configuración General',
        icon: 'ri-settings-line',
        to: '/admin/settings',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Configuración del Spa',
        icon: 'ri-store-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Horarios / Turnos',
        icon: 'ri-time-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Medios de Pago',
        icon: 'ri-bank-card-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Notificaciones',
        icon: 'ri-notification-3-line',
        to: '',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Roles y Permisos',
        icon: 'ri-user-settings-line',
        to: '',
      }"
    />
  </VerticalNavGroup>
</template>
