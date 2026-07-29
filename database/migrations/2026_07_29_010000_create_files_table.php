<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('file_name')->nullable();
            $table->string('disk', 50)->default('local');
            $table->text('path');
            $table->string('mime_type', 100);
            $table->bigInteger('size');
            $table->string('model_type', 100)->nullable();
            $table->bigInteger('model_id')->nullable();
            $table->timestamps();
            $table->string('type', 100)->nullable();

            $table->index(['model_type', 'model_id'], 'files_model_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
