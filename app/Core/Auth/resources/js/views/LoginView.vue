<script setup lang="ts">
// Login screen for the administration SPA
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@shared/components/BaseButton.vue'
import BaseCard from '@shared/components/BaseCard.vue'
import { useApi } from '@shared/composables/useApi'
import { useAuthStore } from '../store/auth.store'
import '../styles/login.scss'

const email = ref('')
const password = ref('')

const router = useRouter()
const authStore = useAuthStore()

// Wraps the login call with loading/error state
const { loading, error, execute: submitLogin } = useApi(authStore.login)

async function handleSubmit(): Promise<void> {
  await submitLogin(email.value, password.value)

  if (!error.value) {
    router.push({ name: 'admin.dashboard' })
  }
}
</script>

<template>
  <div class="login-page">
    <BaseCard title="Sign in" class="login-card">
      <form class="login-form" @submit.prevent="handleSubmit">
        <label class="login-form__field">
          <span>Email</span>
          <input v-model="email" type="email" required autocomplete="username" />
        </label>

        <label class="login-form__field">
          <span>Password</span>
          <input v-model="password" type="password" required autocomplete="current-password" />
        </label>

        <p v-if="error" class="login-form__error">{{ error.message }}</p>

        <BaseButton type="submit" :loading="loading">Sign in</BaseButton>
      </form>
    </BaseCard>
  </div>
</template>
