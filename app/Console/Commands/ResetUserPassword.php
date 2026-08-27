<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

#[Signature('users:reset-password {email} {password?}')]
#[Description('Set a new password for a user (random if not given) and force them to change it on next login')]
class ResetUserPassword extends Command
{
    public function handle(): void
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Tiada akaun dengan emel: {$email}");

            return;
        }

        $password = $this->argument('password');

        if ($password) {
            try {
                validator(['password' => $password], ['password' => Password::defaults()])->validate();
            } catch (ValidationException $e) {
                $this->error(implode(' ', $e->validator->errors()->all()));

                return;
            }
        } else {
            $password = Str::password(14, symbols: false);
        }

        $user->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        $this->warn("Password sementara untuk {$user->email}: {$password}");
        $this->warn('Akaun ini akan dipaksa tukar password sebaik sahaja log masuk kali pertama.');
    }
}
