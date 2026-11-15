<script setup lang="ts">
import { ref } from 'vue'

const codeLength = 6
const digits = ref(Array(codeLength).fill(''))
const activeIndex = ref(0)

function setDigit(value: string, index: number): void {
  if (/^\d$/.test(value)) {
    digits.value[index] = value
    activeIndex.value = Math.min(index + 1, codeLength - 1)
  }
}

function clearDigit(index: number): void {
  digits.value[index] = ''
  activeIndex.value = Math.max(index - 1, 0)
}

function focusInput(index: number): void {
  activeIndex.value = index
}

const code = computed(() => digits.value.join(''))
</script>

<template>
  <div class="auth-layout d-flex align-center justify-center min-vh-100 bg-gray-light">
    <v-card
      class="w-100 max-w-440 p-8"
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
        <h1 class="text-h5 font-weight-bold mb-2">Two Step Verification</h1>
        <p class="text-body-2 text-medium-emphasis mb-2">
          {{ $t('auth.two_step_description', 'We sent a verification code to your mobile. Enter the code from the mobile in the field below.') }}
        </p>
        <div class="text-h6 font-weight-bold">{{ props.email }}</div>
      </div>

      <div class="d-flex justify-center ga-4 mb-8">
        <v-text-field
          v-for="(digit, index) in digits"
          :key="index"
          v-model="digits[index]"
          type="text"
          maxlength="1"
          width="48px"
          hide-details
          variant="outlined"
          :rules="[(v) => v === '' || /^\d$/.test(v) || 'Digit only']"
          @input="setDigit($event, index)"
          @keydown.backspace.native="clearDigit(index)"
          @focus="focusInput(index)"
          :class="{ 'border-primary': activeIndex === index }"
        />
      </div>

      <div class="text-center mb-4">
        <span class="text-body-2 text-medium-emphasis">
          {{ $t('auth.type_security_code', 'Type your 6 digit security code') }}
        </span>
      </div>

      <div class="d-flex justify-center mb-8">
        <v-btn color="primary" variant="flat" block :disabled="code.value.length !== 6">
          {{ $t('common.action.verify_my_account', 'Verify My Account') }}
        </v-btn>
      </div>

      <div class="text-center mt-8 border-t border-opacity-10 border-gray-light pt-6">
        <span class="text-caption text-medium-emphasis mr-2">
          {{ $t('auth.didnt_get_code', "Didn't get the code?") }}
        </span>
        <a href="#" class="text-primary font-weight-medium text-decoration-underline">
          {{ $t('common.action.resend', 'Resend') }}
        </a>
      </div>
    </v-card>
  </div>
</template>