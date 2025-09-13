<template>
  <v-container fluid>
    <v-row>
      <!-- Sidebar -->
      <v-col cols="12" md="3">
        <v-card class="pa-4">
          <div class="text-subtitle-1 mb-2">Acciones</div>
          <v-btn
            block color="primary" class="mb-3"
            @click="showCreateDialog = true"
            v-if="isCurrentUserAdmin"
          >
            Agregar Tarea
          </v-btn>
          <v-btn
            block color="success" variant="tonal" class="mb-3"
            @click="downloadReport"
            :loading="downloadingReport"
          >
            Descargar Reporte
          </v-btn>
          <v-text-field
            v-model="search"
            label="Buscar tareas"
            prepend-inner-icon="mdi-magnify"
            density="comfortable"
            clearable
            class="mb-4"
          />
          <v-btn block color="error" variant="tonal" @click="logout">
            Cerrar sesión
          </v-btn>
          <v-divider class="my-4" />
          <div class="text-caption">
            Sesión: <strong>{{ user?.nombre }}</strong> ({{ user?.rol }})
          </div>
        </v-card>
      </v-col>

      <!-- Contenido principal -->
      <v-col cols="12" md="9">
        <v-card class="pa-4">
          <div class="text-h6 mb-4">Gestión de Tareas</div>
          <v-data-table
            :items="filteredTareas"
            :headers="headers"
            :loading="loading"
            class="elevation-1"
          >
            <template #item.estado="{ item }">
              <v-chip
                :color="getEstadoColor(item.estado)"
                size="small"
              >
                {{ formatEstado(item.estado) }}
              </v-chip>
            </template>

            <template #item.prioridad="{ item }">
              <v-chip
                :color="getPrioridadColor(item.prioridad)"
                size="small"
              >
                {{ formatPrioridad(item.prioridad) }}
              </v-chip>
            </template>

            <template #item.fecha_vencimiento="{ item }">
              <span :class="{ 'text-red': isVencida(item.fecha_vencimiento) }">
                {{ formatDate(item.fecha_vencimiento) }}
              </span>
            </template>

            <template #item.usuario="{ item }">
              {{ item.usuario?.nombre || 'Sin asignar' }}
            </template>

            <template #item.actions="{ item }">
              <div class="d-flex gap-1" v-if="isCurrentUserAdmin">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  color="primary"
                  variant="tonal"
                  @click="editTarea(item)"
                  :title="'Editar tarea: ' + item.titulo"
                />
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  color="error"
                  variant="tonal"
                  @click="deleteTarea(item)"
                  :title="'Eliminar tarea: ' + item.titulo"
                />
              </div>
            </template>

            <template #no-data>
              <div class="pa-6 text-center">No hay tareas para mostrar.</div>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>

    <!-- Dialog para crear/editar tarea -->
    <v-dialog v-model="showCreateDialog" max-width="600px">
      <v-card>
        <v-card-title>
          {{ editingTarea ? 'Editar Tarea' : 'Nueva Tarea' }}
        </v-card-title>
        <v-card-text>
          <v-form v-model="formValid" @submit.prevent="saveTarea">
            <v-text-field
              v-model="tareaForm.titulo"
              label="Título"
              :rules="[rules.required]"
              density="comfortable"
            />
            <v-textarea
              v-model="tareaForm.descripcion"
              label="Descripción"
              rows="3"
              density="comfortable"
            />
            <v-select
              v-model="tareaForm.estado"
              label="Estado"
              :items="estadoOptions"
              :rules="[rules.required]"
              density="comfortable"
            />
            <v-select
              v-model="tareaForm.prioridad"
              label="Prioridad"
              :items="prioridadOptions"
              :rules="[rules.required]"
              density="comfortable"
            />
            <v-text-field
              v-model="tareaForm.fecha_vencimiento"
              label="Fecha de Vencimiento"
              type="date"
              :rules="[rules.required]"
              density="comfortable"
            />
            <v-select
              v-model="tareaForm.usuario_id"
              label="Asignar a Usuario"
              :items="usuarios"
              item-title="nombre"
              item-value="id"
              :rules="[rules.required]"
              density="comfortable"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="grey" variant="text" @click="closeDialog">
            Cancelar
          </v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            @click="saveTarea"
            :disabled="!formValid"
          >
            {{ editingTarea ? 'Actualizar' : 'Crear' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog de confirmación para eliminar -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h6">Confirmar Eliminación</v-card-title>
        <v-card-text>
          ¿Estás seguro de que deseas eliminar la tarea "{{ tareaToDelete?.titulo }}"?
          <br><br>
          <v-alert type="warning" variant="tonal" density="compact">
            Esta acción no se puede deshacer.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="grey" variant="text" @click="showDeleteDialog = false" :disabled="deleting">
            Cancelar
          </v-btn>
          <v-btn color="error" @click="confirmDelete" :loading="deleting">
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
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

type Usuario = { id: number; nombre: string; email: string; rol: string }
type Tarea = {
  id: number
  titulo: string
  descripcion: string
  estado: string
  prioridad: string
  fecha_vencimiento: string
  usuario_id: number
  usuario?: Usuario
}

const router = useRouter()
const search = ref('')
const user = ref<Usuario | null>(null)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const downloadingReport = ref(false)
const showCreateDialog = ref(false)
const showDeleteDialog = ref(false)
const formValid = ref(false)
const editingTarea = ref(false)
const currentEditingId = ref<number | null>(null)
const tareas = ref<Tarea[]>([])
const usuarios = ref<Usuario[]>([])
const tareaToDelete = ref<Tarea | null>(null)

// Snackbar
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const tareaForm = ref({
  titulo: '',
  descripcion: '',
  estado: 'pendiente',
  prioridad: 'media',
  fecha_vencimiento: '',
  usuario_id: null as number | null
})

const isCurrentUserAdmin = computed(() => user.value?.rol === 'admin')

const headers = computed(() => {
  if (isCurrentUserAdmin.value) {
    return [
      { title: 'Título', value: 'titulo' },
      { title: 'Estado', value: 'estado' },
      { title: 'Prioridad', value: 'prioridad' },
      { title: 'Asignado a', value: 'usuario' },
      { title: 'Vencimiento', value: 'fecha_vencimiento' },
      { title: 'Acciones', value: 'actions', sortable: false }
    ]
  } else {
    return [
      { title: 'Título', value: 'titulo' },
      { title: 'Estado', value: 'estado' },
      { title: 'Prioridad', value: 'prioridad' },
      { title: 'Asignado a', value: 'usuario' },
      { title: 'Vencimiento', value: 'fecha_vencimiento' }
    ]
  }
})

const estadoOptions = [
  { title: 'Pendiente', value: 'pendiente' },
  { title: 'En Progreso', value: 'en_progreso' },
  { title: 'Completada', value: 'completada' }
]

const prioridadOptions = [
  { title: 'Baja', value: 'baja' },
  { title: 'Media', value: 'media' },
  { title: 'Alta', value: 'alta' }
]

const rules = {
  required: (v: any) => !!v || 'Este campo es requerido'
}

onMounted(() => {
  const raw = localStorage.getItem('user')
  user.value = raw ? JSON.parse(raw) : null
  fetchTareas()
  fetchUsuarios()
})

const fetchTareas = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/tareas/list')
    tareas.value = data
  } catch (error) {
    console.error('Error al cargar tareas:', error)
    showMessage('Error al cargar tareas', 'error')
  } finally {
    loading.value = false
  }
}

const fetchUsuarios = async () => {
  try {
    const { data } = await api.get('/usuarios/listUsers')
    usuarios.value = data
  } catch (error) {
    console.error('Error al cargar usuarios:', error)
    showMessage('Error al cargar usuarios', 'error')
  }
}

const filteredTareas = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return tareas.value
  return tareas.value.filter(t =>
    t.titulo.toLowerCase().includes(q) ||
    t.descripcion?.toLowerCase().includes(q) ||
    t.usuario?.nombre?.toLowerCase().includes(q) ||
    t.estado.toLowerCase().includes(q)
  )
})

const getEstadoColor = (estado: string) => {
  switch (estado) {
    case 'pendiente': return 'orange'
    case 'en_progreso': return 'blue'
    case 'completada': return 'green'
    default: return 'grey'
  }
}

const formatEstado = (estado: string) => {
  switch (estado) {
    case 'pendiente': return 'Pendiente'
    case 'en_progreso': return 'En Progreso'
    case 'completada': return 'Completada'
    default: return estado
  }
}

const getPrioridadColor = (prioridad: string) => {
  switch (prioridad) {
    case 'alta': return 'red'
    case 'media': return 'orange'
    case 'baja': return 'green'
    default: return 'grey'
  }
}

const formatPrioridad = (prioridad: string) => {
  switch (prioridad) {
    case 'alta': return 'Alta'
    case 'media': return 'Media'
    case 'baja': return 'Baja'
    default: return prioridad
  }
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('es-GT')
}

const isVencida = (fecha: string) => {
  const today = new Date()
  const fechaVencimiento = new Date(fecha)
  return fechaVencimiento < today
}

const editTarea = (tarea: Tarea) => {
  editingTarea.value = true
  currentEditingId.value = tarea.id
  tareaForm.value = {
    titulo: tarea.titulo,
    descripcion: tarea.descripcion || '',
    estado: tarea.estado,
    prioridad: tarea.prioridad,
    fecha_vencimiento: tarea.fecha_vencimiento,
    usuario_id: tarea.usuario_id
  }
  showCreateDialog.value = true
}

const deleteTarea = (tarea: Tarea) => {
  tareaToDelete.value = tarea
  showDeleteDialog.value = true
}

const confirmDelete = async () => {
  if (!tareaToDelete.value) return
  deleting.value = true
  try {
    await api.delete(`/tareas/delete/${tareaToDelete.value.id}`)
    await fetchTareas()
    showDeleteDialog.value = false
    tareaToDelete.value = null
    showMessage('Tarea eliminada correctamente', 'success')
  } catch (error: any) {
    console.error('Error al eliminar tarea:', error)
    const message = error?.response?.data?.message || 'Error al eliminar tarea'
    showMessage(message, 'error')
  } finally {
    deleting.value = false
  }
}

const saveTarea = async () => {
  if (!formValid.value) return
  saving.value = true
  try {
    if (editingTarea.value) {
      await api.put(`/tareas/update/${currentEditingId.value}`, tareaForm.value)
      showMessage('Tarea actualizada correctamente', 'success')
    } else {
      await api.post('/tareas/create', tareaForm.value)
      showMessage('Tarea creada correctamente', 'success')
    }
    await fetchTareas()
    closeDialog()
  } catch (error: any) {
    console.error('Error al guardar tarea:', error)
    const message = error?.response?.data?.message || 'Error al guardar tarea'
    showMessage(message, 'error')
  } finally {
    saving.value = false
  }
}

const closeDialog = () => {
  showCreateDialog.value = false
  editingTarea.value = false
  currentEditingId.value = null
  tareaForm.value = {
    titulo: '',
    descripcion: '',
    estado: 'pendiente',
    prioridad: 'media',
    fecha_vencimiento: '',
    usuario_id: null
  }
}

const downloadReport = async () => {
  downloadingReport.value = true
  try {
    const { data } = await api.get('/tareas/pendientes')
    // Crear contenido CSV simple
    const csvContent = [
      ['ID', 'Título', 'Descripción', 'Prioridad', 'Usuario Asignado', 'Fecha Vencimiento'],
      ...data.map((tarea: any) => [
        tarea.id,
        tarea.titulo,
        tarea.descripcion || '',
        tarea.prioridad,
        tarea.usuario?.nombre || 'Sin asignar',
        tarea.fecha_vencimiento
      ])
    ].map(row => row.join(',')).join('\n')

    // Descargar archivo
    const blob = new Blob([csvContent], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `tareas_pendientes_${new Date().toISOString().split('T')[0]}.csv`
    link.click()
    window.URL.revokeObjectURL(url)
    showMessage('Reporte descargado correctamente', 'success')
  } catch (error) {
    console.error('Error al descargar reporte:', error)
    showMessage('Error al descargar reporte', 'error')
  } finally {
    downloadingReport.value = false
  }
}

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

const showMessage = (message: string, color: string = 'success') => {
  snackbarMessage.value = message
  snackbarColor.value = color
  showSnackbar.value = true
}
</script>

<style scoped>
.text-red {
  color: #f44336;
  font-weight: bold;
}

.gap-1 {
  gap: 4px;
}

:deep(.v-data-table-header) {
  background-color: #f5f5f5;
}

:deep(.v-data-table__tr:hover) {
  background-color: #f9f9f9;
}
</style>