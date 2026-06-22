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
        Schema::create('finance_income', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained();
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('income_date');
            $table->string('method')->default('Cash');
            $table->string('receipt')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_income');
    }
};
