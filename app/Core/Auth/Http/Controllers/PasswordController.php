<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Support\AuthApiError;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Lets the authenticated user change their own password.
 */
#[Group('Authentication', weight: 2)]
final class PasswordController
{
    /**
     * Change the current user's password.
     */
    public function update(Request $request): Response
    {
        $input = Validator::make(
            (array) $request->json()->all(),
            [
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
            ],
        )->validate();

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check((string) $input['current_password'], $user->password)) {
            throw AuthApiError::invalidPassword('current_password');
        }

        $user->forceFill([
            'password' => (string) $input['password'],
            'remember_token' => Str::random(60),
        ])->save();

        return response()->noContent();
    }
}
