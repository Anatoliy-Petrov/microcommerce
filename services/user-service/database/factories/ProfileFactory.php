<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Profile>
 */
final class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'id'           => Str::uuid()->toString(),
            'display_name' => $this->faker->name(),
            'bio'          => $this->faker->optional()->sentence(),
            'avatar_url'   => null,
        ];
    }
}