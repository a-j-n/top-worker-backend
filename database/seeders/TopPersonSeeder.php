<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\TopPerson;
use Illuminate\Database\Seeder;

class TopPersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::query()->get();

        if ($categories->isEmpty()) {
            $categories = Category::factory(5)->create();
        }

        TopPerson::factory(100)
            ->approved()
            ->state(fn (): array => [
                'category_id' => $categories->random()->id,
            ])
            ->create();
    }
}
