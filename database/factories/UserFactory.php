<?php

namespace Database\Factories;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
            'employee_role' => 'System Admin',
        ])->afterCreating(function (User $user) {
            $role = Role::findOrCreate('admin', 'web');
            $user->assignRole($role);
        });
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'employee',
            'employee_role' => fake()->randomElement(['Project Manager', 'Staff Operasional', 'Finance', 'Engineer']),
        ])->afterCreating(function (User $user) {
            $role = Role::findOrCreate('employee', 'web');
            $user->assignRole($role);
        });
    }
}