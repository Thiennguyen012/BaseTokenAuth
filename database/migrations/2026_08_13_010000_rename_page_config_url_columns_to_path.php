<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('page_configs', function (Blueprint $table): void {
            $table->renameColumn('favicon_url', 'favicon_path');
            $table->renameColumn('logo_url', 'logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('page_configs', function (Blueprint $table): void {
            $table->renameColumn('favicon_path', 'favicon_url');
            $table->renameColumn('logo_path', 'logo_url');
        });
    }
};
