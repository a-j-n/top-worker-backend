<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\TopPerson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = collect($this->categories());
        $categoryIds = $categories->pluck('id');

        Category::query()->upsert(
            $categories->all(),
            ['id'],
            ['name', 'name_ar', 'slug', 'icon']
        );

        $obsoleteCategoryIds = Category::query()
            ->whereNotIn('id', $categoryIds)
            ->orderBy('id')
            ->pluck('id');

        $this->reassignTopPeople($obsoleteCategoryIds, $categoryIds);

        Category::query()
            ->whereNotIn('id', $categoryIds)
            ->delete();
    }

    /**
     * @return list<array{id: int, name: string, name_ar: string, slug: string, icon: null}>
     */
    private function categories(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Plumbing',
                'name_ar' => 'سباكة',
                'slug' => 'plumbing',
                'icon' => null,
            ],
            [
                'id' => 2,
                'name' => 'Carpentry',
                'name_ar' => 'نجارة',
                'slug' => 'carpentry',
                'icon' => null,
            ],
            [
                'id' => 3,
                'name' => 'Painting',
                'name_ar' => 'دهانات',
                'slug' => 'painting',
                'icon' => null,
            ],
            [
                'id' => 4,
                'name' => 'Air Conditioning / HVAC',
                'name_ar' => 'تكييفات',
                'slug' => 'air-conditioning-hvac',
                'icon' => null,
            ],
            [
                'id' => 5,
                'name' => 'Electrical Work',
                'name_ar' => 'كهرباء',
                'slug' => 'electrical-work',
                'icon' => null,
            ],
        ];
    }

    /**
     * @param  Collection<int, int>  $obsoleteCategoryIds
     * @param  Collection<int, int>  $replacementCategoryIds
     */
    private function reassignTopPeople(Collection $obsoleteCategoryIds, Collection $replacementCategoryIds): void
    {
        if ($obsoleteCategoryIds->isEmpty() || $replacementCategoryIds->isEmpty()) {
            return;
        }

        $obsoleteCategoryIds->values()->each(function (int $obsoleteCategoryId, int $index) use ($replacementCategoryIds): void {
            $replacementCategoryId = $replacementCategoryIds[$index % $replacementCategoryIds->count()];

            TopPerson::query()
                ->where('category_id', $obsoleteCategoryId)
                ->update(['category_id' => $replacementCategoryId]);
        });
    }
}
