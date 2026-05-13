export const routes = [
  {
    path: '/empleado/activar',
    component: () => import('@/layouts/blank.vue'),
    meta: { public: true },
    children: [
      {
        path: '',
        name: 'empleado-activar',
        component: () => import('@/pages/empleado/EmpleadoActivar.vue'),
      },
    ],
  },
  {
    path: '/cliente',
    component: () => import('@/layouts/blank.vue'),
    meta: { requiresCustomer: true },
    children: [
      {
        path: '',
        redirect: '/cliente/dashboard',
      },
      {
        path: 'dashboard',
        name: 'cliente-dashboard',
        component: () => import('@/pages/cliente/CustomerDashboard.vue'),
      },
    ],
  },
  {
    path: '/empleado',
    component: () => import('@/layouts/blank.vue'),
    meta: { requiresStaff: true },
    children: [
      {
        path: '',
        redirect: '/empleado/dashboard',
      },
      {
        path: 'dashboard',
        name: 'empleado-dashboard',
        component: () => import('@/pages/empleado/EmployeeDashboard.vue'),
      },
    ],
  },
  {
    path: '/',
    component: () => import('@/layouts/blank.vue'),
    children: [
      {
        path: '',
        name: 'landing',
        component: () => import('@/pages/landing.vue'),
      },
      {
        path: 'login',
        name: 'employee-login',
        component: () => import('@/pages/employee-login.vue'),
        meta: { guestOnly: true, employeeLogin: true },
      },
      {
        path: 'cliente/login',
        name: 'customer-login',
        component: () => import('@/pages/customer-login.vue'),
        meta: { guestOnly: true, customerLogin: true },
      },
      {
        path: 'register',
        name: 'customer-register',
        component: () => import('@/pages/customer-register.vue'),
        meta: { guestOnly: true },
      },
      {
        path: 'verify-email',
        name: 'verify-email',
        component: () => import('@/pages/verify-email.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'change-password',
        name: 'change-password',
        component: () => import('@/pages/change-password.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
  {
    path: '/admin',
    component: () => import('@/layouts/default.vue'),
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      {
        path: '',
        name: 'admin-dashboard',
        component: () => import('@/pages/dashboard.vue'),
      },
      {
        path: 'employees',
        name: 'admin-employees',
        component: () => import('@/pages/admin/employees.vue'),
      },
      {
        path: 'logs-trazabilidad',
        name: 'admin-logs-trazabilidad',
        component: () => import('@/pages/admin/LogsTrazabilidad.vue'),
      },

      {
      path: 'settings',
      name: 'admin-settings',
      component: () => import('@/pages/admin/Settings.vue'),
    },
    
    ],
  },
  {
    path: '/admin/login',
    component: () => import('@/layouts/blank.vue'),
    children: [
      {
        path: '',
        name: 'admin-login',
        component: () => import('@/pages/admin-login.vue'),
        meta: { guestOnly: true, guestAdminOnly: true },
      },
      {
        path: '/:pathMatch(.*)*',
        component: () => import('@/pages/[...error].vue'),
      },
    ],
  },
]
