<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * List all categories for admin management.
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()->orderBy('name')->get()
        );
    }

    /**
     * Create a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = $this->makeUniqueSlug($validated['slug'] ?? $validated['name']);

        if ($request->hasFile('icon')) {
            $validated['icon'] = $request->file('icon')->storePublicly('categories');
        }

        $category = Category::query()->create($validated);

        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a single category.
     */
    public function show(Category $category): CategoryResource
    {
        return CategoryResource::make($category);
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $validated = $request->validated();

        if ($request->hasFile('icon')) {
            $this->deleteIcon($category);
            $validated['icon'] = $request->file('icon')->storePublicly('categories');
        } elseif ($request->exists('icon') && $request->input('icon') === null) {
            $this->deleteIcon($category);
            $validated['icon'] = null;
        }

        if (array_key_exists('slug', $validated) || array_key_exists('name', $validated)) {
            $validated['slug'] = $this->makeUniqueSlug(
                $validated['slug'] ?? $validated['name'] ?? $category->slug ?? $category->name,
                $category->id,
            );
        }

        $category->update($validated);

        return CategoryResource::make($category->refresh());
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): Response
    {
        $icon = $category->icon;
        $category->delete();

        if ($icon !== null) {
            Storage::delete($icon);
        }

        return response()->noContent();
    }

    private function deleteIcon(Category $category): void
    {
        if ($category->icon === null) {
            return;
        }

        Storage::delete($category->icon);
    }

    private function makeUniqueSlug(string $value, ?int $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($value);

        if ($baseSlug === '') {
            $baseSlug = 'category';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreCategoryId)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreCategoryId = null): bool
    {
        return Category::query()
            ->when($ignoreCategoryId !== null, fn ($query) => $query->whereKeyNot($ignoreCategoryId))
            ->where('slug', $slug)
            ->exists();
    }
}
