<template>
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
      <v-btn
        icon="mdi-pencil"
        size="small"
        color="primary"
        variant="text"
        @click="editUser(item.id)"
      />
    </template>
    
    <template #no-data>
      <div class="pa-6 text-center">No hay usuarios para mostrar.</div>
    </template>
  </v-data-table>
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
const router = useRouter()

const items = ref<Usuario[]>([])
const loading = ref(false)

const headers = [
  { title: 'Nombre', value: 'nombre' },
  { title: 'Email', value: 'email' },
  { title: 'Rol', value: 'rol' },
  { title: 'Fecha de Registro', value: 'created_at' },
  { title: 'Acciones', value: 'actions', sortable: false }
]

// Cargar usuarios desde la API
const fetchUsers = async () => {
  loading.value = true
  try {
    const { data } = await api.get<Usuario[]>('/usuarios/listUsers')
    items.value = data
  } catch (error) {
    console.error('Error al cargar usuarios:', error)
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
</script>