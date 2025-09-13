<template>
  <div class="login-container">
    <v-container class="d-flex align-center justify-center fill-height">
      <v-row justify="center" no-gutters>
        <v-col cols="12" sm="8" md="5" lg="4">
          <v-card class="login-card pa-8" elevation="8">
            <!-- Header -->
            <div class="text-center mb-8">
              <v-icon size="64" color="primary" class="mb-4">mdi-account-circle</v-icon>
              <h1 class="text-h4 font-weight-light mb-2">Iniciar Sesión</h1>
              <p class="text-body-2 text-medium-emphasis">Accede a tu cuenta</p>
            </div>

            <!-- Form -->
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
              />

              <v-alert
                v-if="errorMsg"
                type="error"
                variant="tonal"
                class="mb-4"
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
                class="mb-6"
                elevation="0"
              >
                Iniciar Sesión
              </v-btn>
            </v-form>

            <!-- Footer -->
            <div class="text-center">
              <p class="text-caption text-medium-emphasis">
                Sistema de Gestión Laravel + Vue
              </p>
            </div>
          </v-card>
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
}

.login-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.login-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
}

.v-text-field :deep(.v-field) {
  transition: all 0.3s ease;
  border-radius: 12px;
}

.v-text-field:hover :deep(.v-field) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.v-text-field--focused :deep(.v-field) {
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
}

.v-btn {
  border-radius: 12px;
  text-transform: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.v-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

/* Responsive */
@media (max-width: 600px) {
  .login-card {
    margin: 16px;
    padding: 24px !important;
  }
}
</style>