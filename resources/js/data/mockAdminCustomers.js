/* eslint-disable camelcase -- columnas / API futura */
import { mockCustomerProfile, mockPets } from '@/data/mockCustomerPets'

/** Listado admin: clientes + conteo mascotas (mock) */
export const mockAdminCustomerRows = [
  {
    id: 101,
    fullName: 'María González',
    ci: 'V-18.765.432',
    phone: '+58 412-5550199',
    petsCount: 3,
    status: 'active',
    user: mockCustomerProfile.user,
  },
  {
    id: 102,
    fullName: 'Carlos Pérez',
    ci: 'E-22.334.556',
    phone: '+58 414-2008899',
    petsCount: 1,
    status: 'active',
    user: { first_name: 'Carlos', last_name: 'Pérez', email: 'carlos.p@example.com', phone: '+58 414-2008899' },
  },
  {
    id: 103,
    fullName: 'Ana Rivas',
    ci: 'V-12.100.200',
    phone: '+58 424-7110033',
    petsCount: 0,
    status: 'blocked',
    user: { first_name: 'Ana', last_name: 'Rivas', email: 'ana.rivas@example.com', phone: '+58 424-7110033' },
  },
  {
    id: 104,
    fullName: 'Luis Méndez',
    ci: 'V-05.889.001',
    phone: '+58 416-9900122',
    petsCount: 2,
    status: 'active',
    user: { first_name: 'Luis', last_name: 'Méndez', email: 'luis.m@example.com', phone: '+58 416-9900122' },
  },
]

export function getMockCustomerDetail(id) {
  const numId = Number(id)
  if (numId === 101) {
    return {
      customer: {
        ...mockCustomerProfile,
        user: { ...mockCustomerProfile.user },
      },
      pets: [...mockPets],
    }
  }

  const row = mockAdminCustomerRows.find(r => r.id === numId)
  if (!row) {
    return null
  }

  return {
    customer: {
      id: row.id,
      user_id: row.id + 900,
      ci: row.ci,
      status: row.status === 'blocked' ? 0 : 1,
      user: {
        first_name: row.user.first_name,
        last_name: row.user.last_name,
        email: row.user.email,
        phone: row.phone,
      },
    },
    pets: numId === 102
      ? [mockPets[0]]
      : numId === 104
        ? mockPets.slice(0, 2)
        : [],
  }
}

/** Cita mock → mascota para vista groomer (solo lectura) */
export const mockAppointmentPetContext = {
  1: {
    appointmentId: 1,
    scheduledAt: '2026-05-13T09:00:00',
    serviceName: 'Baño completo + corte',
    pet: mockPets[0],
    ownerName: 'María González',
  },
  2: {
    appointmentId: 2,
    scheduledAt: '2026-05-13T11:30:00',
    serviceName: 'Spa hidratante',
    pet: mockPets[1],
    ownerName: 'María González',
  },
  3: {
    appointmentId: 3,
    scheduledAt: '2026-05-13T15:00:00',
    serviceName: 'Consulta + uñas',
    pet: mockPets[2],
    ownerName: 'María González',
  },
}

export function getMockAppointmentPet(appointmentId) {
  return mockAppointmentPetContext[Number(appointmentId)] ?? null
}

/** @deprecated Usar mockCalendarAppointments (start_time / end_time) */
export const mockUpcomingAppointments = [
  {
    id: 1,
    pet_name: 'Luna',
    service_name: 'Baño completo + corte',
    start_time: '2026-05-15 09:00',
    end_time: '2026-05-15 10:30',
    status: 'confirmed',
  },
  {
    id: 2,
    pet_name: 'Kai',
    service_name: 'Spa hidratante',
    start_time: '2026-05-15 11:30',
    end_time: '2026-05-15 12:45',
    status: 'pending',
  },
  {
    id: 4,
    pet_name: 'Monty',
    service_name: 'Corte higiénico',
    start_time: '2026-05-15 14:00',
    end_time: '2026-05-15 14:50',
    status: 'finished',
  },
]
