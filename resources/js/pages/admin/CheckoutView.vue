<script setup>
import { apiFetch } from '@/utils/apiFetch'
import { useAuthStore } from '@/stores/auth'
import { useShopStore } from '@/stores/shop'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const shop = useShopStore()

const appointmentId = computed(() => route.params.appointment_id)
const isRetailOnly = computed(() => appointmentId.value === 'retail')
const appointment = ref(null)
const loading = ref(false)
const submitting = ref(false)
const error = ref('')

const appointmentSubtotal = computed(() => Number(appointment.value?.agreed_price || 0))
const shopSubtotal = computed(() => shop.subtotal)
const grandTotal = computed(() => appointmentSubtotal.value + shopSubtotal.value)

const paymentMethod = ref('Efectivo')

const paymentItems = [
  { title: 'Efectivo', value: 'Efectivo', icon: 'ri-money-dollar-circle-line' },
  { title: 'Transferencia Bancaria', value: 'Transferencia Bancaria', icon: 'ri-bank-line' },
  { title: 'Código QR (Yape/Plin/Simple)', value: 'Código QR (Yape/Plin/Simple)', icon: 'ri-qr-code-line' },
  { title: 'Tarjeta de Crédito/Débito', value: 'Tarjeta de Crédito/Débito', icon: 'ri-bank-card-line' },
]

const qrModalOpen = ref(false)
const notifySent = ref(false)

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

function showNotification(msg, color = 'success') {
  snackbarText.value = msg
  snackbarColor.value = color
  snackbar.value = true
}

async function fetchAppointment() {
  if (isRetailOnly.value) {
    appointment.value = null
    
    return
  }

  loading.value = true
  try {
    const res = await apiFetch('appointments', { method: 'GET' }, auth.token)
    if (res.ok) {
      const all = await res.json()

      appointment.value = all.find(a => String(a.id) === String(appointmentId.value)) ?? null
    }
  }
  catch (e) {
    console.error(e)
  }
  finally {
    loading.value = false
  }
}

async function procesarPagoDefinitivo() {
  submitting.value = true
  error.value = ''
  try {
    const res = await apiFetch('payments', {
      method: 'POST',
      body: JSON.stringify({
        appointment_id: isRetailOnly.value ? null : Number(appointmentId.value),
        total_amount: grandTotal.value,
        payment_method: paymentMethod.value,
        items: shop.items.map(i => ({
          productId: i.productId,
          quantity: i.quantity,
        })),
      }),
    }, auth.token)

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      const msg = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Error al procesar el cobro.'

      error.value = msg
      showNotification(msg, 'error')
      
      return
    }

    if (data.low_stock_alerts && data.low_stock_alerts.length > 0) {
      console.warn('Low stock alerts:', data.low_stock_alerts)

      const alertNames = data.low_stock_alerts.map(a => a.name).join(', ')

      showNotification(`¡Cobro registrado! Alertas de stock bajo: ${alertNames}`, 'warning')
      await new Promise(resolve => setTimeout(resolve, 2000))
    } else {
      showNotification('¡Cobro registrado con éxito!', 'success')
    }

    console.log('[Checkout] Cobro registrado', {
      appointment_id: isRetailOnly.value ? null : appointmentId.value,
      payment_type: paymentMethod.value,
      shop_items: shop.items,
      shop_total: shopSubtotal.value,
      grand_total: grandTotal.value,
    })

    shop.clearCart()
    shop.setCheckoutAppointmentId(null)
    router.push(backTarget.value)
  }
  catch (e) {
    error.value = 'Error de conexión con el servidor.'
    showNotification(error.value, 'error')
  }
  finally {
    submitting.value = false
  }
}

const sendPickupNotification = () => {
  console.log('[Checkout] WhatsApp recojo simulado', {
    appointment: appointment.value,
    shop: shop.items,
  })
  notifySent.value = true
}

const backTarget = computed(() => {
  if (route.path.startsWith('/admin')) {
    return shop.items.length ? '/admin/shop' : '/admin/calendar'
  }

  return '/empleado/shop'
})

const shopBackPath = computed(() =>
  route.path.startsWith('/admin') ? '/admin/shop' : '/empleado/shop',
)

onMounted(() => {
  fetchAppointment()
})
</script>

<template>
  <div>
    <VBtn
      variant="text"
      prepend-icon="ri-arrow-left-line"
      class="mb-4"
      @click="router.push(isRetailOnly ? shopBackPath : backTarget)"
    >
      {{ isRetailOnly ? 'Volver a tienda' : 'Volver a agenda' }}
    </VBtn>

    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      {{ error }}
    </VAlert>

    <VRow class="mb-4">
      <VCol cols="12">
        <h2 class="text-h5 mb-1">
          Recepción y cobro
        </h2>
        <p
          v-if="appointment"
          class="text-body-2 text-medium-emphasis mb-0"
        >
          Cita #{{ appointmentId }} · {{ appointment.pet_name }} · {{ appointment.service_name }}
        </p>
        <p
          v-else-if="isRetailOnly"
          class="text-body-2 text-medium-emphasis mb-0"
        >
          Venta de tienda · {{ shop.itemCount }} producto(s) en carrito
        </p>
      </VCol>
    </VRow>

    <VRow v-if="!loading && (appointment || isRetailOnly || shop.items.length)">
      <VCol
        cols="12"
        lg="7"
      >
        <VCard
          rounded="lg"
          class="pa-6 mb-4"
        >
          <div class="text-subtitle-1 font-weight-medium mb-4">
            Resumen de cargos
          </div>
          <VList
            lines="two"
            class="bg-transparent pa-0"
          >
            <VListItem
              v-if="appointment"
              class="px-0"
            >
              <VListItemTitle>Servicio: {{ appointment.service_name }}</VListItemTitle>
              <VListItemSubtitle>Precio pactado según peso</VListItemSubtitle>
              <template #append>
                <span class="text-body-1 font-weight-medium">${{ appointmentSubtotal.toFixed(2) }}</span>
              </template>
            </VListItem>

            <VListItem
              v-for="item in shop.items"
              :key="item.productId"
              class="px-0"
            >
              <VListItemTitle>
                {{ item.name }} × {{ item.quantity }}
              </VListItemTitle>
              <VListItemSubtitle>Tienda</VListItemSubtitle>
              <template #append>
                <span class="text-body-1 font-weight-medium">
                  ${{ (item.price * item.quantity).toFixed(2) }}
                </span>
              </template>
            </VListItem>
          </VList>

          <VDivider
            v-if="appointment && shop.items.length"
            class="my-4"
          />

          <div
            v-if="appointment && shop.items.length"
            class="d-flex justify-space-between text-body-2 mb-2"
          >
            <span>Subtotal servicio</span>
            <span>${{ appointmentSubtotal.toFixed(2) }}</span>
          </div>
          <div
            v-if="shop.items.length"
            class="d-flex justify-space-between text-body-2 mb-2"
          >
            <span>Subtotal tienda</span>
            <span>${{ shopSubtotal.toFixed(2) }}</span>
          </div>

          <VDivider class="my-4" />
          <div class="d-flex justify-space-between text-h6">
            <span>Total</span>
            <span class="text-primary">${{ grandTotal.toFixed(2) }}</span>
          </div>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        lg="5"
      >
        <VCard
          rounded="lg"
          class="pa-6 mb-4"
        >
          <div class="text-subtitle-1 font-weight-medium mb-4">
            Método de pago
          </div>

          <!-- Seleccionador Premium de Método de Pago -->
          <VRow
            class="mb-4"
            dense
          >
            <VCol
              v-for="p in paymentItems"
              :key="p.value"
              cols="12"
              sm="6"
            >
              <VCard
                :variant="paymentMethod === p.value ? 'flat' : 'outlined'"
                :color="paymentMethod === p.value ? 'primary' : 'default'"
                class="d-flex align-center pa-4 cursor-pointer payment-method-card"
                :class="[paymentMethod === p.value ? 'bg-primary-lighten-5 border-primary border-opacity-100' : '']"
                elevation="0"
                rounded="lg"
                @click="paymentMethod = p.value"
              >
                <VIcon
                  :icon="p.icon"
                  size="24"
                  class="me-3"
                  :color="paymentMethod === p.value ? 'primary' : 'medium-emphasis'"
                />
                <span 
                  class="font-weight-medium text-body-2"
                  :class="paymentMethod === p.value ? 'text-primary' : 'text-high-emphasis'"
                >
                  {{ p.title }}
                </span>
              </VCard>
            </VCol>
          </VRow>

          <!-- Simulador de QR dinámico -->
          <VExpandTransition>
            <div v-if="paymentMethod === 'Código QR (Yape/Plin/Simple)'">
              <VCard
                variant="outlined"
                color="info"
                class="pa-4 mb-4 text-center"
                rounded="lg"
              >
                <div class="d-flex align-center justify-center mb-3">
                  <VIcon
                    icon="ri-qr-code-line"
                    color="info"
                    size="24"
                    class="me-2"
                  />
                  <span class="font-weight-bold text-subtitle-1 text-info">Simulador QR de Pago</span>
                </div>
                <div class="d-flex flex-column align-center justify-center py-4 bg-grey-lighten-4 rounded-lg border mb-3">
                  <span class="text-caption text-medium-emphasis mb-2">Escanea para pagar ${{ grandTotal.toFixed(2) }}</span>
                  <VImg
                    :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=petspa_payment_total_${grandTotal}`"
                    width="180"
                    height="180"
                    class="border rounded bg-white"
                  />
                  <div class="text-caption font-weight-medium text-center mt-3">
                    <div>Yape / Plin / Simple: <strong>+51 987 654 321</strong></div>
                    <div class="text-medium-emphasis">
                      Destinatario: Taller Pet Spa Tecsup
                    </div>
                  </div>
                </div>
                <VBtn
                  color="info"
                  variant="tonal"
                  size="small"
                  block
                  prepend-icon="ri-zoom-in-line"
                  @click="qrModalOpen = true"
                >
                  Ver QR en pantalla completa
                </VBtn>
              </VCard>
            </div>
          </VExpandTransition>

          <VBtn
            color="primary"
            block
            class="mt-4"
            size="large"
            :loading="submitting"
            :disabled="!appointment && !shop.items.length"
            @click="procesarPagoDefinitivo"
          >
            Registrar cobro y guardar pago{{ appointment ? ' y finalizar' : '' }}
          </VBtn>
        </VCard>

        <!-- Dialog/Modal de QR en pantalla completa -->
        <VDialog
          v-model="qrModalOpen"
          max-width="400"
        >
          <VCard
            rounded="lg"
            class="text-center pa-6"
          >
            <div class="d-flex justify-space-between align-center mb-4">
              <span class="text-h6 font-weight-bold text-info d-flex align-center ga-1">
                <VIcon icon="ri-qr-code-line" /> Código QR de Recepción
              </span>
              <VBtn
                icon="ri-close-line"
                variant="text"
                size="small"
                @click="qrModalOpen = false"
              />
            </div>
            <div class="pa-4 bg-grey-lighten-4 rounded-lg border mb-4 d-flex justify-center">
              <VImg
                :src="`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=petspa_payment_total_${grandTotal}`"
                width="280"
                height="280"
                class="border rounded bg-white"
              />
            </div>
            <div class="text-h5 font-weight-bold text-primary mb-1">
              ${{ grandTotal.toFixed(2) }}
            </div>
            <div class="text-body-2 text-medium-emphasis mb-4">
              Monto Total a Cobrar
            </div>
            <div class="text-body-2 font-weight-medium">
              Yape / Plin / Simple: <strong>+51 987 654 321</strong>
            </div>
            <div class="text-caption text-medium-emphasis mb-4">
              Destinatario: Taller Pet Spa Tecsup
            </div>
            <VBtn
              color="primary"
              block
              @click="qrModalOpen = false"
            >
              Cerrar Vista
            </VBtn>
          </VCard>
        </VDialog>

        <VCard
          v-if="appointment || shop.items.length"
          rounded="lg"
          class="pa-6"
          variant="tonal"
          color="success"
        >
          <div class="text-subtitle-2 mb-2">
            Notificación al cliente
          </div>
          <p class="text-body-2 mb-4">
            Avisar por WhatsApp que la mascota está lista o que puede pasar a retirar su compra.
          </p>
          <VBtn
            color="success"
            variant="flat"
            block
            prepend-icon="ri-whatsapp-line"
            :disabled="notifySent"
            @click="sendPickupNotification"
          >
            {{ notifySent ? 'Notificación enviada' : 'Enviar notificación de recojo' }}
          </VBtn>
        </VCard>
      </VCol>
    </VRow>

    <VAlert
      v-else-if="!loading"
      type="warning"
      variant="tonal"
    >
      No hay cargos para cobrar. Regresa a la
      <RouterLink :to="shopBackPath">
        tienda
      </RouterLink>
      o a la agenda.
    </VAlert>

    <!-- Snackbar de notificaciones -->
    <VSnackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="3500"
      location="top end"
    >
      {{ snackbarText }}
    </VSnackbar>
  </div>
</template>

<style scoped>
.payment-method-card {
  transition: all 0.2s ease-in-out;
  border-width: 1px !important;
}
.payment-method-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.5) !important;
  background-color: rgba(var(--v-theme-primary), 0.02);
}
.bg-primary-lighten-5 {
  background-color: rgba(var(--v-theme-primary), 0.06) !important;
}
.border-primary {
  border-color: rgb(var(--v-theme-primary)) !important;
}
</style>
