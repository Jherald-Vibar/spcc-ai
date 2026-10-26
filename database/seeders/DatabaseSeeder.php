<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Administrator', 'admin_spcc@spcc.edu.ph', User::ROLE_ADMIN, null],
            ['Maria Santos', 'santos_maria@spcc.edu.ph', User::ROLE_TEACHER, null],
            ['Juan Dela Cruz', 'delacruz_juan@spcc.edu.ph', User::ROLE_STUDENT, 4],
        ];

        foreach ($accounts as [$name, $email, $role, $yearLevel]) {
            $user = User::firstOrNew(['email' => $email]);

            $user->forceFill([
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'status' => User::STATUS_ACTIVE,
                'year_level' => $yearLevel,
                'email_verified_at' => now(),
            ])->save();
        }

        $this->call(CurriculumSeeder::class);
    }
}
