<script setup lang="ts">
withDefaults(
  defineProps<{
    password?: string
    confirmPassword?: string
  }>(),
  {
    password: '',
    confirmPassword: '',
  },
)

const modelPassword = ref(props.password)
const modelConfirmPassword = ref(props.confirmPassword)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
</script>

<template>
  <div class="auth-layout d-flex align-center justify-center min-vh-100 bg-gray-light">
    <v-card
      class="w-100 max-w-400 p-8"
      :style="{ borderRadius: '24px', backgroundColor: '#FFFFFF' }"
      elevation="0"
      border
    >
      <div class="text-center mb-8">
        <div
          class="auth-logo d-flex align-center justify-center mb-6"
          :style="{
            width: '48px',
            height: '48px',
            borderRadius: '12px',
            backgroundColor: '#EDEDFE',
            marginInline: 'auto',
          }"
        >
          <v-icon
            icon="mdi-letter-m"
            size="28"
            color="#5B5BF5"
          />
        </div>
        <h1 class="text-h5 font-weight-bold mb-2">
          Reset Password
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Enter your email and we'll send you instructions to reset your password
        </p>
      </div>

      <v-text-field
        v-model="modelPassword"
        label="New Password"
        prepend-inner-icon="mdi-lock-outline"
        append-inner-icon="showPassword ? 'mdi-eye-outline' : 'mdi-eye-off-outline'"
        :type="showPassword ? 'text' : 'password'"
        @click:append-inner="showPassword = !showPassword"
        rules="[(v) => !!v || 'Password is required', (v) => v!.length >= 6 || 'Minimum 6 characters']"
        class="mb-6"
      />
      <v-text-field
        v-model="modelConfirmPassword"
        label="Confirm Password"
        prepend-inner-icon="mdi-lock-outline"
        append-inner-icon="showConfirmPassword ? 'mdi-eye-outline' : 'mdi-eye-off-outline'"
        :type="showConfirmPassword ? 'text' : 'password'"
        @click:append-inner="showConfirmPassword = !showConfirmPassword"
        rules="[(v) => !!v || 'Please confirm your password', (v) => v === modelPassword || 'Passwords do not match']"
        class="mb-8"
      />

      <div class="d-flex justify-center mb-4">
        <v-btn
          color="primary"
          variant="flat"
          block
          @click="resetPassword"
        >
          {{ $t('common.action.set_new_password', 'Set New Password') }}
        </v-btn>
      </div>

      <div class="text-center mt-8 border-t border-opacity-10 border-gray-light pt-6">
        <a
          href="#"
          class="text-primary font-weight-medium text-decoration-underline"
        >
          {{ $t('auth.back_to_login', 'Back to login') }}
        </a>
      </div>
    </v-card>
  </div>
</template>