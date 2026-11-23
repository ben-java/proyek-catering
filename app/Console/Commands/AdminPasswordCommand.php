<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AdminPasswordCommand extends Command
{
    protected $signature = 'catering:admin-password {password? : Password baru (minimal 12 karakter)}';

    protected $description = 'Setel ulang password akun admin (kebijakan minimal 12 karakter)';

    public function handle(): int
    {
        $password = (string) ($this->argument('password') ?? $this->secret('Password baru (minimal 12 karakter)'));

        if (strlen($password) < 12) {
            $this->error('Password minimal 12 karakter.');

            return self::FAILURE;
        }

        $admin = User::query()->where('email', 'admin')->first();

        if (! $admin) {
            $this->error('Akun admin tidak ditemukan. Jalankan seeder lebih dulu.');

            return self::FAILURE;
        }

        // Cast 'hashed' pada model User yang melakukan bcrypt.
        $admin->update(['password' => $password]);

        $this->info('Password admin diperbarui.');

        return self::SUCCESS;
    }
}
