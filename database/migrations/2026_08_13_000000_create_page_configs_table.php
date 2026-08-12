<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->json('addresses')->nullable();
            $table->string('hotline', 255)->nullable();
            $table->string('working_hour', 255)->nullable();
            $table->text('socials')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_configs');
    }
};
