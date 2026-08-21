<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->unique()->after('category_name');
        });

        // Generate slug for existing categories
        $categories = DB::table('categories')->get();
        foreach ($categories as $category) {
            $base = Str::slug($category->category_name) ?: 'danh-muc-' . $category->id;
            $slug = $base;
            $counter = 2;
            while (DB::table('categories')->where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $base . '-' . $counter++;
            }
            DB::table('categories')->where('id', $category->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
