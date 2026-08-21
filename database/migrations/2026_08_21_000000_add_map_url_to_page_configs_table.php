<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_configs', function (Blueprint $table): void {
            $table->text('map_url')->nullable()->after('addresses');
        });
    }

    public function down(): void
    {
        Schema::table('page_configs', function (Blueprint $table): void {
            $table->dropColumn('map_url');
        });
    }
};
