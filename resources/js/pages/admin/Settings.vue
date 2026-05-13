<script setup>

const systemToggles = [
  { key: 'enable_preloader',  label: 'Preloader en transiciones', hint: 'Muestra indicador de carga al navegar entre páginas.' },
  { key: 'maintenance_mode',  label: 'Modo mantenimiento',        hint: 'Bloquea el acceso a usuarios no administradores.' },
  { key: 'activity_log',      label: 'Registro de actividad',     hint: 'Guarda un log de acciones de los usuarios.' },
  { key: 'confirm_delete',    label: 'Confirmar antes de eliminar', hint: 'Solicita confirmación al borrar registros.' },
]
const notifEvents = [
  { key: 'notify_new_appointment', label: 'Nueva cita registrada',         hint: 'Notificar al admin cuando se crea una cita.' },
  { key: 'notify_reminder',        label: 'Recordatorio al cliente',        hint: 'Enviar recordatorio automático antes de la cita.' },
  { key: 'notify_cancellation',    label: 'Cita cancelada',                 hint: 'Alertar al peluquero si se cancela una cita.' },
  { key: 'notify_new_customer',    label: 'Cliente nuevo registrado',       hint: 'Notificar cuando se registra un nuevo cliente.' },
]
const themeOptions = [
  { key: 'show_breadcrumbs', label: 'Mostrar breadcrumbs',          hint: 'Muestra la ruta de navegación en cada página.' },
  { key: 'animations',       label: 'Animaciones de transición',    hint: 'Habilita animaciones suaves al cambiar de vista.' },
  { key: 'progress_bar',     label: 'Barra de progreso superior',   hint: 'Muestra barra de progreso al cargar páginas.' },
]
// 🔁 Cuando conectes el backend, importa apiFetch y useAuthStore
// import { apiFetch } from '@/utils/apiFetch'
// import { useAuthStore } from '@/stores/auth'

const activeTab = ref('general')

// ─── DATOS MOCKEADOS ──────────────────────────────────────────
// 🔁 Reemplazar con: const res = await apiFetch('admin/settings', ...)
const form = reactive({
  // Tab General
  app_name: 'PetSpa Admin',
  slogan: '',
  currency: 'BOB',
  timezone: 'America/La_Paz',

  // Empresa
  company_name: 'PetSpa S.R.L.',
  tax_number: '123456789',
  email: 'contacto@petspa.bo',
  phone: '+591 72345678',
  city: 'La Paz',
  country: 'Bolivia',
  address: 'Av. Arce 2395, Sopocachi',
  copyright: '© 2025 PetSpa. Todos los derechos reservados.',

  // Tab Sistema
  enable_preloader: true,
  maintenance_mode: false,
  activity_log: true,
  confirm_delete: true,
  appointment_duration: 60,
  schedule_start: '08:00',
  schedule_end: '18:00',
  reminder: '24h',

  // Tab Notificaciones (SMTP)
  mail_driver: 'smtp',
  mail_host: '',
  mail_port: 587,
  mail_encryption: 'tls',
  mail_username: '',
  mail_password: '',
  mail_from_name: 'PetSpa Notificaciones',
  mail_from_address: '',
  notify_new_appointment: true,
  notify_reminder: true,
  notify_cancellation: false,
  notify_new_customer: true,

  // Tab Apariencia
  theme_mode: 'light',
  primary_color: '#534AB7',
  nav_type: 'fixed',
  density: 'default',
  show_breadcrumbs: true,
  animations: true,
  progress_bar: false,
})

const logoPreview = ref(null)
const faviconPreview = ref(null)
const isSaving = ref(false)

const tabs = [
  { key: 'general',       label: 'General',        icon: 'tabler-building' },
  { key: 'system',        label: 'Sistema',         icon: 'tabler-adjustments-horizontal' },
  { key: 'notifications', label: 'Notificaciones',  icon: 'tabler-bell' },
  { key: 'theme',         label: 'Apariencia',      icon: 'tabler-palette' },
]

const colors = ['#534AB7', '#0F6E56', '#854F0B', '#993C1D', '#185FA5']

const handleLogoChange = (e) => {
  const file = e.target.files[0]
  if (file) logoPreview.value = URL.createObjectURL(file)
}

const handleFaviconChange = (e) => {
  const file = e.target.files[0]
  if (file) faviconPreview.value = URL.createObjectURL(file)
}

const saveSettings = async () => {
  isSaving.value = true
  try {
    // 🔁 Descomentar cuando el API esté listo:
    // const res = await apiFetch('admin/settings', { method: 'POST', body: JSON.stringify(form) }, auth.token)
    await new Promise(r => setTimeout(r, 800)) // simula delay
    // Aquí mostrar snackbar de éxito
    console.log('Guardado:', toRaw(form))
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <!-- Breadcrumb -->
  <VBreadcrumbs :items="['Inicio', 'Configuración']" class="px-0 pb-2" />

  <div class="d-flex align-center gap-2 mb-6">
    <VIcon icon="tabler-settings" color="primary" />
    <h1 class="text-h5 font-weight-medium">Configuración</h1>
  </div>

  <!-- Tabs -->
  <VTabs v-model="activeTab" class="mb-6">
    <VTab v-for="tab in tabs" :key="tab.key" :value="tab.key">
      <VIcon :icon="tab.icon" class="mr-2" size="18" />
      {{ tab.label }}
    </VTab>
  </VTabs>

  <VWindow v-model="activeTab">

    <!-- ──────────── TAB GENERAL ──────────── -->
    <VWindowItem value="general">
      <VCard>
        <VCardText>

          <!-- Info de la app -->
          <p class="text-overline text-primary mb-4">
            <VIcon icon="tabler-info-circle" size="16" class="mr-1" />
            Información de la aplicación
          </p>
          <VRow>
            <VCol cols="12" md="6">
              <VTextField v-model="form.app_name" label="Nombre de la app *" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.slogan" label="Slogan / descripción" />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect v-model="form.currency" label="Moneda *"
                :items="['BOB — Boliviano','USD — Dólar','PEN — Sol peruano']" />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect v-model="form.timezone" label="Zona horaria *"
                :items="['America/La_Paz (UTC-4)','America/Lima (UTC-5)','America/Bogota (UTC-5)']" />
            </VCol>
          </VRow>

          <VDivider class="my-5" />

          <!-- Info empresa -->
          <p class="text-overline text-primary mb-4">
            <VIcon icon="tabler-building-store" size="16" class="mr-1" />
            Información de la empresa
          </p>
          <VRow>
            <VCol cols="12" md="6">
              <VTextField v-model="form.company_name" label="Razón social *" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.tax_number" label="NIT / RUC" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.email" label="Email de contacto *" type="email" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.phone" label="Teléfono *" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.city" label="Ciudad" />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect v-model="form.country" label="País"
                :items="['Bolivia','Perú','Argentina','Chile']" />
            </VCol>
            <VCol cols="12">
              <VTextarea v-model="form.address" label="Dirección" rows="2" />
            </VCol>
            <VCol cols="12">
              <VTextField v-model="form.copyright" label="Copyright" />
            </VCol>
          </VRow>

          <VDivider class="my-5" />

          <!-- Identidad visual -->
          <p class="text-overline text-primary mb-4">
            <VIcon icon="tabler-photo" size="16" class="mr-1" />
            Identidad visual
          </p>
          <VRow>
            <VCol cols="12" md="6">
              <p class="text-body-2 text-medium-emphasis mb-2">Logo principal</p>
              <div class="d-flex align-center gap-4">
                <VAvatar v-if="logoPreview" :image="logoPreview" size="72" rounded />
                <VAvatar v-else color="secondary" size="72" rounded>
                  <VIcon icon="tabler-photo" size="28" />
                </VAvatar>
                <!-- 🔁 onChange sube la imagen al servidor -->
                <VBtn variant="outlined" prepend-icon="tabler-cloud-upload" @click="$refs.logoInput.click()">
                  Elegir archivo
                </VBtn>
                <input ref="logoInput" type="file" accept="image/*" hidden @change="handleLogoChange" />
              </div>
            </VCol>
            <VCol cols="12" md="6">
              <p class="text-body-2 text-medium-emphasis mb-2">Favicon</p>
              <div class="d-flex align-center gap-4">
                <VAvatar v-if="faviconPreview" :image="faviconPreview" size="72" rounded />
                <VAvatar v-else color="secondary" size="72" rounded>
                  <VIcon icon="tabler-photo" size="28" />
                </VAvatar>
                <VBtn variant="outlined" prepend-icon="tabler-cloud-upload" @click="$refs.faviconInput.click()">
                  Elegir archivo
                </VBtn>
                <input ref="faviconInput" type="file" accept="image/*" hidden @change="handleFaviconChange" />
              </div>
            </VCol>
          </VRow>

        </VCardText>
        <VDivider />
        <VCardActions class="justify-end pa-4 gap-3">
          <VBtn variant="outlined" prepend-icon="tabler-refresh">Restablecer</VBtn>
          <VBtn color="primary" prepend-icon="tabler-device-floppy"
            :loading="isSaving" @click="saveSettings">
            Guardar cambios
          </VBtn>
        </VCardActions>
      </VCard>
    </VWindowItem>

    <!-- ──────────── TAB SISTEMA ──────────── -->
    <VWindowItem value="system">
      <VCard>
        <VCardText>
          <p class="text-overline text-primary mb-4">Comportamiento del sistema</p>
          <VList lines="two" class="mb-4">
            <VListItem v-for="toggle in systemToggles" :key="toggle.key">
              <VListItemTitle>{{ toggle.label }}</VListItemTitle>
              <VListItemSubtitle>{{ toggle.hint }}</VListItemSubtitle>
              <template #append>
                <VSwitch v-model="form[toggle.key]" color="primary" hide-details />
              </template>
            </VListItem>
          </VList>
          <VDivider class="my-5" />
          <p class="text-overline text-primary mb-4">Citas y agenda</p>
          <VRow>
            <VCol cols="12" md="6">
              <VSelect v-model="form.appointment_duration" label="Duración por defecto"
                :items="[{title:'30 min',value:30},{title:'45 min',value:45},{title:'60 min',value:60},{title:'90 min',value:90}]" />
            </VCol>
            <VCol cols="12" md="3">
              <VTextField v-model="form.schedule_start" label="Hora inicio" type="time" />
            </VCol>
            <VCol cols="12" md="3">
              <VTextField v-model="form.schedule_end" label="Hora cierre" type="time" />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect v-model="form.reminder" label="Recordatorio automático"
                :items="['No enviar','1 hora antes','24 horas antes','48 horas antes']" />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardActions class="justify-end pa-4 gap-3">
          <VBtn variant="outlined" prepend-icon="tabler-refresh">Restablecer</VBtn>
          <VBtn color="primary" prepend-icon="tabler-device-floppy"
            :loading="isSaving" @click="saveSettings">Guardar cambios</VBtn>
        </VCardActions>
      </VCard>
    </VWindowItem>

    <!-- ──────────── TAB NOTIFICACIONES ──────────── -->
    <VWindowItem value="notifications">
      <VCard>
        <VCardText>
          <p class="text-overline text-primary mb-4">Configuración SMTP</p>
          <VRow>
            <VCol cols="12" md="6">
              <VSelect v-model="form.mail_driver" label="Driver"
                :items="['smtp','mailgun','sendgrid']" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.mail_host" label="Host SMTP" />
            </VCol>
            <VCol cols="12" md="3">
              <VTextField v-model="form.mail_port" label="Puerto" type="number" />
            </VCol>
            <VCol cols="12" md="3">
              <VSelect v-model="form.mail_encryption" label="Encriptación"
                :items="['tls','ssl','none']" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.mail_username" label="Usuario SMTP" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.mail_password" label="Contraseña SMTP" type="password" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.mail_from_name" label="Nombre remitente" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.mail_from_address" label="Email remitente" type="email" />
            </VCol>
          </VRow>
          <VDivider class="my-5" />
          <p class="text-overline text-primary mb-4">Eventos de notificación</p>
          <VList lines="two">
            <VListItem v-for="evt in notifEvents" :key="evt.key">
              <VListItemTitle>{{ evt.label }}</VListItemTitle>
              <VListItemSubtitle>{{ evt.hint }}</VListItemSubtitle>
              <template #append>
                <VSwitch v-model="form[evt.key]" color="primary" hide-details />
              </template>
            </VListItem>
          </VList>
        </VCardText>
        <VDivider />
        <VCardActions class="justify-end pa-4 gap-3">
          <VBtn variant="outlined" prepend-icon="tabler-send">Email de prueba</VBtn>
          <VBtn color="primary" prepend-icon="tabler-device-floppy"
            :loading="isSaving" @click="saveSettings">Guardar cambios</VBtn>
        </VCardActions>
      </VCard>
    </VWindowItem>

    <!-- ──────────── TAB APARIENCIA ──────────── -->
    <VWindowItem value="theme">
      <VCard>
        <VCardText>
          <p class="text-overline text-primary mb-4">Tema y colores</p>
          <VRow>
            <VCol cols="12" md="6">
              <VSelect v-model="form.theme_mode" label="Modo de tema"
                :items="['light','dark','system']" />
            </VCol>
            <VCol cols="12" md="6">
              <p class="text-body-2 text-medium-emphasis mb-2">Color primario</p>
              <div class="d-flex gap-3">
                <div v-for="c in colors" :key="c"
                  :style="`width:28px;height:28px;border-radius:50%;background:${c};cursor:pointer;
                    border: 2px solid ${form.primary_color === c ? c : 'transparent'}`"
                  @click="form.primary_color = c" />
              </div>
            </VCol>
            <VCol cols="12" md="6">
              <VSelect v-model="form.nav_type" label="Navegación lateral"
                :items="['fixed','collapsible','mini']" />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect v-model="form.density" label="Densidad"
                :items="['compact','default','comfortable']" />
            </VCol>
          </VRow>
          <VDivider class="my-5" />
          <p class="text-overline text-primary mb-4">Opciones visuales</p>
          <VList lines="two">
            <VListItem v-for="opt in themeOptions" :key="opt.key">
              <VListItemTitle>{{ opt.label }}</VListItemTitle>
              <VListItemSubtitle>{{ opt.hint }}</VListItemSubtitle>
              <template #append>
                <VSwitch v-model="form[opt.key]" color="primary" hide-details />
              </template>
            </VListItem>
          </VList>
        </VCardText>
        <VDivider />
        <VCardActions class="justify-end pa-4 gap-3">
          <VBtn variant="outlined" prepend-icon="tabler-refresh">Restablecer</VBtn>
          <VBtn color="primary" prepend-icon="tabler-device-floppy"
            :loading="isSaving" @click="saveSettings">Guardar cambios</VBtn>
        </VCardActions>
      </VCard>
    </VWindowItem>

  </VWindow>
</template>
