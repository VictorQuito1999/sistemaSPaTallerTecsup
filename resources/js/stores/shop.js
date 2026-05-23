import { defineStore } from 'pinia'

const STORAGE_KEY = 'petspa_shop_cart'

function loadPersisted() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return { items: [], checkoutAppointmentId: null }
    }
    const parsed = JSON.parse(raw)

    return {
      items: Array.isArray(parsed.items) ? parsed.items : [],
      checkoutAppointmentId: parsed.checkoutAppointmentId ?? null,
    }
  }
  catch {
    return { items: [], checkoutAppointmentId: null }
  }
}

function persist(state) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify({
    items: state.items,
    checkoutAppointmentId: state.checkoutAppointmentId,
  }))
}

export const useShopStore = defineStore('shop', {
  state: () => ({
    ...loadPersisted(),
  }),

  getters: {
    itemCount: state => state.items.reduce((sum, i) => sum + i.quantity, 0),
    subtotal: state => state.items.reduce((sum, i) => sum + i.price * i.quantity, 0),
    isEmpty: state => state.items.length === 0,
  },

  actions: {
    persistToStorage() {
      persist(this.$state)
    },

    setCheckoutAppointmentId(id) {
      this.checkoutAppointmentId = id ? Number(id) : null
      this.persistToStorage()
    },

    addItem(product) {
      const stock = Number(product.stock ?? 0)
      if (stock <= 0) {
        return false
      }

      const existing = this.items.find(i => i.productId === product.id)
      if (existing) {
        if (existing.quantity >= stock) {
          return false
        }
        existing.quantity += 1
      }
      else {
        this.items.push({
          productId: product.id,
          name: product.name,
          price: Number(product.price ?? 0),
          stock,
          sku: product.sku ?? product.code ?? '',
          quantity: 1,
        })
      }

      this.persistToStorage()
      
      return true
    },

    incrementItem(productId) {
      const item = this.items.find(i => i.productId === productId)
      if (!item || item.quantity >= item.stock) {
        return
      }
      item.quantity += 1
      this.persistToStorage()
    },

    decrementItem(productId) {
      const item = this.items.find(i => i.productId === productId)
      if (!item) {
        return
      }
      if (item.quantity <= 1) {
        this.removeItem(productId)
        
        return
      }
      item.quantity -= 1
      this.persistToStorage()
    },

    removeItem(productId) {
      this.items = this.items.filter(i => i.productId !== productId)
      this.persistToStorage()
    },

    clearCart() {
      this.items = []
      this.persistToStorage()
    },

    /** Guarda carrito y devuelve ruta de checkout (admin o empleado). */
    prepareCheckoutRoute(isAdminArea) {
      this.persistToStorage()

      const base = isAdminArea ? '/admin' : '/empleado'
      const id = this.checkoutAppointmentId ?? 'retail'

      return `${base}/checkout/${id}`
    },
  },
})
