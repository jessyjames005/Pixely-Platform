<script setup lang="ts">
import { ref } from 'vue'

withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    email?: string
    password?: string
  }>(),
  {
    title: 'Login',
    subtitle: 'Please sign-in to your account and start the adventure',
    email: 'johndoe@email.com',
    password: '',
  },
)

const showPassword = ref(false)
const rememberMe = ref(false)

const modelEmail = ref(props.email)
const modelPassword = ref(props.password)
</script>

<template>
  <div class="auth-layout d-flex align-center justify-center min-vh-100 bg-gray-light">
    <v-card
      class="w-100 max-w-560 p-8"
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
          {{ title }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ subtitle }}
        </p>
      </div>

      <v-text-field
        v-model="modelEmail"
        label="Email"
        prepend-inner-icon="mdi-email-outline"
        rules="[(v) => !!v || 'Email is required', (v) => /.+@.+\..+/.test(v) || 'Invalid email']"
        class="mb-4"
      />
      <div class="d-flex align-center mb-6">
        <v-checkbox
          v-model="rememberMe"
          label="Remember me"
          class="mr-auto"
          hide-details
        />
        <a
          href="#"
          class="text-body-2 text-decoration-underline text-primary"
        >{{ $t?.('common.action.forgot_password', 'Forgot Password?') }}</a>
      </div>
      <v-text-field
        v-model="modelPassword"
        :label="$t('auth.password', 'Password')"
        :type="showPassword ? 'text' : 'password'"
        prepend-inner-icon="mdi-lock-outline"
        append-inner-icon="showPassword ? 'mdi-eye-outline' : 'mdi-eye-off-outline'"
        rules="[(v) => !!v || 'Password is required', (v) => v!.length >= 6 || 'Minimum 6 characters']"
        class="mb-8"
        @click:append-inner="showPassword = !showPassword"
      />
      <div class="d-flex justify-space-between align-center mb-4">
        <v-btn
          color="primary"
          variant="flat"
          block
          prepend-icon="mdi-login"
          @click="login"
        >
          {{ $t('common.action.login', 'Login') }}
        </v-btn>
      </div>
      <div class="text-center mt-6">
        <span class="text-body-2 text-medium-emphasis mr-2">
          {{ $t('auth.new_here', 'New on our platform?') }}
        </span>
        <a
          href="#"
          class="text-primary font-weight-medium text-decoration-underline"
        >
          {{ $t('auth.create_account', 'Create an account') }}
        </a>
      </div>
      <div class="d-flex align-center justify-center mt-8 border-t border-opacity-10 border-gray-light pt-6">
        <v-icon
          icon="mdi-facebook"
          size="18"
          color="#3B5998"
          class="mr-4"
        />
        <v-icon
          icon="mdi-twitter"
          size="18"
          color="#1DA1F2"
          class="mr-4"
        />
        <v-icon
          icon="mdi-google"
          size="18"
          color="#DB4437"
        />
      </div>
    </v-card>
  </div>
</template>