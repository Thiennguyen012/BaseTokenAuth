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
        Schema::table('products', function (Blueprint $table): void {
            $table->string('slug', 255)->nullable()->after('product_name');
        });

        $used = [];
        DB::table('products')->orderBy('id')->get(['id', 'product_name'])->each(function ($product) use (&$used): void {
            $base = Str::slug($product->product_name) ?: 'san-pham-' . $product->id;
            $slug = $base;
            $suffix = 2;

            while (isset($used[$slug])) {
                $slug = $base . '-' . $suffix++;
            }

            $used[$slug] = true;
            DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
