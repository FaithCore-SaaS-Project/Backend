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
        Schema::create('certificates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('church_id')
          ->constrained();
    $table->foreignId('member_id')
          ->constrained();
    $table->string('certificate_type');
    $table->string('certificate_number')
          ->unique();
    $table->date('issue_date');
    $table->string('pdf_file')
          ->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
