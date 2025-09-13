<template>
  <div>
    <v-data-table
      :items="filtered"
      :headers="headers"
      :loading="loading"
      class="elevation-1"
    >
      <template #item.created_at="{ item }">
        <span>{{ formatDate(item.created_at) }}</span>
      </template>
      
      <template #item.rol="{ item }">
        <v-chip
          :color="item.rol === 'admin' ? 'primary' : 'secondary'"
          size="small"
        >
          {{ item.rol === 'admin' ? 'Administrador' : 'Usuario' }}
        </v-chip>
      </template>
      
      <template #item.actions="{ item }">
        <div class="d-flex gap-1" v-if="isCurrentUserAdmin">
          <!-- Botón Editar -->
          <v-btn
            variant="text"
            color="primary"
            @click="editUser(item.id)"
            :title="'Editar ' + item.nombre"
            class="action-btn"
          >
            <v-icon size="20">mdi-pencil</v-icon>
          </v-btn>

          <!-- Botón Eliminar -->
          <v-btn
            variant="text"
            color="error"
            @click="confirmDeleteUser(item)"
            :title="'Eliminar ' + item.nombre"
            :disabled="item.id === currentUser?.id"
            class="action-btn"
          >
            <v-icon size="20">mdi-delete</v-icon>
          </v-btn>
        </div>
      </template>
      
      <template #no-data>
        <div class="pa-6 text-center">No hay usuarios para mostrar.</div>
      </template>
    </v-data-table>

    <!-- Dialog de confirmación para eliminar usuario -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h6">Confirmar Eliminación</v-card-title>
        <v-card-text>
          ¿Estás seguro de que deseas eliminar al usuario "{{ userToDelete?.nombre }}"?
          <br><br>
          <v-alert type="warning" variant="tonal" density="compact">
            Esta acción no se puede deshacer.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn 
            color="grey" 
            variant="text" 
            @click="showDeleteDialog = false"
            :disabled="deleting"
          >
            Cancelar
          </v-btn>
          <v-btn 
            color="error" 
            @click="deleteUser" 
            :loading="deleting"
          >
            Eliminar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar para mostrar mensajes -->
    <v-snackbar
      v-model="showSnackbar"
      :color="snackbarColor"
      timeout="3000"
      location="top"
    >
      {{ snackbarMessage }}
      <template #actions>
        <v-btn
          color="white"
          variant="text"
          @click="showSnackbar = false"
        >
          Cerrar
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

type Usuario = {
  id: number
  nombre: string
  email: string
  rol: 'admin'|'usuario'
  created_at: string
  updated_at: string
}

const props = defineProps<{ searchTerm?: string }>()
const emit = defineEmits<{
  usersLoaded: [users: Usuario[]]
}>()

const router = useRouter()
const items = ref<Usuario[]>([])
const loading = ref(false)
const deleting = ref(false)
const showDeleteDialog = ref(false)
const userToDelete = ref<Usuario | null>(null)
const currentUser = ref<Usuario | null>(null)

// Snackbar
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

// Obtener usuario actual del localStorage
onMounted(() => {
  const raw = localStorage.getItem('user')
  currentUser.value = raw ? JSON.parse(raw) : null
})

const isCurrentUserAdmin = computed(() => currentUser.value?.rol === 'admin')

const headers = computed(() => {
  if (isCurrentUserAdmin.value) {
    return [
      { title: 'Nombre', value: 'nombre' },
      { title: 'Email', value: 'email' },
      { title: 'Rol', value: 'rol' },
      { title: 'Fecha de Registro', value: 'created_at' },
      { title: 'Acciones', value: 'actions', sortable: false }
    ]
  } else {
    return [
      { title: 'Nombre', value: 'nombre' },
      { title: 'Email', value: 'email' },
      { title: 'Rol', value: 'rol' },
      { title: 'Fecha de Registro', value: 'created_at' }
    ]
  }
})

// Cargar usuarios desde la API
const fetchUsers = async () => {
  loading.value = true
  try {
    const { data } = await api.get<Usuario[]>('/usuarios/listUsers')
    items.value = data
    emit('usersLoaded', data) // Emitir evento con los usuarios cargados
  } catch (error) {
    console.error('Error al cargar usuarios:', error)
    showMessage('Error al cargar usuarios', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(fetchUsers)

const filtered = computed(() => {
  const q = (props.searchTerm || '').toLowerCase().trim()
  if (!q) return items.value
  return items.value.filter(u =>
    u.nombre.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q) ||
    u.rol.toLowerCase().includes(q)
  )
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('es-GT', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const editUser = (id: number) => {
  router.push(`/usuarios/${id}/editar`)
}

const confirmDeleteUser = (user: Usuario) => {
  userToDelete.value = user
  showDeleteDialog.value = true
}

const deleteUser = async () => {
  if (!userToDelete.value) return
  
  deleting.value = true
  try {
    await api.delete(`/usuarios/deleteUser/${userToDelete.value.id}`)
    await fetchUsers() // Recargar la lista
    showDeleteDialog.value = false
    userToDelete.value = null
    showMessage('Usuario eliminado correctamente', 'success')
  } catch (error: any) {
    console.error('Error al eliminar usuario:', error)
    const message = error?.response?.data?.message || 'Error al eliminar usuario'
    showMessage(message, 'error')
  } finally {
    deleting.value = false
  }
}

const showMessage = (message: string, color: string = 'success') => {
  snackbarMessage.value = message
  snackbarColor.value = color
  showSnackbar.value = true
}

// Exponer fetchUsers para que el componente padre pueda recargar la lista
defineExpose({
  fetchUsers
})
</script>

<style scoped>
.gap-1 {
  gap: 4px;
}

:deep(.v-data-table-header) {
  background-color: #f5f5f5;
}

:deep(.v-data-table__tr:hover) {
  background-color: #f9f9f9;
}

/* Evitar que los botones de Vuetify sean sobrescritos por style.css */
:deep(.v-btn) {
  background-color: unset;
  border: none;
  min-width: 0;
  padding: 0;
  line-height: 1;
  box-shadow: none;
}

/* Estilos para botones de acción */
.action-btn {
  transition: transform 0.2s, box-shadow 0.2s;
}

.action-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
</style>
