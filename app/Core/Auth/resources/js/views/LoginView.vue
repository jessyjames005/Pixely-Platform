<script setup lang="ts">
// Login screen for the platform SPA: credentials, optional "remember me",
// and the second step for accounts with two-factor authentication.
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@shared/composables/useApi'
import { useAuthStore } from '../store/auth.store'
import { authErrorMessage } from '../composables/authErrors'
import AuthCard from '../components/AuthCard.vue'

type Step = 'credentials' | 'two-factor'

const email = ref('')
const password = ref('')
const remember = ref(false)
const showPassword = ref(false)

const step = ref<Step>('credentials')
const code = ref('')
const useRecoveryCode = ref(false)

const router = useRouter()
const authStore = useAuthStore()

const { loading, error, execute: submitLogin } = useApi(authStore.login)
const {
  loading: verifying,
  error: challengeError,
  execute: submitChallenge,
} = useApi(authStore.completeTwoFactorChallenge)

const errorMessage = computed(() => authErrorMessage(error.value ?? challengeError.value))

async function handleSubmit(): Promise<void> {
  challengeError.value = null
  const result = await submitLogin(email.value, password.value, remember.value)

  if (error.value || !result) return

  if (result.twoFactorRequired) {
    step.value = 'two-factor'
    return
  }

  await router.push({ name: 'admin.dashboard' })
}

async function handleChallenge(): Promise<void> {
  const value = code.value.trim()
  await submitChallenge(useRecoveryCode.value ? { recoveryCode: value } : { code: value })

  if (!challengeError.value) {
    await router.push({ name: 'admin.dashboard' })
    return
  }

  // The pending sign-in is gone server-side: start over from the password.
  if (['TWO_FACTOR_SESSION_EXPIRED', 'TOO_MANY_ATTEMPTS'].includes(challengeError.value.code)) {
    backToCredentials()
  }
}

function backToCredentials(): void {
  step.value = 'credentials'
  code.value = ''
  useRecoveryCode.value = false
  password.value = ''
}

function toggleRecoveryCode(): void {
  useRecoveryCode.value = !useRecoveryCode.value
  code.value = ''
  challengeError.value = null
}

function togglePasswordVisibility(): void {
  showPassword.value = !showPassword.value
}
</script>

<template>
  <AuthCard
    :title="step === 'credentials'
      ? $t('core.auth.title.sign_in', 'Sign in')
      : $t('core.auth.title.two_factor_challenge', 'Two-factor authentication')"
  >
    <v-form v-if="step === 'credentials'" @submit.prevent="handleSubmit">
      <v-text-field
        v-model="email"
        :label="$t('core.entities.object.user.email.label', 'Email')"
        type="email"
        autocomplete="username"
        required
      />
      <v-text-field
        v-model="password"
        :label="$t('core.entities.object.user.password.label', 'Password')"
        :type="showPassword ? 'text' : 'password'"
        autocomplete="current-password"
        required
        :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
        @click:append-inner="togglePasswordVisibility"
      />

      <div class="d-flex align-center justify-space-between flex-wrap mb-2">
        <v-checkbox
          v-model="remember"
          :label="$t('core.auth.object.session.remember.label', 'Remember me')"
          density="compact"
          hide-details
        />
        <router-link :to="{ name: 'forgot-password' }" class="text-body-2">
          {{ $t('core.auth.action.forgot_password', 'Forgot your password?') }}
        </router-link>
      </div>

      <v-alert v-if="errorMessage" type="error" density="compact" class="mb-4">
        {{ errorMessage }}
      </v-alert>

      <v-btn type="submit" color="primary" block :loading="loading">{{ $t('core.auth.action.sign_in', 'Sign in') }}</v-btn>
    </v-form>

    <v-form v-else @submit.prevent="handleChallenge">
      <p class="mb-4">
        {{ useRecoveryCode
          ? $t('core.auth.msg.enter_recovery_code', 'Enter one of your recovery codes.')
          : $t('core.auth.msg.enter_authenticator_code', 'Enter the 6-digit code from your authenticator app.') }}
      </p>

      <v-text-field
        v-model="code"
        :label="useRecoveryCode
          ? $t('core.auth.object.two_factor.recovery_code.label', 'Recovery code')
          : $t('core.auth.object.two_factor.code.label', 'Authentication code')"
        :inputmode="useRecoveryCode ? 'text' : 'numeric'"
        autocomplete="one-time-code"
        autofocus
        required
      />

      <v-alert v-if="errorMessage" type="error" density="compact" class="mb-4">
        {{ errorMessage }}
      </v-alert>

      <v-btn type="submit" color="primary" block :loading="verifying">{{ $t('core.auth.action.verify', 'Verify') }}</v-btn>

      <div class="d-flex justify-space-between mt-4">
        <v-btn variant="text" size="small" @click="toggleRecoveryCode">
          {{ useRecoveryCode
            ? $t('core.auth.action.use_authenticator_code', 'Use an authenticator code')
            : $t('core.auth.action.use_recovery_code', 'Use a recovery code') }}
        </v-btn>
        <v-btn variant="text" size="small" @click="backToCredentials">
          {{ $t('core.auth.action.back_to_sign_in', 'Back to sign in') }}
        </v-btn>
      </div>
    </v-form>
  </AuthCard>
</template>
