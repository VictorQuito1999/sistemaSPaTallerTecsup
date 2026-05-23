/** Etiquetas UI para enum temperament (pets.temperament) */
export const temperamentLabel = {
  calm: 'Calm',
  nervous: 'Nervous',
  aggressive: 'Aggressive',
  friendly: 'Friendly',
}

/** Resalte visual de peso (>25 kg y >40 kg) */
export function weightTone(kg) {
  const n = Number(kg)
  if (!Number.isFinite(n)) {
    return { chip: null, textClass: 'text-medium-emphasis', chipColor: 'default' }
  }
  if (n > 40) {
    return {
      chip: 'Alto peso',
      textClass: 'text-error font-weight-black',
      chipColor: 'error',
    }
  }
  if (n > 25) {
    return {
      chip: 'Peso elevado',
      textClass: 'text-warning font-weight-bold',
      chipColor: 'warning',
    }
  }

  return { chip: null, textClass: 'text-medium-emphasis', chipColor: 'default' }
}

export function wMeta(pet) {
  return weightTone(pet?.weight_kg)
}
