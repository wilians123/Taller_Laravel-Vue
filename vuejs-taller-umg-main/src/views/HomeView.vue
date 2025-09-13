<template>
  <v-container fluid class="pa-0">
    <v-row no-gutters>
      <!-- Sidebar moderno -->
      <v-col cols="12" md="3">
        <v-navigation-drawer permanent class="elevation-2">
          <v-card flat class="pa-4 bg-gradient-primary">
            <div class="text-white text-center">
              <v-avatar size="64" class="mb-3" color="white">
                <v-icon size="32" color="primary">mdi-account-circle</v-icon>
              </v-avatar>
              <div class="text-h6 font-weight-bold">{{ user?.nombre }}</div>
              <div class="text-caption opacity-90">{{ user?.rol === 'admin' ? 'Administrador' : 'Usuario' }}</div>
            </div>
          </v-card>
          
          <v-divider class="my-3" />
          
          <!-- Navegación -->
          <v-list nav dense>
            <v-list-item
              prepend-icon="mdi-account-group"
              title="Usuarios"
              :class="{ 'bg-primary text-white': currentRoute === 'usuarios' }"
              @click="$router.push('/usuarios')"
            />
            <v-list-item
              prepend-icon="mdi-clipboard-list"
              title="Tareas"
              :class="{ 'bg-primary text-white': currentRoute === 'tareas' }"
              @click="$router.push('/tareas')"
            />
          </v-list>
          
          <v-divider class="my-3" />
          
          <!-- Acciones -->
          <div class="pa-3">
            <div class="text-subtitle-2 mb-3 font-weight-bold">Acciones Rápidas</div>
            
            <v-btn
              block
              color="primary"
              variant="elevated"
              class="mb-3"
              :disabled="!isAdmin"
              prepend-icon="mdi-account-plus"
              @click="goAddUser"
            >
              Nuevo Usuario
            </v-btn>
            
            <!-- Búsqueda -->
            <v-text-field
              v-model="search"
              label="Buscar usuarios"
              prepend-inner-icon="mdi-magnify"
              density="comfortable"
              variant="outlined"
              clearable
              class="mb-4"
              hide-details
            />
            
            <v-btn
              block
              color="error"
              variant="tonal"
              prepend-icon="mdi-logout"
              @click="logout"
            >
              Cerrar Sesión
            </v-btn>
          </div>
        </v-navigation-drawer>
      </v-col>
      
      <!-- Contenido principal -->
      <v-col cols="12" md="9">
        <div class="pa-6">
          <!-- Header -->
          <div class="d-flex align-center justify-space-between mb-6">
            <div>
              <h1 class="text-h4 font-weight-bold mb-2">Gestión de Usuarios</h1>
              <p class="text-subtitle-1 text-medium-emphasis">Administra los usuarios del sistema</p>
            </div>
            
            <v-chip
              color="success"
              variant="elevated"
              size="large"
              prepend-icon="mdi-check-circle"
            >
              Sistema Activo
            </v-chip>
          </div>
          
          <!-- Estadísticas rápidas -->
          <v-row class="mb-6">
            <v-col cols="12" sm="4">
              <v-card class="pa-4 text-center" elevation="2">
                <v-icon size="48" color="primary" class="mb-2">mdi-account-group</v-icon>
                <div class="text-h5 font-weight-bold">{{ totalUsers }}</div>
                <div class="text-caption text-medium-emphasis">Total Usuarios</div>
              </v-card>
            </v-col>
            <v-col cols="12" sm="4">
              <v-card class="pa-4 text-center" elevation="2">
                <v-icon size="48" color="success" class="mb-2">mdi-shield-account</v-icon>
                <div class="text-h5 font-weight-bold">{{ adminUsers }}</div>
                <div class="text-caption text-medium-emphasis">Administradores</div>
              </v-card>
            </v-col>
            <v-col cols="12" sm="4">
              <v-card class="pa-4 text-center" elevation="2">
                <v-icon size="48" color="info" class="mb-2">mdi-account</v-icon>
                <div class="text-h5 font-weight-bold">{{ regularUsers }}</div>
                <div class="text-caption text-medium-emphasis">Usuarios Regulares</div>
              </v-card>
            </v-col>
          </v-row>
          
          <!-- Tabla de usuarios -->
          <v-card elevation="2">
            <v-card-title class="d-flex align-center">
              <v-icon class="me-2">mdi-table</v-icon>
              Lista de Usuarios
            </v-card-title>
            <v-card-text>
              <UsersList :search-term="search" @users-loaded="onUsersLoaded" />
            </v-card-text>
          </v-card>
        </div>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import UsersList from '@/views/UsersList.vue'

type User = { id: number; nombre: string; email: string; rol: 'admin'|'usuario' }

const router = useRouter()
const route = useRoute()
const search = ref('')
const user = ref<User | null>(null)
const users = ref<User[]>([])

onMounted(() => {
  const raw = localStorage.getItem('user')
  user.value = raw ? JSON.parse(raw) as User : null
})

const currentRoute = computed(() => route.name)
const isAdmin = computed(() => user.value?.rol === 'admin')

// Estadísticas
const totalUsers = computed(() => users.value.length)
const adminUsers = computed(() => users.value.filter(u => u.rol === 'admin').length)
const regularUsers = computed(() => users.value.filter(u => u.rol === 'usuario').length)

const goAddUser = () => router.push('/usuarios/nuevo')

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

const onUsersLoaded = (loadedUsers: User[]) => {
  users.value = loadedUsers
}
</script>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.v-navigation-drawer {
  border-right: none !important;
}

.v-list-item--active {
  color: white !important;
}
</style>