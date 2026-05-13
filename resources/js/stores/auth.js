import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('petspa_user') || 'null'),
    token: localStorage.getItem('petspa_token') || '',
  }),
  getters: {
    isAdminSession: state => Boolean(state.token && state.user?.role === 'admin'),
    isStaffSession: state => Boolean(
      state.token && ['groomer', 'receptionist'].includes(state.user?.role),
    ),

    /** Cliente con sesión sin Bearer (OTP / login cliente). */
    isCustomerLoggedIn: state => Boolean(
      state.user && !state.token && state.user.role === 'customer',
    ),
    isCustomerBearerSession: state => Boolean(
      state.token && state.user?.role === 'customer',
    ),
    isAuthenticated: state => Boolean(state.user),
    isAdmin: state => state.user?.role === 'admin',
  },
  actions: {
    setUser(user) {
      this.user = user
      localStorage.setItem('petspa_user', JSON.stringify(user))
    },
    setBearerSession(user, token) {
      this.user = user
      this.token = token
      localStorage.setItem('petspa_user', JSON.stringify(user))
      localStorage.setItem('petspa_token', token)
    },
    setAdminSession(user, token) {
      this.setBearerSession(user, token)
    },
    clearBearerToken() {
      this.token = ''
      localStorage.removeItem('petspa_token')
    },
    logout() {
      this.user = null
      this.token = ''
      localStorage.removeItem('petspa_user')
      localStorage.removeItem('petspa_token')
    },
  },
})
