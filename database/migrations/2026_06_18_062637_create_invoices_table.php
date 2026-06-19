<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->string('invoice_no')->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->date('invoice_date');
            $table->string('pdf_file')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
