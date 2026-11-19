<script setup lang="ts">
withDefaults(
  defineProps<{
    email?: string
  }>(),
  {
    email: 'hello@example.com',
  },
)
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
          <v-icon
            icon="mdi-letter-m"
            size="28"
            color="#5B5BF5"
          />
        </div>
        <h1 class="text-h5 font-weight-bold mb-2">
          Verify your email
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Account activation link sent to your email address:
        </p>
      </div>

      <div class="text-center mb-8">
        <span class="text-h6 font-weight-bold">{{ props.email }}</span>
        <span class="text-caption text-medium-emphasis ml-2">
          {{ $t('auth.please_follow_link', 'Please follow the link inside to continue.') }}
        </span>
      </div>

      <div class="d-flex justify-center mb-8">
        <v-btn
          color="primary"
          variant="flat"
          block
        >
          {{ $t('common.action.skip_for_now', 'Skip For Now') }}
        </v-btn>
      </div>

      <div class="text-center mt-8 border-t border-opacity-10 border-gray-light pt-6">
        <span class="text-caption text-medium-emphasis mr-2">
          {{ $t('auth.didnt_get_mail', "Didn't get the mail?") }}
        </span>
        <a
          href="#"
          class="text-primary font-weight-medium text-decoration-underline"
        >
          {{ $t('common.action.resend', 'Resend') }}
        </a>
      </div>
    </v-card>
  </div>
</template>