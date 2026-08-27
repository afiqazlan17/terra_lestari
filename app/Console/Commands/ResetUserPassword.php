<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Signature('users:reset-password {email}')]
#[Description('Generate a new random password for a user and print it once (for account recovery when the forgot-password email cannot be delivered)')]
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

        $password = Str::password(14, symbols: false);
        $user->update(['password' => Hash::make($password)]);

        $this->warn("Password baru untuk {$user->email}: {$password}");
        $this->warn('Sila log masuk dan tukar password serta-merta melalui halaman Profile.');
    }
}
