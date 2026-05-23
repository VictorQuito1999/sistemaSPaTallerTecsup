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
      {
        path: 'perfil-mascotas',
        name: 'cliente-perfil-mascotas',
        component: () => import('@/pages/cliente/ClientePerfilMascotas.vue'),
      },
      {
        path: 'reservar',
        name: 'cliente-reservar',
        component: () => import('@/pages/cliente/CustomerBookingView.vue'),
      },
    ],
  },
  {
    path: '/empleado',
    component: () => import('@/layouts/staff.vue'),
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
      {
        path: 'agenda-global',
        name: 'empleado-agenda-global',
        component: () => import('@/pages/empleado/EmpleadoAgendaView.vue'),
        meta: { staffRoles: ['receptionist'] },
      },
      {
        path: 'clientes',
        name: 'empleado-clientes',
        component: () => import('@/pages/empleado/EmpleadoCustomersView.vue'),
        meta: { staffRoles: ['receptionist'] },
      },
      {
        path: 'clientes/:id',
        name: 'empleado-clientes-detail',
        component: () => import('@/pages/empleado/EmpleadoCustomerDetailView.vue'),
        meta: { staffRoles: ['receptionist'] },
      },
      {
        path: 'shop',
        name: 'empleado-shop',
        component: () => import('@/pages/admin/StoreView.vue'),
        meta: { staffRoles: ['receptionist'] },
      },
      {
        path: 'checkout/:appointment_id',
        name: 'empleado-checkout',
        component: () => import('@/pages/admin/CheckoutView.vue'),
        meta: { staffRoles: ['receptionist'] },
      },
    ],
  },
  {
    path: '/groomer',
    component: () => import('@/layouts/staff.vue'),
    meta: { requiresStaff: true },
    children: [
      {
        path: 'today',
        name: 'groomer-today',
        component: () => import('@/pages/groomer/GroomerTodayView.vue'),
        meta: { staffRoles: ['groomer'] },
      },
      {
        path: 'service/:id',
        name: 'groomer-service-console',
        component: () => import('@/pages/groomer/GroomingConsoleView.vue'),
        meta: { staffRoles: ['groomer'] },
      },
      {
        path: 'appointments/:id/pet',
        name: 'groomer-appointment-pet',
        component: () => import('@/pages/groomer/GroomerPetView.vue'),
        meta: { staffRoles: ['groomer'] },
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
    meta: { requiresAuth: true, role: ['admin', 'receptionist'] },
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
        meta: { requiresAdmin: true },
      },
      {
        path: 'customers',
        name: 'admin-customers-index',
        component: () => import('@/pages/admin/CustomerIndexView.vue'),
      },
      {
        path: 'customers/:id',
        name: 'admin-customers-detail',
        component: () => import('@/pages/admin/AdminCustomerDetailView.vue'),
      },
      {
        path: 'pets',
        name: 'admin-pets',
        component: () => import('@/pages/admin/AdminPetsView.vue'),
      },
      {
        path: 'species-categories',
        name: 'admin-species-categories',
        component: () => import('@/pages/admin/SpeciesCategoriesView.vue'),
        meta: { requiresAdmin: true },
      },
      {
        path: 'breeds',
        name: 'admin-breeds',
        component: () => import('@/pages/admin/BreedsView.vue'),
        meta: { requiresAdmin: true },
      },
      {
        path: 'services',
        name: 'admin-services',
        component: () => import('@/pages/admin/ServicesView.vue'),
        meta: { requiresAdmin: true },
      },

      {
        path: 'calendar',

        name: 'admin-calendar',
        component: () => import('@/pages/admin/CalendarDashboard.vue'),
      },
      {
        path: 'inventory',
        name: 'admin-inventory',
        component: () => import('@/pages/admin/InventoryView.vue'),
        meta: { requiresAdmin: true },
      },
      {
        path: 'reports',
        name: 'admin-reports',
        component: () => import('@/pages/admin/ReportsView.vue'),
        meta: { requiresAdmin: true },
      },
      {
        path: 'shop',
        name: 'admin-shop',
        component: () => import('@/pages/admin/StoreView.vue'),
      },
      {
        path: 'checkout/:appointment_id',
        name: 'admin-checkout',
        component: () => import('@/pages/admin/CheckoutView.vue'),
      },
      {
        path: 'logs-trazabilidad',
        name: 'admin-logs-trazabilidad',
        component: () => import('@/pages/admin/LogsTrazabilidad.vue'),
        meta: { requiresAdmin: true },
      },
      {
        path: 'settings',
        name: 'admin-settings',
        component: () => import('@/pages/admin/Settings.vue'),
        meta: { requiresAdmin: true },
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
