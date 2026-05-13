import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { routes } from './routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async to => {
  if (to.matched.some(record => record.meta?.public)) {
    return true
  }

  const auth = useAuthStore()

  // Google OAuth: fragment with token (antes de requiresCustomer)
  if (typeof window !== 'undefined' && to.hash?.includes('token=')) {
    const params = new URLSearchParams(to.hash.replace(/^#/, ''))
    const oauthToken = params.get('token')
    if (oauthToken && to.path.startsWith('/cliente')) {
      try {
        const res = await fetch('/api/auth/me', {
          headers: { Authorization: `Bearer ${oauthToken}`, Accept: 'application/json' },
        })

        if (res.ok) {
          const user = await res.json()

          auth.setBearerSession(user, oauthToken)

          return { path: to.path, query: to.query, replace: true, hash: '' }
        }
      }
      catch {
        /* ignorar */
      }
    }
  }

  const employeeLogin = to.matched.some(record => record.meta?.employeeLogin)
  const customerLogin = to.matched.some(record => record.meta?.customerLogin)

  if (employeeLogin) {
    if (auth.user?.role === 'customer' && (auth.isCustomerBearerSession || auth.isCustomerLoggedIn)) {
      return '/cliente/dashboard'
    }
    if (auth.isStaffSession) {
      return '/empleado/dashboard'
    }
    if (auth.isAdminSession) {
      return '/admin'
    }
  }

  if (customerLogin) {
    if (auth.isCustomerBearerSession) {
      return '/cliente/dashboard'
    }
    if (auth.isAdminSession) {
      return '/admin'
    }
    if (auth.isStaffSession) {
      return '/empleado/dashboard'
    }
    if (auth.isCustomerLoggedIn) {
      return '/'
    }
  }

  const requiresCustomer = to.matched.some(record => record.meta?.requiresCustomer)
  const requiresStaff = to.matched.some(record => record.meta?.requiresStaff)
  const requiresAuth = to.matched.some(record => record.meta?.requiresAuth)
  const requiresAdminRole = to.matched.some(record => record.meta?.role === 'admin')
  const guestOnly = to.matched.some(record => record.meta?.guestOnly)
  const guestAdminOnly = to.matched.some(record => record.meta?.guestAdminOnly)

  const goingAdminArea = to.path.startsWith('/admin') && !to.path.startsWith('/admin/login')
  const goingEmpleadoArea = to.path.startsWith('/empleado') && !to.matched.some(r => r.meta?.public)

  if ((goingAdminArea || goingEmpleadoArea) && auth.user?.role === 'customer') {
    return '/cliente/dashboard'
  }

  if (requiresCustomer) {
    if (auth.isStaffSession) {
      return '/empleado/dashboard'
    }
    if (auth.isAdminSession) {
      return '/admin'
    }
    if (!auth.isCustomerBearerSession) {
      return '/cliente/login'
    }
  }

  if (requiresStaff && !auth.isStaffSession) {
    if (auth.user?.role === 'customer') {
      return '/cliente/dashboard'
    }

    return '/login'
  }

  if (requiresAuth) {
    if (requiresAdminRole) {
      if (!auth.isAdminSession) {
        if (auth.user?.role === 'customer') {
          return '/cliente/dashboard'
        }

        return '/admin/login'
      }
    }
    else if (!auth.isAuthenticated) {
      return '/cliente/login'
    }
  }

  if (guestAdminOnly && auth.isAdminSession) {
    return '/admin'
  }

  if (guestOnly && !guestAdminOnly) {
    if (auth.isAdminSession) {
      return '/admin'
    }
    if (auth.isStaffSession) {
      return '/empleado/dashboard'
    }
    if (
      auth.isCustomerBearerSession
      && !employeeLogin
      && !customerLogin
    ) {
      return '/cliente/dashboard'
    }
    if (
      auth.isCustomerLoggedIn
      && !employeeLogin
      && !customerLogin
    ) {
      return '/'
    }
  }

  return true
})

export default function (app) {
  app.use(router)
}
export { router }
