<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

/**
 * Mirrors AuthenticatedSessionController::store()'s role-based redirect
 * (dashboard for staff who can manage operations, POS for cashiers) so
 * logging in via passkey lands in the same place a password login would.
 */
class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request): JsonResponse
    {
        $home = $request->user()->canManageOperations()
            ? route('dashboard', absolute: false)
            : route('pos.index', absolute: false);

        return new JsonResponse(['redirect' => $home]);
    }
}
