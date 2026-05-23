<script setup>
import { useShopStore } from '@/stores/shop'
import { useAuthStore } from '@/stores/auth'
import { apiFetch } from '@/utils/apiFetch'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const shop = useShopStore()

const isAdminArea = computed(() => route.path.startsWith('/admin'))

const products = ref([])
const loading = ref(false)
const search = ref('')
const snackbar = ref(false)
const snackbarText = ref('')
const linkAppointmentId = ref(shop.checkoutAppointmentId ? String(shop.checkoutAppointmentId) : '')

const filteredProducts = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) {
    return products.value
  }

  return products.value.filter(p =>
    String(p.name).toLowerCase().includes(q)
    || String(p.sku || '').toLowerCase().includes(q)
    || String(p.category || '').toLowerCase().includes(q),
  )
})

function mapApiProduct(p) {
  return {
    id: p.id,
    name: p.name,
    sku: p.sku ?? p.code,
    price: Number(p.price ?? 0),
    stock: Number(p.stock ?? 0),
    category: p.category ?? 'General',
    is_active: p.is_active !== false,
  }
}

async function fetchProducts() {
  loading.value = true
  try {
    const res = await apiFetch('admin/products', { method: 'GET' }, auth.token)
    if (res.ok) {
      const data = await res.json()

      const rows = (Array.isArray(data) ? data : (data.data ?? []))
        .filter(p => p.is_active !== false && Number(p.stock) > 0)
        .map(mapApiProduct)

      products.value = rows
    }
  }
  catch (e) {
    console.error(e)
  }
  finally {
    loading.value = false
  }
}

function notify(message, color = 'info') {
  snackbarText.value = message
  snackbar.value = true
}

function onAdd(product) {
  const ok = shop.addItem(product)
  if (!ok) {
    notify('Sin stock disponible para agregar más unidades.', 'warning')
    
    return
  }
  notify(`${product.name} agregado al carrito`, 'success')
}

function proceedToCheckout() {
  if (shop.isEmpty) {
    notify('El carrito está vacío.', 'warning')
    
    return
  }

  if (linkAppointmentId.value) {
    shop.setCheckoutAppointmentId(linkAppointmentId.value)
  }

  const checkoutPath = shop.prepareCheckoutRoute(isAdminArea.value)

  router.push(checkoutPath)
}

watch(linkAppointmentId, val => {
  if (val) {
    shop.setCheckoutAppointmentId(val)
  }
})

onMounted(fetchProducts)
</script>

<template>
  <div class="store-view">
    <VRow class="mb-4">
      <VCol cols="12">
        <h2 class="text-h5 mb-1">
          Tienda del Spa
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Venta de productos adicionales mientras el cliente espera (juguetes, comida, accesorios).
        </p>
      </VCol>
    </VRow>

    <VRow>
      <!-- Catálogo -->
      <VCol
        cols="12"
        lg="8"
      >
        <VCard
          rounded="lg"
          class="pa-4"
        >
          <VTextField
            v-model="search"
            label="Buscar producto"
            placeholder="Nombre, SKU o categoría…"
            prepend-inner-icon="ri-search-line"
            clearable
            hide-details
            class="mb-4"
          />

          <div
            v-if="loading"
            class="d-flex justify-center py-12"
          >
            <VProgressCircular
              indeterminate
              color="primary"
            />
          </div>

          <VAlert
            v-else-if="!filteredProducts.length"
            type="info"
            variant="tonal"
          >
            No hay productos que coincidan con la búsqueda.
          </VAlert>

          <VRow v-else>
            <VCol
              v-for="product in filteredProducts"
              :key="product.id"
              cols="12"
              sm="6"
              md="4"
            >
              <VCard
                rounded="lg"
                variant="outlined"
                class="product-card h-100 d-flex flex-column"
              >
                <VCardText class="flex-grow-1">
                  <VChip
                    size="x-small"
                    color="secondary"
                    variant="tonal"
                    class="mb-2"
                  >
                    {{ product.category }}
                  </VChip>
                  <div class="text-subtitle-1 font-weight-medium mb-1">
                    {{ product.name }}
                  </div>
                  <div class="text-h6 text-primary mb-2">
                    ${{ product.price.toFixed(2) }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Stock: {{ product.stock }}
                    <span v-if="product.sku"> · {{ product.sku }}</span>
                  </div>
                </VCardText>
                <VCardActions class="px-4 pb-4 pt-0">
                  <VBtn
                    color="primary"
                    variant="tonal"
                    block
                    prepend-icon="ri-shopping-cart-line"
                    :disabled="product.stock <= 0"
                    @click="onAdd(product)"
                  >
                    Agregar al carrito
                  </VBtn>
                </VCardActions>
              </VCard>
            </VCol>
          </VRow>
        </VCard>
      </VCol>

      <!-- Carrito -->
      <VCol
        cols="12"
        lg="4"
      >
        <VCard
          rounded="lg"
          class="pa-4 cart-panel"
        >
          <div class="d-flex align-center justify-space-between mb-4">
            <div class="text-subtitle-1 font-weight-medium d-flex align-center ga-2">
              <VIcon
                icon="ri-shopping-bag-3-line"
                color="primary"
              />
              Carrito
            </div>
            <VChip
              size="small"
              color="primary"
              variant="tonal"
            >
              {{ shop.itemCount }} uds.
            </VChip>
          </div>

          <VTextField
            v-model="linkAppointmentId"
            label="Vincular a cita (opcional)"
            placeholder="Ej. 501"
            hint="Deja vacío para venta solo de tienda"
            persistent-hint
            density="compact"
            class="mb-4"
            hide-details="auto"
          />

          <VAlert
            v-if="shop.isEmpty"
            type="info"
            variant="tonal"
            density="comfortable"
          >
            Aún no has agregado productos.
          </VAlert>

          <VList
            v-else
            class="bg-transparent pa-0 mb-4"
            density="compact"
          >
            <VListItem
              v-for="item in shop.items"
              :key="item.productId"
              class="px-0 border-b"
            >
              <VListItemTitle class="text-body-2 font-weight-medium">
                {{ item.name }}
              </VListItemTitle>
              <VListItemSubtitle>
                ${{ item.price.toFixed(2) }} c/u
              </VListItemSubtitle>
              <template #append>
                <div class="d-flex flex-column align-end ga-1">
                  <div class="d-flex align-center ga-1">
                    <VBtn
                      icon
                      size="x-small"
                      variant="text"
                      @click="shop.decrementItem(item.productId)"
                    >
                      <VIcon icon="ri-subtract-line" />
                    </VBtn>
                    <span class="text-body-2 font-weight-medium mx-1">{{ item.quantity }}</span>
                    <VBtn
                      icon
                      size="x-small"
                      variant="text"
                      :disabled="item.quantity >= item.stock"
                      @click="shop.incrementItem(item.productId)"
                    >
                      <VIcon icon="ri-add-line" />
                    </VBtn>
                  </div>
                  <span class="text-caption font-weight-medium">
                    ${{ (item.price * item.quantity).toFixed(2) }}
                  </span>
                  <VBtn
                    size="x-small"
                    variant="text"
                    color="error"
                    @click="shop.removeItem(item.productId)"
                  >
                    Quitar
                  </VBtn>
                </div>
              </template>
            </VListItem>
          </VList>

          <VDivider class="mb-3" />

          <div class="d-flex justify-space-between text-body-2 mb-1">
            <span>Subtotal tienda</span>
            <span>${{ shop.subtotal.toFixed(2) }}</span>
          </div>
          <div class="d-flex justify-space-between text-h6 mb-4">
            <span>Total</span>
            <span class="text-primary">${{ shop.subtotal.toFixed(2) }}</span>
          </div>

          <VBtn
            v-if="!shop.isEmpty"
            variant="text"
            size="small"
            color="error"
            class="mb-2"
            block
            @click="shop.clearCart()"
          >
            Vaciar carrito
          </VBtn>

          <VBtn
            color="primary"
            block
            size="large"
            prepend-icon="ri-bank-card-line"
            :disabled="shop.isEmpty"
            @click="proceedToCheckout"
          >
            Proceder al pago
          </VBtn>
        </VCard>
      </VCol>
    </VRow>

    <VSnackbar
      v-model="snackbar"
      :timeout="2500"
      location="top end"
      color="primary"
    >
      {{ snackbarText }}
    </VSnackbar>
  </div>
</template>

<style scoped>
@media (min-width: 1280px) {
  .cart-panel {
    position: sticky;
    top: 80px;
  }
}

.product-card {
  transition: box-shadow 0.2s ease;
}

.product-card:hover {
  box-shadow: 0 4px 20px rgba(var(--v-theme-on-surface), 0.08);
}
</style>
