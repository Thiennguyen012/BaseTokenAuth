<?php

use App\Models\Products\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Product::query()
            ->whereNotNull('deleted_at')
            ->eachById(fn (Product $product) => $product->delete());

        Schema::table('products', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }
};
