<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name_ar');
            $table->string('icon')->nullable()->after('slug');
        });

        $existingSlugs = [];

        DB::table('categories')
            ->select('id', 'name')
            ->orderBy('id')
            ->get()
            ->each(function (object $category) use (&$existingSlugs): void {
                $baseSlug = Str::slug($category->name);

                if ($baseSlug === '') {
                    $baseSlug = 'category';
                }

                $slug = $baseSlug;

                if (in_array($slug, $existingSlugs, true)) {
                    $slug = "{$baseSlug}-{$category->id}";
                }

                $existingSlugs[] = $slug;

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['slug' => $slug]);
            });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'icon']);
        });
    }
};
