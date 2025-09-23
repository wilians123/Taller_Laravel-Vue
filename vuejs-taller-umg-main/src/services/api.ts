import axios from 'axios'

// Detectar el tenant desde el hostname del frontend
const getTenantFromHostname = (): string | null => {
  const hostname = window.location.hostname
  
  // Si es localhost sin subdominio, no hay tenant
  if (hostname === 'localhost' || hostname === '127.0.0.1') {
    return null
  }
  
  // Extraer subdominio 
  const match = hostname.match(/^([^.]+)\.localhost$/)
  return match ? match[1] : null
}

// Configurar la baseURL según el tenant
const getBaseURL = (): string => {
  const tenant = getTenantFromHostname()
  
  if (tenant) {
    // Si hay tenant, usar las rutas con parámetro tenant
    return 'http://127.0.0.1:8000/api/tenant/' + tenant
  }
  // Sin tenant, usar la URL normal
  return 'http://127.0.0.1:8000/api' 
}

const api = axios.create({
  baseURL: getBaseURL(),
})

// Log para debug
console.log('API configurado para:', {
  hostname: window.location.hostname,
  tenant: getTenantFromHostname(),
  baseURL: getBaseURL()
})

api.interceptors.request.use((config) => {
  // rutas que no deben llevar token
  const noAuthEndpoints = ['/login', '/register']
  if (!noAuthEndpoints.includes(config.url || '')) {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
      console.log('Token agregado a request:', token.substring(0, 20) + '...')
    }
  }
  console.log('Request config:', config.url, config.method)
  return config
})

// Interceptor para manejar errores de conexión entre tenants
api.interceptors.response.use(
  (response) => {
    console.log('Response exitoso:', response.config.url)
    return response
  },
  (error) => {
    console.log('Error en response:', error.response?.status, error.config?.url)
    if (error.response?.status === 401) {
      // Token inválido para este tenant, limpiar y redirigir al login
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
