/* eslint-disable camelcase -- alineado con AppointmentRequest / API */
import { getMockCustomerDetail, mockAdminCustomerRows } from '@/data/mockAdminCustomers'

/** Servicios para selector (hasta exista GET /api/services) */
export const mockAppointmentServices = [
  { id: 1, name: 'Baño completo', base_duration_min: 60, base_price: 35 },
  { id: 2, name: 'Corte + baño', base_duration_min: 90, base_price: 45 },
  { id: 3, name: 'Spa hidratante', base_duration_min: 75, base_price: 55 },
  { id: 4, name: 'Corte higiénico', base_duration_min: 45, base_price: 25 },
]

/** Empleados groomers (hasta exista endpoint dedicado) */
export const mockAppointmentEmployees = [
  { id: 1, name: 'Laura Méndez' },
  { id: 2, name: 'Diego Ríos' },
  { id: 3, name: 'Patricia Solís' },
]

/**
 * Citas del calendario con start_time / end_time (Y-m-d H:i) y status del backend.
 */
export const mockCalendarAppointments = [
  {
    id: 1,
    pet_id: 1,
    service_id: 2,
    employee_id: 1,
    pet_name: 'Luna',
    service_name: 'Corte + baño',
    start_time: '2026-05-15 09:00',
    end_time: '2026-05-15 10:30',
    status: 'confirmed',
  },
  {
    id: 2,
    pet_id: 2,
    service_id: 3,
    employee_id: 2,
    pet_name: 'Kai',
    service_name: 'Spa hidratante',
    start_time: '2026-05-15 11:30',
    end_time: '2026-05-15 12:45',
    status: 'pending',
  },
  {
    id: 3,
    pet_id: 3,
    service_id: 4,
    employee_id: 1,
    pet_name: 'Monty',
    service_name: 'Corte higiénico',
    start_time: '2026-05-15 14:00',
    end_time: '2026-05-15 14:50',
    status: 'finished',
  },
  {
    id: 4,
    pet_id: 1,
    service_id: 1,
    employee_id: 3,
    pet_name: 'Luna',
    service_name: 'Baño completo',
    start_time: '2026-05-15 16:00',
    end_time: '2026-05-15 17:00',
    status: 'cancelled',
  },
]

/** Colores Vuetify por estado de cita */
export const appointmentStatusColors = {
  pending: 'warning',
  confirmed: 'info',
  cancelled: 'error',
  finished: 'success',
  in_progress: 'primary',
  paid: 'success',
}

export const appointmentStatusLabels = {
  pending: 'Pendiente',
  confirmed: 'Confirmada',
  cancelled: 'Cancelada',
  finished: 'Finalizada',
  in_progress: 'En curso',
  paid: 'Pagada',
}

/**
 * Clientes con mascotas para el formulario.
 * Estructura lista para mapear respuesta de GET /api/customers.
 */
export function getMockCustomersWithPets() {
  return mockAdminCustomerRows
    .filter(row => row.status === 'active')
    .map(row => {
      const detail = getMockCustomerDetail(row.id)

      return {
        id: row.id,
        full_name: row.fullName,
        ci: row.ci,
        phone: row.phone,
        pets: (detail?.pets ?? []).map(p => ({
          id: p.id,
          name: p.name,
          customer_id: p.customer_id,
          weight_kg: p.weight_kg,
        })),
      }
    })
}
