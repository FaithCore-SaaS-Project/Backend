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
        Schema::create('churches', function (Blueprint $table) {
    $table->id();
    $table->string('church_name');
    $table->string('registration_no')->nullable();
    $table->string('pastor_name');
    $table->string('email');
    $table->string('phone')->nullable();
    $table->text('address')->nullable();
    $table->string('city')->nullable();
    $table->string('country')->default('Sri Lanka');
    $table->string('logo')->nullable();
    $table->enum('status', [
        'active',
        'inactive'
    ])->default('active');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};
