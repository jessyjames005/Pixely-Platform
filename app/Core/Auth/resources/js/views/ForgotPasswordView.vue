<script setup lang="ts">
// Request a password reset link by email.
import { computed, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useAuthStore } from '../store/auth.store'
import { authErrorMessage } from '../composables/authErrors'
import AuthCard from '../components/AuthCard.vue'

const email = ref('')
const sent = ref(false)

const authStore = useAuthStore()
const { loading, error, execute: submitForgotPassword } = useApi(authStore.forgotPassword)

const errorMessage = computed(() => authErrorMessage(error.value))

async function handleSubmit(): Promise<void> {
  await submitForgotPassword(email.value)

  if (!error.value) sent.value = true
}
</script>

<template>
  <AuthCard :title="$t('core.auth.title.forgot_password', 'Forgot your password?')">
    <template v-if="sent">
      <v-alert type="success" density="compact" class="mb-4" data-testid="reset-link-sent">
        {{ $t('core.auth.msg.reset_link_sent', 'If an account exists for this address, a password reset link is on its way.') }}
      </v-alert>
    </template>

    <v-form v-else @submit.prevent="handleSubmit">
      <p class="mb-4">
        {{ $t('core.auth.msg.forgot_password_intro', 'Enter your email address and we will send you a link to choose a new password.') }}
      </p>
      <v-text-field
        v-model="email"
        :label="$t('core.entities.object.user.email.label', 'Email')"
        type="email"
        autocomplete="email"
        required
      />

      <v-alert v-if="errorMessage" type="error" density="compact" class="mb-4">
        {{ errorMessage }}
      </v-alert>

      <v-btn type="submit" color="primary" block :loading="loading">
        {{ $t('core.auth.action.send_reset_link', 'Send reset link') }}
      </v-btn>
    </v-form>

    <div class="mt-4">
      <router-link :to="{ name: 'login' }" class="text-body-2">
        {{ $t('core.auth.action.back_to_sign_in', 'Back to sign in') }}
      </router-link>
    </div>
  </AuthCard>
</template>
