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
        Schema::create('members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('church_id')
          ->constrained();
    $table->foreignId('family_id')
          ->nullable()
          ->constrained();
    $table->string('member_no')
          ->unique();
    $table->string('first_name');
    $table->string('last_name');
    $table->enum('gender', [
        'male',
        'female'
    ]);
    $table->date('dob')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->text('address')->nullable();
    $table->date('baptism_date')->nullable();
    $table->date('membership_date')->nullable();
    $table->string('occupation')->nullable();
    $table->string('photo')->nullable();
    $table->boolean('status')
          ->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
