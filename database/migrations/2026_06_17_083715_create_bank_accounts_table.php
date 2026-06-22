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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('account_type'); // Current or Savings
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('ledger_balance', 12, 2)->default(0);
            $table->string('status')->default('Active');
            $table->string('branch')->nullable();
            $table->string('currency')->default('LKR');
            $table->date('last_statement_date')->nullable();
            $table->date('created_on')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
