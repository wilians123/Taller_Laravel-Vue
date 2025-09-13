import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api',
})


api.interceptors.request.use((config) => {
  // rutas que no deben llevar token
  const noAuthEndpoints = ['/login', '/register']

  if (!noAuthEndpoints.includes(config.url || '')) {
    const token = localStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

export default api
