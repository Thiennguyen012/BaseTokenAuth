<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone',50)->nullable();
            $table->string('email',50)->unique();
            $table->date('birthday')->nullable();
            $table->string('address',256)->nullable();
            $table->string('avatar')->nullable();
            $table->tinyInteger('status')->default(1)->index();
            $table->tinyInteger('is_super_admin')->default(0);
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};