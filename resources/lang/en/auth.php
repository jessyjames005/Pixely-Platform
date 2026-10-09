<?php

declare(strict_types=1);

return [
    'title' => [
        'sign_in' => 'Sign in',
        'two_factor_challenge' => 'Two-factor authentication',
        'forgot_password' => 'Forgot your password?',
        'reset_password' => 'Choose a new password',
        'change_password' => 'Password',
        'two_factor' => 'Two-factor authentication',
        'disable_two_factor' => 'Disable two-factor authentication',
        'regenerate_recovery_codes' => 'Regenerate recovery codes',
    ],
    'action' => [
        'sign_in' => 'Sign in',
        'log_out' => 'Log out',
        'forgot_password' => 'Forgot your password?',
        'verify' => 'Verify',
        'use_authenticator_code' => 'Use an authenticator code',
        'use_recovery_code' => 'Use a recovery code',
        'back_to_sign_in' => 'Back to sign in',
        'send_reset_link' => 'Send reset link',
        'reset_password' => 'Reset password',
        'change_password' => 'Change password',
        'copy_codes' => 'Copy codes',
        'saved_codes' => 'I\'ve saved them',
        'regenerate_recovery_codes' => 'Regenerate recovery codes',
        'disable_two_factor' => 'Disable two-factor',
        'enable_two_factor' => 'Enable two-factor',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
        'open_authenticator' => 'Open in authenticator app',
    ],
    'msg' => [
        'invalid_credentials' => 'The provided credentials are incorrect.',
        'account_disabled' => 'This account has been disabled.',
        'invalid_two_factor_code' => 'The authentication code is invalid or has already been used.',
        'two_factor_session_expired' => 'The sign-in session expired. Sign in again.',
        'two_factor_already_enabled' => 'Two-factor authentication is already enabled.',
        'invalid_password' => 'The provided password is incorrect.',
        'invalid_reset_token' => 'This password reset link is invalid or has expired.',
        'too_many_attempts' => 'Too many attempts. Try again in :seconds seconds.',
        'enter_recovery_code' => 'Enter one of your recovery codes.',
        'enter_authenticator_code' => 'Enter the 6-digit code from your authenticator app.',
        'reset_link_sent' => 'If an account exists for this address, a password reset link is on its way.',
        'forgot_password_intro' => 'Enter your email address and we will send you a link to choose a new password.',
        'password_reset_done' => 'Your password has been changed. You can now sign in.',
        'password_changed' => 'Password changed.',
        'change_password_subtitle' => 'Choose a long, unique password.',
        'two_factor_enabled' => 'Two-factor authentication enabled.',
        'two_factor_disabled' => 'Two-factor authentication disabled.',
        'two_factor_subtitle' => 'Require a code from an authenticator app when you sign in.',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'recovery_codes_warning' => 'Save these recovery codes somewhere safe. Each one can be used once if you lose access to your authenticator app. They will not be shown again.',
        'recovery_codes_remaining' => 'Recovery codes remaining: :count',
        'recovery_codes_regenerated' => 'New recovery codes generated.',
        'recovery_codes_copied' => 'Recovery codes copied.',
        'copy_failed' => 'Copy failed. Select the codes and copy them manually.',
        'setup_instructions' => 'Scan the QR code with your authenticator app, then enter the 6-digit code it shows.',
        'qr_code_label' => 'QR code to add this account to your authenticator app',
        'setup_key_hint' => 'Can\'t scan it? Enter this setup key manually:',
        'two_factor_enable_intro' => 'Confirm your password to start setting up two-factor authentication.',
        'disable_two_factor_confirm' => 'Your account will only be protected by your password. Enter your password to continue.',
        'regenerate_codes_confirm' => 'Your current recovery codes will stop working. Enter your password to continue.',
    ],
    'object' => [
        'session' => [
            'remember' => [
                'label' => 'Remember me',
            ],
        ],
        'two_factor' => [
            'code' => [
                'label' => 'Authentication code',
            ],
            'recovery_code' => [
                'label' => 'Recovery code',
            ],
            'secret' => [
                'label' => 'Setup key',
            ],
        ],
        'password' => [
            'current' => [
                'label' => 'Current password',
            ],
            'new' => [
                'label' => 'New password',
                'hint' => 'At least 8 characters.',
            ],
            'confirmation' => [
                'label' => 'Confirm new password',
            ],
        ],
    ],
];
