<template>
  <v-container fluid>
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-card class="pa-6">
          <v-card-title class="text-h5 mb-4">
            {{ isEditing ? 'Editar Usuario' : 'Agregar Nuevo Usuario' }}
          </v-card-title>
          
          <v-form v-model="valid" @submit.prevent="onSubmit">
            <v-text-field
              v-model="form.nombre"
              label="Nombre completo"
              :rules="[rules.required]"
              prepend-inner-icon="mdi-account"
              density="comfortable"
              clearable
            />
            
            <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              :rules="[rules.required, rules.email]"
              prepend-inner-icon="mdi-email"
              density="comfortable"
              clearable
            />
            
            <v-text-field
              v-model="form.password"
              label="Contraseña"
              type="password"
              :rules="isEditing ? [rules.min6IfPresent] : [rules.required, rules.min6]"
              prepend-inner-icon="mdi-lock"
              density="comfortable"
              clearable
              :hint="isEditing ? 'Dejar vacío para mantener la contraseña actual' : ''"
              persistent-hint
            />
            
            <v-select
              v-model="form.rol"
              label="Rol"
              :items="roles"
              :rules="[rules.required]"
              prepend-inner-icon="mdi-shield-account"
              density="comfortable"
            />
            
            <v-alert
              v-if="errorMsg"
              type="error"
              variant="tonal"
              class="mb-4"
              :text="errorMsg"
              density="comfortable"
            />
            
            <v-alert
              v-if="successMsg"
              type="success"
              variant="tonal"
              class="mb-4"
              :text="successMsg"
              density="comfortable"
            />
            
            <div class="d-flex gap-3">
              <v-btn
                :loading="loading"
                :disabled="!valid || loading"
                type="submit"
                color="primary"
                size="large"
              >
                {{ isEditing ? 'Actualizar' : 'Crear Usuario' }}
              </v-btn>
              
              <v-btn
                color="grey"
                variant="outlined"
                size="large"
                @click="goBack"
              >
                Cancelar
              </v-btn>
            </div>
          </v-form>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const route = useRoute()

const valid = ref(false)
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const isEditing = ref(false)

const form = ref({
  nombre: '',
  email: '',
  password: '',
  rol: 'usuario'
})

const roles = [
  { title: 'Usuario', value: 'usuario' },
  { title: 'Administrador', value: 'admin' }
]

const rules = {
  required: (v: string) => !!v || 'Este campo es requerido',
  email: (v: string) => /.+@.+\..+/.test(v) || 'Email inválido',
  min6: (v: string) => (v?.length ?? 0) >= 6 || 'Mínimo 6 caracteres',
  min6IfPresent: (v: string) => !v || v.length >= 6 || 'Mínimo 6 caracteres'
}

onMounted(() => {
  if (route.params.id) {
    isEditing.value = true
    loadUser()
  }
})

const loadUser = async () => {
  try {
    loading.value = true
    const { data } = await api.get(`/usuarios/getUser/${route.params.id}`)
    form.value = {
      nombre: data.nombre,
      email: data.email,
      password: '',
      rol: data.rol
    }
  } catch (error: any) {
    errorMsg.value = 'Error al cargar el usuario'
  } finally {
    loading.value = false
  }
}

const onSubmit = async () => {
  errorMsg.value = ''
  successMsg.value = ''
  loading.value = true
  
  try {
    let payload: any = { ...form.value }
    
    // Si se eta editando y no hay password se remueve del payload
    if (isEditing.value && !payload.password) {
      const { password, ...payloadWithoutPassword } = payload
      payload = payloadWithoutPassword
    }
    
    if (isEditing.value) {
      await api.put(`/usuarios/updateUser/${route.params.id}`, payload)
      successMsg.value = 'Usuario actualizado correctamente'
    } else {
      await api.post('/usuarios/addUser', payload)
      successMsg.value = 'Usuario creado correctamente'
      
      // Limpiar formulario despues de crear
      form.value = {
        nombre: '',
        email: '',
        password: '',
        rol: 'usuario'
      }
    }
    
    // Redirigir despues de 2 segundos
    setTimeout(() => {
      router.push('/usuarios')
    }, 2000)
    
  } catch (error: any) {
    errorMsg.value = error?.response?.data?.message 
      || error?.response?.data?.errors?.email?.[0]
      || 'Error al procesar la solicitud'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push('/usuarios')
}
</script>

<style scoped>
.gap-3 {
  gap: 12px;
}
</style>