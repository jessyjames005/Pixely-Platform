<script setup lang="ts">
// Login screen for the administration SPA
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@shared/composables/useApi'
import { useAuthStore } from '../store/auth.store'

const email = ref('')
const password = ref('')

const router = useRouter()
const authStore = useAuthStore()

const { loading, error, execute: submitLogin } = useApi(authStore.login)

async function handleSubmit(): Promise<void> {
  await submitLogin(email.value, password.value)

  if (!error.value) {
    router.push({ name: 'admin.dashboard' })
  }
}
</script>

<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card title="Sign in">
          <v-card-text>
            <v-form @submit.prevent="handleSubmit">
              <v-text-field
                v-model="email"
                label="Email"
                type="email"
                autocomplete="username"
                required
              />
              <v-text-field
                v-model="password"
                label="Password"
                type="password"
                autocomplete="current-password"
                required
              />

              <v-alert v-if="error" type="error" density="compact" class="mb-4">
                {{ error.message }}
              </v-alert>

              <v-btn type="submit" color="primary" block :loading="loading">Sign in</v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
