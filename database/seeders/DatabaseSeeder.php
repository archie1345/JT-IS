<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TemporaryProjectMonitoringSeeder::class,
        ]);

        // 1. Cetak 1 Akun Admin Utama
        User::factory()->admin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@jte.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. Cetak 10 Akun Karyawan Acak
        User::factory()->count(10)->employee()->create();
    }
}
