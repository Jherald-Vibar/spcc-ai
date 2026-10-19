<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Test accounts (password for all: "password"). Change or remove before deploying.
     */
    public function run(): void
    {
        $accounts = [
            ['Administrator', 'admin_spcc@spcc.edu.ph', User::ROLE_ADMIN],
            ['Maria Santos', 'santos_maria@spcc.edu.ph', User::ROLE_TEACHER],
            ['Juan Dela Cruz', 'delacruz_juan@spcc.edu.ph', User::ROLE_STUDENT],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);

            $user->forceFill([
                'name' => $name,
                'password' => 'password', // hashed by the model cast
                'role' => $role,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
