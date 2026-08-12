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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('tenants')->onDelete('cascade');
            $table->string('receipt_no');
            $table->date('receipt_date');
            $table->string('member_name');
            $table->string('member_email')->nullable();
            $table->string('member_phone')->nullable();
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('status')->default('Emailed');
            $table->string('received_by');
            $table->text('description')->nullable();
            $table->timestamps();

            // A receipt number must be unique per church/tenant
            $table->unique(['church_id', 'receipt_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
