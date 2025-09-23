import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/usuarios' },
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue') },
    { path: '/usuarios', name: 'usuarios', component: () => import('@/views/HomeView.vue'), meta: { requiresAuth: true } },
    { path: '/usuarios/nuevo', name: 'usuarios-nuevo', component: () => import('@/views/UserForm.vue'), meta: { requiresAuth: true } },
    { path: '/usuarios/:id/editar', name: 'usuarios-editar', component: () => import('@/views/UserForm.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/tareas', name: 'tareas', component: () => import('@/views/TareasView.vue'), meta: { requiresAuth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/usuarios' },
  ],
})

router.beforeEach(async (to) => {
  const token = localStorage.getItem('token')
  const user = localStorage.getItem('user')
  
  console.log('Router guard:', {
    to: to.name,
    hasToken: !!token,
    hasUser: !!user,
    requiresAuth: to.meta.requiresAuth
  })
  
  if (to.meta.requiresAuth) {
    if (!token || !user) {
      console.log('No token or user, redirecting to login')
      return { name: 'login', query: { redirect: to.fullPath } }
    }
    
  }
  
  if (to.name === 'login' && token && user) {
    console.log('Already logged in, redirecting to usuarios')
    return { name: 'usuarios' }
  }
  
  return true
})

// Funcion para obtener la URL del API segun el tenant
function getApiUrl(): string {
  const hostname = window.location.hostname
  
  if (hostname === 'localhost' || hostname === '127.0.0.1') {
    return 'http://127.0.0.1:8000/api'
  }
  
  const match = hostname.match(/^([^.]+)\.localhost$/)
  if (match) {
    return `http://${match[1]}.localhost:8000/api`
  }
  
  return 'http://127.0.0.1:8000/api'
}

export default router