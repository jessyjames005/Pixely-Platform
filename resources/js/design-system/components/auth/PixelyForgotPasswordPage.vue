<script setup lang="ts">
withDefaults(
  defineProps<{
    email?: string
  }>(),
  {
    email: '',
  },
)

const modelEmail = ref(props.email)
</script>

<template>
  <div class="auth-layout d-flex align-center justify-center min-vh-100 bg-gray-light">
    <v-card
      class="w-100 max-w-480 p-8"
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
          <v-icon icon="mdi-letter-m" size="28" color="#5B5BF5" />
        </div>
        <h1 class="text-h5 font-weight-bold mb-2">Forgot Password</h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Enter your email and we'll send you instructions to reset your password.
        </p>
      </div>

      <v-text-field
        v-model="modelEmail"
        label="Email"
        prepend-inner-icon="mdi-email-outline"
        rules="[(v) => !!v || 'Email is required', (v) => /.+@.+\..+/.test(v) || 'Invalid email']"
        class="mb-8"
      />

      <div class="d-flex justify-center mb-4">
        <v-btn color="primary" variant="flat" block @click="resetPassword">
          {{ $t('common.action.send_email', 'Send Email') }}
        </v-btn>
      </div>

      <div class="text-center mt-8 border-t border-opacity-10 border-gray-light pt-6">
        <a href="#" class="text-primary font-weight-medium text-decoration-underline">
          {{ $t('auth.back_to_login', 'Back to login') }}
        </a>
      </div>
    </v-card>
  </div>
</template>