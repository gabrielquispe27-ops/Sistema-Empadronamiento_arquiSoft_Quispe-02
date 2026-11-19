<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password2026!'),
            'role' => User::ROLE_BRIGADISTA,
            'remember_token' => Str::random(10),
        ];
    }

    public function coordinador(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => User::ROLE_COORDINADOR,
        ]);
    }

    public function brigadista(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => User::ROLE_BRIGADISTA,
        ]);
    }
}
