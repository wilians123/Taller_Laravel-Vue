<template>
  <div class="login-container">
    <!-- Fondo con patrón -->
    <div class="background-pattern"></div>
    
    <!-- Contenido principal -->
    <v-container class="d-flex align-center justify-center fill-height">
      <v-row justify="center" no-gutters>
        <v-col cols="12" sm="8" md="6" lg="4">
          <!-- Logo/Header -->
          <div class="text-center mb-8">
            <v-avatar size="80" class="mb-4 elevation-8" color="primary">
              <v-icon size="40" color="white">mdi-shield-account</v-icon>
            </v-avatar>
            <h1 class="text-h3 font-weight-bold text-gradient mb-2">Laravel + Vue</h1>
            <p class="text-h6 text-medium-emphasis">Sistema de Gestión de Usuarios</p>
          </div>
          
          <!-- Card de login -->
          <v-card 
            class="login-card elevation-12 rounded-xl pa-8"
            :class="{ 'shake': errorMsg }"
          >
            <div class="text-center mb-6">
              <h2 class="text-h5 font-weight-bold mb-2">Iniciar Sesión</h2>
              <p class="text-body-2 text-medium-emphasis">Ingresa tus credenciales para continuar</p>
            </div>
            
            <v-form v-model="valid" @submit.prevent="onSubmit">
              <v-text-field
                v-model="email"
                label="Correo Electrónico"
                type="email"
                :rules="[rules.required, rules.email]"
                prepend-inner-icon="mdi-email-outline"
                variant="outlined"
                density="comfortable"
                clearable
                class="mb-3"
                :class="{ 'error-field': errorMsg }"
              />
              
              <v-text-field
                v-model="password"
                label="Contraseña"
                :type="showPassword ? 'text' : 'password'"
                :rules="[rules.required, rules.min6]"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append-inner="showPassword = !showPassword"
                variant="outlined"
                density="comfortable"
                clearable
                class="mb-4"
                :class="{ 'error-field': errorMsg }"
              />
              
              <v-alert
                v-if="errorMsg"
                type="error"
                variant="tonal"
                class="mb-4 animated-alert"
                density="comfortable"
                :text="errorMsg"
                closable
                @click:close="errorMsg = ''"
              />
              
              <v-btn
                :loading="loading"
                :disabled="!valid || loading"
                type="submit"
                color="primary"
                size="large"
                block
                class="mb-4 login-btn"
                elevation="4"
              >
                <v-icon left class="me-2">mdi-login</v-icon>
                Iniciar Sesión
              </v-btn>
              
              <!-- Info de demo -->
              <v-card 
                variant="tonal" 
                color="info" 
                class="pa-3"
              >
                <div class="text-caption">
                  <div class="font-weight-bold mb-1">Credenciales de prueba:</div>
                  <div>📧 nuevo@example.com</div>
                  <div>🔒 123456</div>
                </div>
              </v-card>
            </v-form>
          </v-card>
          
          <!-- Footer -->
          <div class="text-center mt-8">
            <p class="text-caption text-medium-emphasis">
              © 2025 UMG - Sistema de Gestión Laravel + Vue.js
            </p>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const loading = ref(false)
const valid = ref(false)
const errorMsg = ref('')
const showPassword = ref(false)

const rules = {
  required: (v: string) => !!v || 'Este campo es requerido',
  email: (v: string) => /.+@.+\..+/.test(v) || 'Ingresa un email válido',
  min6: (v: string) => (v?.length ?? 0) >= 6 || 'Mínimo 6 caracteres',
}

const onSubmit = async () => {
  errorMsg.value = ''
  loading.value = true
  
  try {
    const { data } = await api.post('/login', {
      email: email.value,
      password: password.value,
    })
    
    // Guardar token y usuario
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.usuario))
    
    // Redirigir
    const redirect = (route.query.redirect as string) || '/usuarios'
    router.push(redirect)
    
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message
      || e?.response?.data?.errors?.email?.[0]
      || 'Error al iniciar sesión. Verifica tus credenciales.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow: hidden;
}

.background-pattern {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%),
    radial-gradient(circle at 75% 75%, rgba(255,255,255,0.1) 0%, transparent 50%);
  background-size: 100px 100px;
  animation: float 20s ease-in-out infinite;
}

.login-card {
  backdrop-filter: blur(20px);
  background: rgba(255, 255, 255, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
}

.login-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.1) !important;
}

.text-gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.login-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  transition: all 0.3s ease;
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4) !important;
}

.error-field {
  animation: shake 0.5s ease-in-out;
}

.animated-alert {
  animation: slideIn 0.3s ease-out;
}

.shake {
  animation: shake 0.5s ease-in-out;
}

@keyframes float {
  0%, 100% { 
    transform: translateY(0px) rotate(0deg); 
  }
  50% { 
    transform: translateY(-20px) rotate(180deg); 
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Efectos en los campos de entrada */
.v-text-field :deep(.v-field) {
  transition: all 0.3s ease;
}

.v-text-field:hover :deep(.v-field) {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.v-text-field--focused :deep(.v-field) {
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.2);
}

/* Responsive */
@media (max-width: 600px) {
  .login-card {
    margin: 16px;
  }
}
</style>