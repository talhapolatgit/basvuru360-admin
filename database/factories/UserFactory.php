<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ad' => fake()->firstName(),
            'soyad' => fake()->lastName(),
            'tc_kimlik_no' => fake()->unique()->numerify('###########'),
            'dogum_tarihi' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'telefon' => fake()->numerify('05##########'),
            'il' => fake()->city(),
            'ilce' => fake()->city(),
            'adres' => fake()->address(),
            'aktif' => true,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->roller()->exists()) {
                return;
            }

            $personelId = Rol::query()->where('kod', 'personel')->value('id');
            if ($personelId) {
                $user->syncRoller([(int) $personelId]);
            }
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function personel(): static
    {
        return $this->afterCreating(function (User $user) {
            $id = Rol::query()->where('kod', 'personel')->value('id');
            if ($id) {
                $user->syncRoller([(int) $id]);
            }
        });
    }

    public function ogretmen(): static
    {
        return $this->afterCreating(function (User $user) {
            $id = Rol::query()->where('kod', 'ogretmen')->value('id');
            if ($id) {
                $user->syncRoller([(int) $id]);
            }
        });
    }
}
