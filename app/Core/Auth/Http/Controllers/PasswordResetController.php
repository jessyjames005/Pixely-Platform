<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Support\AuthApiError;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * "Forgot password" flow: request a reset link by email, then set a new
 * password with the token it carries.
 */
#[Group('Authentication', weight: 2)]
final class PasswordResetController
{
    /**
     * Email a password reset link.
     *
     * The response is identical whether or not the address belongs to an
     * account, so this endpoint cannot be used to discover registered emails.
     */
    public function forgot(Request $request): Response
    {
        $input = Validator::make(
            (array) $request->json()->all(),
            ['email' => ['required', 'email']],
        )->validate();

        Password::sendResetLink(['email' => (string) $input['email']]);

        return response()->noContent();
    }

    /**
     * Set a new password using a reset token.
     */
    public function reset(Request $request): Response
    {
        $input = Validator::make(
            (array) $request->json()->all(),
            [
                'token' => ['required', 'string'],
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
        )->validate();

        $status = Password::reset(
            [
                'email' => (string) $input['email'],
                'password' => (string) $input['password'],
                'token' => (string) $input['token'],
            ],
            function (User $user, string $password): void {
                // Rotating the remember token signs out every "remember me"
                // session of this account, including an attacker's.
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw AuthApiError::invalidResetToken();
        }

        return response()->noContent();
    }
}
