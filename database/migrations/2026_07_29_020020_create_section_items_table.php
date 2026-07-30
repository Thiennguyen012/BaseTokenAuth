<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_section_id')->constrained('page_sections')->cascadeOnDelete();
            $table->string('title', 255)->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->text('content')->nullable();
            $table->text('media')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['page_section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_items');
    }
};
