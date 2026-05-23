/** Mock operativo: KPIs, analítica, inventario, checkout, consola groomer */

export const mockBusinessKpis = {
  revenueToday: 1248.5,
  appointmentsCompleted: 18,
  criticalStockItems: 4,
}

/** 7 valores para sparkline ingresos semanales */
export const mockWeeklyRevenue = [820, 910, 780, 1050, 1120, 980, mockBusinessKpis.revenueToday]

export const mockTopServicesWeekly = [
  { name: 'Baño completo', count: 42 },
  { name: 'Corte + baño', count: 31 },
  { name: 'Spa hidratante', count: 19 },
]

export const mockGroomerPerformance = [
  { id: 1, name: 'Laura Méndez', appointmentsDone: 14, suppliesUsedUnits: 28 },
  { id: 2, name: 'Diego Ríos', appointmentsDone: 11, suppliesUsedUnits: 22 },
  { id: 3, name: 'Patricia Solís', appointmentsDone: 9, suppliesUsedUnits: 17 },
]

export const mockInventoryProducts = [
  { id: 1, sku: 'SHP-001', name: 'Shampoo hipoalergénico 500ml', category: 'Higiene', stock: 8, minStock: 12, unit: 'botella', price: 14.5 },
  { id: 2, sku: 'COND-02', name: 'Acondicionador premium 1L', category: 'Higiene', stock: 22, minStock: 10, unit: 'botella', price: 18 },
  { id: 3, sku: 'NAV-10', name: 'Navajas desechables (caja 20)', category: 'Herramientas', stock: 3, minStock: 5, unit: 'caja', price: 9.5 },
  { id: 4, sku: 'ESP-44', name: 'Espuma limpiadora orejas', category: 'Higiene', stock: 15, minStock: 8, unit: 'frasco', price: 11 },
  { id: 5, sku: 'TOA-RL', name: 'Toallas rollo industrial', category: 'Consumibles', stock: 2, minStock: 6, unit: 'rollo', price: 22 },
]

/** Catálogo tienda (venta al público en recepción) */
export const mockShopProducts = [
  { id: 101, sku: 'JUG-01', name: 'Pelota interactiva', category: 'Juguetes', stock: 24, price: 8.5, is_active: true },
  { id: 102, sku: 'JUG-02', name: 'Hueso de goma', category: 'Juguetes', stock: 18, price: 6.0, is_active: true },
  { id: 103, sku: 'COM-01', name: 'Snack dental (bolsa 200g)', category: 'Comida', stock: 30, price: 12.0, is_active: true },
  { id: 104, sku: 'COM-02', name: 'Alimento premium 3kg', category: 'Comida', stock: 12, price: 28.5, is_active: true },
  { id: 105, sku: 'ACC-01', name: 'Correa ajustable', category: 'Accesorios', stock: 15, price: 14.0, is_active: true },
  { id: 106, sku: 'ACC-02', name: 'Arnés reflectivo', category: 'Accesorios', stock: 9, price: 19.5, is_active: true },
  { id: 107, sku: 'ACC-03', name: 'Plato antidesliz', category: 'Accesorios', stock: 20, price: 7.5, is_active: true },
  { id: 108, sku: 'HIG-01', name: 'Toallitas húmedas (pack 80)', category: 'Higiene', stock: 25, price: 9.0, is_active: true },
]

export const mockCheckoutByAppointmentId = {
  501: {
    appointmentId: 501,
    customerName: 'María González',
    petName: 'Luna',
    serviceLine: 'Baño completo + corte mediano',
    servicePrice: 45,
    extraSupplies: [
      { name: 'Mascarilla hidratante', price: 8 },
      { name: 'Perfume hypo', price: 3.5 },
    ],
  },
  502: {
    appointmentId: 502,
    customerName: 'Carlos Pérez',
    petName: 'Rocky',
    serviceLine: 'Solo baño express',
    servicePrice: 22,
    extraSupplies: [],
  },
}

export function getMockCheckout(appointmentId) {
  return mockCheckoutByAppointmentId[Number(appointmentId)] ?? {
    appointmentId: Number(appointmentId),
    customerName: 'Cliente demo',
    petName: 'Mascota demo',
    serviceLine: 'Servicio estándar',
    servicePrice: 35,
    extraSupplies: [{ name: 'Cepillo desenredante', price: 5 }],
  }
}

const defaultSupplies = [
  { id: 's1', name: 'Shampoo hipoalergénico', default: false },
  { id: 's2', name: 'Acondicionador', default: false },
  { id: 's3', name: 'Spray desenredante', default: false },
  { id: 's4', name: 'Cologne spa', default: false },
]

/** Consola de trabajo por id de servicio/cita */
export const mockGroomingServiceById = {
  101: {
    id: 101,
    petName: 'Luna',
    serviceName: 'Baño completo + corte',
    estimatedMinutes: 90,
    checklist: [
      { id: 'c1', label: 'Revisar piel y orejas', required: true },
      { id: 'c2', label: 'Baño con shampoo hipoalergénico', required: true },
      { id: 'c3', label: 'Secado y cepillado', required: true },
      { id: 'c4', label: 'Corte según ficha del cliente', required: true },
      { id: 'c5', label: 'Foto final y nota en sistema', required: true },
    ],
    suppliesCatalog: defaultSupplies,
  },
}

export function getMockGroomingService(id) {
  return mockGroomingServiceById[Number(id)] ?? {
    id: Number(id),
    petName: 'Mascota',
    serviceName: 'Servicio grooming',
    estimatedMinutes: 60,
    checklist: [
      { id: 'x1', label: 'Saludar al cliente y pesar', required: true },
      { id: 'x2', label: 'Completar servicio según orden', required: true },
      { id: 'x3', label: 'Entregar mascota en recepción', required: true },
    ],
    suppliesCatalog: defaultSupplies,
  }
}

export const mockReceptionistAgendaSlots = [
  { time: '09:00', pet: 'Luna', service: 'Baño + corte', groomer: 'Laura', status: 'confirmed', checkoutId: 501 },
  { time: '10:30', pet: 'Rocky', service: 'Baño', groomer: 'Diego', status: 'in_progress', checkoutId: 502 },
  { time: '12:00', pet: 'Coco', service: 'Corte higiénico', groomer: 'Patricia', status: 'confirmed', checkoutId: 501 },
  { time: '15:00', pet: 'Kai', service: 'Spa', groomer: 'Laura', status: 'pending', checkoutId: 502 },
]

export const mockStaffCustomers = [
  { id: 101, fullName: 'María González', ci: 'V-18.765.432', phone: '+58 412-5550199', petsCount: 3 },
  { id: 102, fullName: 'Carlos Pérez', ci: 'E-22.334.556', phone: '+58 414-2008899', petsCount: 1 },
]

export const mockGroomerTodayAppointments = [
  { id: 101, time: '09:00', petName: 'Luna', service: 'Baño + corte', customer: 'María G.', consoleServiceId: 101 },
  { id: 102, time: '11:30', petName: 'Kai', service: 'Spa hidratante', customer: 'María G.', consoleServiceId: 101 },
  { id: 103, time: '15:00', petName: 'Monty', service: 'Consulta uñas', customer: 'María G.', consoleServiceId: 101 },
]
