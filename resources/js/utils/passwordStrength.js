/** @returns {number} 0–5 */
export function passwordStrengthScore(password) {
  if (!password || typeof password !== 'string') {
    return 0
  }

  let score = 0
  if (password.length >= 8)
    score++
  if (/[a-z]/u.test(password))
    score++
  if (/[A-Z]/u.test(password))
    score++
  if (/\d/.test(password))
    score++
  if (/[^\p{L}0-9]/u.test(password))
    score++

  return score
}

export function passwordStrengthLabel(score) {
  if (score <= 1)
    return 'Muy débil'
  if (score === 2)
    return 'Débil'
  if (score === 3)
    return 'Media'
  if (score === 4)
    return 'Buena'
  
  return 'Excelente'
}

/** Lista de reglas incumplidas (alineado con App\Rules\StrongPassword) */
export function strongPasswordViolations(password) {
  if (!password || typeof password !== 'string') {
    return ['Introduce una contraseña.']
  }

  const v = []
  if (password.length < 8)
    v.push('Al menos 8 caracteres')
  if (!/[a-z]/u.test(password))
    v.push('Al menos una minúscula')
  if (!/[A-Z]/u.test(password))
    v.push('Al menos una mayúscula')
  if (!/\d/.test(password))
    v.push('Al menos un número')
  if (!/[^\p{L}0-9]/u.test(password))
    v.push('Al menos un símbolo')

  return v
}

/** @returns {boolean} */
export function isStrongPassword(password) {
  return strongPasswordViolations(password).length === 0
}
