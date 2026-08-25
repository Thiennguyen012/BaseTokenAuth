<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('tag_group_id')
                ->nullable()
                ->after('id')
                ->constrained('tag_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropForeign(['tag_group_id']);
            $table->dropColumn('tag_group_id');
        });

        Schema::dropIfExists('tag_groups');
    }
};
