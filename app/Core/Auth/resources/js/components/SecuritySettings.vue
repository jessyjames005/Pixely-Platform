<script setup lang="ts">
// "Security" section of the My Profile screen: change the password and
// manage two-factor authentication (authenticator app + recovery codes).
// Shared by the admin and user-space profile screens.
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useNotify } from '@shared/composables/useNotify'
import { translate as t } from '@shared/plugins/i18n'
import { authErrorMessage } from '../composables/authErrors'
import QrCode from './QrCode.vue'
import { useSecurityStore, type TwoFactorEnrolment } from '../store/security.store'

type SensitiveAction = 'disable' | 'regenerate'

const securityStore = useSecurityStore()
const notify = useNotify()

const status = computed(() => securityStore.status)

// --- Change password -------------------------------------------------------
const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')

const {
  loading: changingPassword,
  error: passwordError,
  execute: submitPasswordChange,
} = useApi(securityStore.changePassword)

async function handleChangePassword(): Promise<void> {
  await submitPasswordChange({
    currentPassword: currentPassword.value,
    password: newPassword.value,
    passwordConfirmation: newPasswordConfirmation.value,
  })

  if (passwordError.value) return

  currentPassword.value = ''
  newPassword.value = ''
  newPasswordConfirmation.value = ''
  notify.success(t('core.auth.msg.password_changed', 'Password changed.'))
}

// --- Two-factor enrolment --------------------------------------------------
const enablePassword = ref('')
const enrolment = ref<TwoFactorEnrolment | null>(null)
const confirmationCode = ref('')
const recoveryCodes = ref<string[]>([])

const { loading: fetchingStatus, execute: fetchStatus } = useApi(securityStore.fetchTwoFactorStatus)
const {
  loading: starting,
  error: startError,
  execute: submitEnable,
} = useApi(securityStore.enableTwoFactor)
const {
  loading: confirming,
  error: confirmError,
  execute: submitConfirm,
} = useApi(securityStore.confirmTwoFactor)

async function handleEnable(): Promise<void> {
  const result = await submitEnable(enablePassword.value)

  if (startError.value || !result) return

  enrolment.value = result
  enablePassword.value = ''
}

async function handleConfirm(): Promise<void> {
  const codes = await submitConfirm(confirmationCode.value.trim())

  if (confirmError.value || !codes) return

  recoveryCodes.value = codes
  enrolment.value = null
  confirmationCode.value = ''
  notify.success(t('core.auth.msg.two_factor_enabled', 'Two-factor authentication enabled.'))
}

function cancelEnrolment(): void {
  enrolment.value = null
  confirmationCode.value = ''
  confirmError.value = null
}

// --- Disable / regenerate (both re-check the password) ---------------------
const sensitiveAction = ref<SensitiveAction | null>(null)
const sensitivePassword = ref('')

const dialogOpen = computed({
  get: () => sensitiveAction.value !== null,
  set: (open: boolean) => {
    if (!open) closeDialog()
  },
})

const {
  loading: sensitiveLoading,
  error: sensitiveError,
  execute: runSensitive,
} = useApi(async (action: SensitiveAction, password: string): Promise<string[] | null> => {
  if (action === 'disable') {
    await securityStore.disableTwoFactor(password)
    return null
  }

  return securityStore.regenerateRecoveryCodes(password)
})

function openDialog(action: SensitiveAction): void {
  sensitiveError.value = null
  sensitivePassword.value = ''
  sensitiveAction.value = action
}

function closeDialog(): void {
  sensitiveAction.value = null
  sensitivePassword.value = ''
}

async function handleSensitive(): Promise<void> {
  const action = sensitiveAction.value
  if (action === null) return

  const codes = await runSensitive(action, sensitivePassword.value)

  if (sensitiveError.value) return

  if (action === 'regenerate' && codes) {
    recoveryCodes.value = codes
    notify.success(t('core.auth.msg.recovery_codes_regenerated', 'New recovery codes generated.'))
  } else {
    recoveryCodes.value = []
    notify.success(t('core.auth.msg.two_factor_disabled', 'Two-factor authentication disabled.'))
  }

  closeDialog()
}

// --- Recovery codes --------------------------------------------------------
async function copyRecoveryCodes(): Promise<void> {
  try {
    await navigator.clipboard.writeText(recoveryCodes.value.join('\n'))
    notify.success(t('core.auth.msg.recovery_codes_copied', 'Recovery codes copied.'))
  } catch {
    notify.error(t('core.auth.msg.copy_failed', 'Copy failed. Select the codes and copy them manually.'))
  }
}

onMounted(() => {
  void fetchStatus()
})
</script>

<template>
  <div>
    <v-card class="mb-4">
      <v-card-item>
        <template #title><span class="text-subtitle-1">{{ $t('core.auth.title.change_password', 'Password') }}</span></template>
        <template #subtitle>{{ $t('core.auth.msg.change_password_subtitle', 'Choose a long, unique password.') }}</template>
      </v-card-item>
      <v-card-text>
        <v-form @submit.prevent="handleChangePassword">
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="currentPassword"
                :label="$t('core.auth.object.password.current.label', 'Current password')"
                type="password"
                autocomplete="current-password"
                density="comfortable"
                required
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="newPassword"
                :label="$t('core.auth.object.password.new.label', 'New password')"
                :hint="$t('core.auth.object.password.new.hint', 'At least 8 characters.')"
                type="password"
                autocomplete="new-password"
                density="comfortable"
                required
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="newPasswordConfirmation"
                :label="$t('core.auth.object.password.confirmation.label', 'Confirm new password')"
                type="password"
                autocomplete="new-password"
                density="comfortable"
                required
              />
            </v-col>
          </v-row>

          <v-alert v-if="passwordError" type="error" density="compact" class="mb-4">
            {{ authErrorMessage(passwordError) }}
          </v-alert>

          <v-btn type="submit" color="primary" :loading="changingPassword">
            {{ $t('core.auth.action.change_password', 'Change password') }}
          </v-btn>
        </v-form>
      </v-card-text>
    </v-card>

    <v-card :loading="fetchingStatus">
      <v-card-item>
        <template #title><span class="text-subtitle-1">{{ $t('core.auth.title.two_factor', 'Two-factor authentication') }}</span></template>
        <template #subtitle>{{ $t('core.auth.msg.two_factor_subtitle', 'Require a code from an authenticator app when you sign in.') }}</template>
        <template #append>
          <v-chip v-if="status" :color="status.enabled ? 'success' : undefined" size="small" data-testid="two-factor-status">
            {{ status.enabled ? $t('core.auth.msg.enabled', 'Enabled') : $t('core.auth.msg.disabled', 'Disabled') }}
          </v-chip>
        </template>
      </v-card-item>

      <v-card-text>
        <!-- Recovery codes: shown once, after enabling or regenerating -->
        <v-alert v-if="recoveryCodes.length" type="warning" class="mb-4" data-testid="recovery-codes">
          <p class="mb-2">
            {{ $t('core.auth.msg.recovery_codes_warning', 'Save these recovery codes somewhere safe. Each one can be used once if you lose access to your authenticator app. They will not be shown again.') }}
          </p>
          <div class="d-flex flex-wrap ga-4 mb-3">
            <code v-for="recoveryCode in recoveryCodes" :key="recoveryCode">{{ recoveryCode }}</code>
          </div>
          <v-btn size="small" variant="outlined" class="mr-2" @click="copyRecoveryCodes">
            {{ $t('core.auth.action.copy_codes', 'Copy codes') }}
          </v-btn>
          <v-btn size="small" variant="text" @click="recoveryCodes = []">
            {{ $t('core.auth.action.saved_codes', "I've saved them") }}
          </v-btn>
        </v-alert>

        <!-- Enabled -->
        <template v-if="status?.enabled">
          <p class="mb-4">
            {{ $t('core.auth.msg.recovery_codes_remaining', 'Recovery codes remaining: :count', { count: status.recoveryCodesRemaining }) }}
          </p>
          <v-btn variant="outlined" class="mr-2" @click="openDialog('regenerate')">
            {{ $t('core.auth.action.regenerate_recovery_codes', 'Regenerate recovery codes') }}
          </v-btn>
          <v-btn color="error" variant="outlined" @click="openDialog('disable')">
            {{ $t('core.auth.action.disable_two_factor', 'Disable two-factor') }}
          </v-btn>
        </template>

        <!-- Enrolment in progress -->
        <template v-else-if="enrolment">
          <p class="mb-4">
            {{ $t('core.auth.msg.setup_instructions', 'Scan the QR code with your authenticator app, then enter the 6-digit code it shows.') }}
          </p>
          <div class="mb-4">
            <QrCode :value="enrolment.otpauthUri" :label="$t('core.auth.msg.qr_code_label', 'QR code to add this account to your authenticator app')" />
          </div>
          <p class="mb-1 text-medium-emphasis">{{ $t('core.auth.msg.setup_key_hint', "Can't scan it? Enter this setup key manually:") }}</p>
          <p class="mb-2"><code data-testid="two-factor-secret">{{ enrolment.secret }}</code></p>
          <p class="mb-4">
            <a :href="enrolment.otpauthUri">{{ $t('core.auth.action.open_authenticator', 'Open in authenticator app') }}</a>
          </p>

          <v-form @submit.prevent="handleConfirm">
            <v-text-field
              v-model="confirmationCode"
              :label="$t('core.auth.object.two_factor.code.label', 'Authentication code')"
              inputmode="numeric"
              autocomplete="one-time-code"
              density="comfortable"
              required
            />

            <v-alert v-if="confirmError" type="error" density="compact" class="mb-4">
              {{ authErrorMessage(confirmError) }}
            </v-alert>

            <v-btn type="submit" color="primary" class="mr-2" :loading="confirming">
              {{ $t('core.auth.action.confirm', 'Confirm') }}
            </v-btn>
            <v-btn variant="text" @click="cancelEnrolment">{{ $t('core.auth.action.cancel', 'Cancel') }}</v-btn>
          </v-form>
        </template>

        <!-- Disabled -->
        <v-form v-else @submit.prevent="handleEnable">
          <p class="mb-4">
            {{ $t('core.auth.msg.two_factor_enable_intro', 'Confirm your password to start setting up two-factor authentication.') }}
          </p>
          <v-text-field
            v-model="enablePassword"
            :label="$t('core.entities.object.user.password.label', 'Password')"
            type="password"
            autocomplete="current-password"
            density="comfortable"
            required
          />

          <v-alert v-if="startError" type="error" density="compact" class="mb-4">
            {{ authErrorMessage(startError) }}
          </v-alert>

          <v-btn type="submit" color="primary" :loading="starting">
            {{ $t('core.auth.action.enable_two_factor', 'Enable two-factor') }}
          </v-btn>
        </v-form>
      </v-card-text>
    </v-card>

    <v-dialog v-model="dialogOpen" max-width="420">
      <v-card
        :title="sensitiveAction === 'disable'
          ? $t('core.auth.title.disable_two_factor', 'Disable two-factor authentication')
          : $t('core.auth.title.regenerate_recovery_codes', 'Regenerate recovery codes')"
      >
        <v-form @submit.prevent="handleSensitive">
          <v-card-text>
            <p class="mb-4">
              {{ sensitiveAction === 'disable'
                ? $t('core.auth.msg.disable_two_factor_confirm', 'Your account will only be protected by your password. Enter your password to continue.')
                : $t('core.auth.msg.regenerate_codes_confirm', 'Your current recovery codes will stop working. Enter your password to continue.') }}
            </p>
            <v-text-field
              v-model="sensitivePassword"
              :label="$t('core.entities.object.user.password.label', 'Password')"
              type="password"
              autocomplete="current-password"
              autofocus
              required
            />
            <v-alert v-if="sensitiveError" type="error" density="compact">
              {{ authErrorMessage(sensitiveError) }}
            </v-alert>
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="closeDialog">{{ $t('core.auth.action.cancel', 'Cancel') }}</v-btn>
            <v-btn
              type="submit"
              :color="sensitiveAction === 'disable' ? 'error' : 'primary'"
              :loading="sensitiveLoading"
            >
              {{ $t('core.auth.action.confirm', 'Confirm') }}
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-dialog>
  </div>
</template>
