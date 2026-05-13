const apiOrigin = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

function apiUrl(path) {
  const clean = String(path).replace(/^\//, '')

  return apiOrigin ? `${apiOrigin}/api/${clean}` : `/api/${clean}`
}

/** @returns {Promise<Response>} */
export function apiFetch(path, options = {}, token = '') {
  const url = apiUrl(path)

  const headers = {
    Accept: 'application/json',
    ...(options.headers || {}),
  }

  const hasBody = options.body !== undefined && options.body !== null
  if (hasBody && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json'
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  return fetch(url, { ...options, headers })
}
