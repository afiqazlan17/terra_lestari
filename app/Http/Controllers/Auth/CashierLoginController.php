<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashierLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.cashier-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $throttleKey = 'cashier-login|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'pin' => "Terlalu banyak percubaan. Cuba lagi dalam {$seconds} saat.",
            ]);
        }

        // Any active user with a PIN set can log in here - cashiers (4-digit)
        // and owner-tier accounts (6-digit) share this one screen, and the
        // matched user's own role decides where they land afterwards.
        $user = User::whereNotNull('pin')
            ->where('is_active', true)
            ->get()
            ->first(fn (User $candidate) => Hash::check($request->string('pin'), $candidate->pin));

        if (! $user) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'pin' => 'PIN salah.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user);
        $request->session()->regenerate();

        // See AuthenticatedSessionController for why intended() is avoided.
        $home = $user->canManageOperations() ? route('dashboard', absolute: false) : route('pos.index', absolute: false);

        return redirect($home);
    }
}
