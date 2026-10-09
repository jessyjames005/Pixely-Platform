<?php

declare(strict_types=1);

return [
    'title' => [
        'sign_in' => 'Connexion',
        'two_factor_challenge' => 'Authentification à deux facteurs',
        'forgot_password' => 'Mot de passe oublié ?',
        'reset_password' => 'Choisir un nouveau mot de passe',
        'change_password' => 'Mot de passe',
        'two_factor' => 'Authentification à deux facteurs',
        'disable_two_factor' => 'Désactiver l\'authentification à deux facteurs',
        'regenerate_recovery_codes' => 'Régénérer les codes de récupération',
    ],
    'action' => [
        'sign_in' => 'Se connecter',
        'log_out' => 'Se déconnecter',
        'forgot_password' => 'Mot de passe oublié ?',
        'verify' => 'Vérifier',
        'use_authenticator_code' => 'Utiliser un code de l\'application',
        'use_recovery_code' => 'Utiliser un code de récupération',
        'back_to_sign_in' => 'Retour à la connexion',
        'send_reset_link' => 'Envoyer le lien de réinitialisation',
        'reset_password' => 'Réinitialiser le mot de passe',
        'change_password' => 'Changer le mot de passe',
        'copy_codes' => 'Copier les codes',
        'saved_codes' => 'Je les ai enregistrés',
        'regenerate_recovery_codes' => 'Régénérer les codes de récupération',
        'disable_two_factor' => 'Désactiver la double authentification',
        'enable_two_factor' => 'Activer la double authentification',
        'confirm' => 'Confirmer',
        'cancel' => 'Annuler',
        'open_authenticator' => 'Ouvrir dans l\'application d\'authentification',
    ],
    'msg' => [
        'invalid_credentials' => 'Les identifiants fournis sont incorrects.',
        'account_disabled' => 'Ce compte a été désactivé.',
        'invalid_two_factor_code' => 'Le code d\'authentification est invalide ou a déjà été utilisé.',
        'two_factor_session_expired' => 'La session de connexion a expiré. Connectez-vous à nouveau.',
        'two_factor_already_enabled' => 'L\'authentification à deux facteurs est déjà activée.',
        'invalid_password' => 'Le mot de passe fourni est incorrect.',
        'invalid_reset_token' => 'Ce lien de réinitialisation du mot de passe est invalide ou a expiré.',
        'too_many_attempts' => 'Trop de tentatives. Réessayez dans :seconds secondes.',
        'enter_recovery_code' => 'Saisissez l\'un de vos codes de récupération.',
        'enter_authenticator_code' => 'Saisissez le code à 6 chiffres affiché par votre application d\'authentification.',
        'reset_link_sent' => 'Si un compte existe pour cette adresse, un lien de réinitialisation du mot de passe vous a été envoyé.',
        'forgot_password_intro' => 'Saisissez votre adresse email : nous vous enverrons un lien pour choisir un nouveau mot de passe.',
        'password_reset_done' => 'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.',
        'password_changed' => 'Mot de passe modifié.',
        'change_password_subtitle' => 'Choisissez un mot de passe long et unique.',
        'two_factor_enabled' => 'Authentification à deux facteurs activée.',
        'two_factor_disabled' => 'Authentification à deux facteurs désactivée.',
        'two_factor_subtitle' => 'Exiger un code d\'une application d\'authentification à chaque connexion.',
        'enabled' => 'Activée',
        'disabled' => 'Désactivée',
        'recovery_codes_warning' => 'Conservez ces codes de récupération en lieu sûr. Chacun ne peut servir qu\'une fois si vous perdez l\'accès à votre application d\'authentification. Ils ne seront plus affichés.',
        'recovery_codes_remaining' => 'Codes de récupération restants : :count',
        'recovery_codes_regenerated' => 'Nouveaux codes de récupération générés.',
        'recovery_codes_copied' => 'Codes de récupération copiés.',
        'copy_failed' => 'Échec de la copie. Sélectionnez les codes et copiez-les manuellement.',
        'setup_instructions' => 'Scannez le QR code avec votre application d\'authentification, puis saisissez le code à 6 chiffres qu\'elle affiche.',
        'qr_code_label' => 'QR code pour ajouter ce compte à votre application d\'authentification',
        'setup_key_hint' => 'Impossible de scanner ? Saisissez cette clé de configuration manuellement :',
        'two_factor_enable_intro' => 'Confirmez votre mot de passe pour commencer la configuration de l\'authentification à deux facteurs.',
        'disable_two_factor_confirm' => 'Votre compte ne sera plus protégé que par votre mot de passe. Saisissez votre mot de passe pour continuer.',
        'regenerate_codes_confirm' => 'Vos codes de récupération actuels cesseront de fonctionner. Saisissez votre mot de passe pour continuer.',
    ],
    'object' => [
        'session' => [
            'remember' => [
                'label' => 'Se souvenir de moi',
            ],
        ],
        'two_factor' => [
            'code' => [
                'label' => 'Code d\'authentification',
            ],
            'recovery_code' => [
                'label' => 'Code de récupération',
            ],
            'secret' => [
                'label' => 'Clé de configuration',
            ],
        ],
        'password' => [
            'current' => [
                'label' => 'Mot de passe actuel',
            ],
            'new' => [
                'label' => 'Nouveau mot de passe',
                'hint' => 'Au moins 8 caractères.',
            ],
            'confirmation' => [
                'label' => 'Confirmer le nouveau mot de passe',
            ],
        ],
    ],
];
