<script setup lang="ts">
// Login screen for the administration SPA
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import { useApi } from '../composables/useApi'
import { useAuth } from '../composables/useAuth'

const email = ref('')
const password = ref('')

const router = useRouter()
const { login } = useAuth()

// Wraps the login call with loading/error state
const { loading, error, execute: submitLogin } = useApi(login)

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

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f9fafb;
}

.login-card {
  width: 320px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.login-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.85rem;
}

.login-form__field input {
  padding: 0.5rem 0.65rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
}

.login-form__error {
  color: #dc2626;
  margin: 0;
  font-size: 0.85rem;
}
</style>
