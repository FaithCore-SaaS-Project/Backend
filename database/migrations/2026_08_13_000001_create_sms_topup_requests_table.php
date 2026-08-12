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
        Schema::create('sms_topup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->onDelete('cascade');
            $table->integer('amount'); // Number of SMS credits requested
            $table->decimal('price', 10, 2); // Cost in currency
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_topup_requests');
    }
};
