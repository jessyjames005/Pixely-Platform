<script setup lang="ts">
// Choose a new password using the token from the reset email
// (the link carries ?token=...&email=...).
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '@shared/composables/useApi'
import { useAuthStore } from '../store/auth.store'
import { authErrorMessage } from '../composables/authErrors'
import AuthCard from '../components/AuthCard.vue'

const route = useRoute()
const authStore = useAuthStore()

function queryValue(name: string): string {
  const value = route.query[name]
  return typeof value === 'string' ? value : ''
}

const token = queryValue('token')
const email = queryValue('email')

const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const done = ref(false)

const linkIsUsable = computed(() => token !== '' && email !== '')

const { loading, error, execute: submitReset } = useApi(authStore.resetPassword)
const errorMessage = computed(() => authErrorMessage(error.value))

async function handleSubmit(): Promise<void> {
  await submitReset({
    token,
    email,
    password: password.value,
    passwordConfirmation: passwordConfirmation.value,
  })

  if (!error.value) done.value = true
}
</script>

<template>
  <AuthCard :title="$t('core.auth.title.reset_password', 'Choose a new password')">
    <v-alert v-if="!linkIsUsable" type="error" density="compact" class="mb-4">
      {{ $t('core.auth.msg.invalid_reset_token', 'This password reset link is invalid or has expired.') }}
    </v-alert>

    <template v-else-if="done">
      <v-alert type="success" density="compact" class="mb-4" data-testid="password-reset-done">
        {{ $t('core.auth.msg.password_reset_done', 'Your password has been changed. You can now sign in.') }}
      </v-alert>
      <v-btn color="primary" block :to="{ name: 'login' }">{{ $t('core.auth.action.sign_in', 'Sign in') }}</v-btn>
    </template>

    <v-form v-else @submit.prevent="handleSubmit">
      <v-text-field :model-value="email" :label="$t('core.entities.object.user.email.label', 'Email')" readonly />
      <v-text-field
        v-model="password"
        :label="$t('core.auth.object.password.new.label', 'New password')"
        :hint="$t('core.auth.object.password.new.hint', 'At least 8 characters.')"
        :type="showPassword ? 'text' : 'password'"
        autocomplete="new-password"
        required
        :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="showPassword = !showPassword"
      />
      <v-text-field
        v-model="passwordConfirmation"
        :label="$t('core.auth.object.password.confirmation.label', 'Confirm new password')"
        :type="showPassword ? 'text' : 'password'"
        autocomplete="new-password"
        required
      />

      <v-alert v-if="errorMessage" type="error" density="compact" class="mb-4">
        {{ errorMessage }}
      </v-alert>

      <v-btn type="submit" color="primary" block :loading="loading">
        {{ $t('core.auth.action.reset_password', 'Reset password') }}
      </v-btn>
    </v-form>
  </AuthCard>
</template>
