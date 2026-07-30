<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('disk', 50)->nullable()->change();
            $table->text('path')->nullable()->change();
            $table->string('mime_type', 100)->nullable()->change();
            $table->unsignedBigInteger('size')->nullable()->change();
            $table->string('external_url', 2048)->nullable()->after('path');
            $table->unsignedInteger('sort_order')->default(0)->after('type');
        });

        Schema::table('page_sections', fn (Blueprint $table) => $table->dropColumn('media'));
        Schema::table('section_items', fn (Blueprint $table) => $table->dropColumn('media'));
    }

    public function down(): void
    {
        Schema::table('page_sections', fn (Blueprint $table) => $table->text('media')->nullable());
        Schema::table('section_items', fn (Blueprint $table) => $table->text('media')->nullable());
        Schema::table('files', fn (Blueprint $table) => $table->dropColumn(['external_url', 'sort_order']));
    }
};
