<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variant_groups', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'is_required']);
        });
    }

    public function down(): void
    {
        Schema::table('variant_groups', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
        });
    }
};
