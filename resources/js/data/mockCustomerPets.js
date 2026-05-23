/* eslint-disable camelcase -- alineado con columnas Postgres (pets, breeds, customers) */
/**
 * Datos de demostración para la UI de perfil cliente / mascotas.
 * Estructura alineada con migrations: pets, breeds → species_categories.
 */

export const mockSpeciesCategories = [
  { id: 1, key: 'companion', label: 'Companion' },
  { id: 2, key: 'reptile', label: 'Reptile' },
  { id: 3, key: 'bird', label: 'Bird' },
  { id: 4, key: 'small_mammal', label: 'Small mammal' },
]

export const mockBreeds = [
  { id: 1, category_id: 1, name: 'Golden Retriever' },
  { id: 2, category_id: 1, name: 'French Bulldog' },
  { id: 3, category_id: 1, name: 'Border Collie' },
  { id: 4, category_id: 2, name: 'Leopard Gecko' },
  { id: 5, category_id: 2, name: 'Ball Python' },
  { id: 6, category_id: 3, name: 'Cockatiel' },
  { id: 7, category_id: 3, name: 'Budgerigar' },
  { id: 8, category_id: 4, name: 'Netherland Dwarf' },
]

export const mockCustomerProfile = {
  id: 101,
  user_id: 55,
  ci: 'V-18.765.432',
  status: 1,
  user: {
    first_name: 'María',
    last_name: 'González',
    email: 'maria.gonzalez@example.com',
    phone: '+58 412-5550199',
  },
}

export const mockPets = [
  {
    id: 1,
    customer_id: 101,
    breed_id: 1,
    name: 'Luna',
    gender: 'female',
    birth_date: '2021-03-12',
    weight_kg: 12.4,
    allergies: 'Pollo (digestión sensible)',
    temperament: 'friendly',
    is_active: true,
    created_at: '2026-05-01T10:00:00.000000Z',
    updated_at: '2026-05-08T14:22:00.000000Z',
    breed: mockBreeds[0],
    category: mockSpeciesCategories[0],
  },
  {
    id: 2,
    customer_id: 101,
    breed_id: 3,
    name: 'Kai',
    gender: 'male',
    birth_date: '2019-07-01',
    weight_kg: 28.75,
    allergies: null,
    temperament: 'calm',
    is_active: true,
    created_at: '2026-04-15T09:30:00.000000Z',
    updated_at: '2026-05-02T11:05:00.000000Z',
    breed: mockBreeds[2],
    category: mockSpeciesCategories[0],
  },
  {
    id: 3,
    customer_id: 101,
    breed_id: 5,
    name: 'Monty',
    gender: 'male',
    birth_date: '2022-11-20',
    weight_kg: 42.1,
    allergies: 'Ninguna conocida',
    temperament: 'nervous',
    is_active: true,
    created_at: '2026-03-20T16:45:00.000000Z',
    updated_at: '2026-05-10T08:10:00.000000Z',
    breed: mockBreeds[4],
    category: mockSpeciesCategories[1],
  },
]
