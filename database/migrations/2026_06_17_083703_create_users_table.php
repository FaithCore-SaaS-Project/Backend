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
    $table->foreignId('church_id')
          ->constrained();

    $table->string('first_name');
    $table->string('last_name');
    $table->string('email')->unique();
    $table->string('phone')->nullable();
    $table->string('password');
    $table->string('profile_photo')->nullable();
    $table->timestamp('last_login')
          ->nullable();
    $table->boolean('status')
          ->default(true);
    $table->rememberToken();
    $table->timestamps();
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
