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
        Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('church_id')
          ->constrained();
    $table->foreignId('category_id')
          ->constrained('document_categories');
    $table->string('title');
    $table->string('file_path');
    $table->foreignId('uploaded_by')
          ->constrained('users');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
