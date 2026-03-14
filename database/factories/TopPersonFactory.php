<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\TopPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopPerson>
 */
class TopPersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('+201#########'),
            'avatar' => null,
            'category_id' => Category::factory(),
            'is_approved' => fake()->boolean(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'is_approved' => true,
        ]);
    }
}
