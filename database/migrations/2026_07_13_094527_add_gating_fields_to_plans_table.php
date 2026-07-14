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
        Schema::table('plans', function (Blueprint $table) {
            $table->json('features')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->string('badge')->nullable();
            $table->integer('department_limit')->default(3);
            $table->string('stripe_price_id')->nullable();
            $table->string('payhere_item_number')->nullable();
            $table->renameColumn('storage_limit', 'storage_limit_mb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('storage_limit_mb', 'storage_limit');
            $table->dropColumn(['features', 'is_popular', 'badge', 'department_limit', 'stripe_price_id', 'payhere_item_number']);
        });
    }
};
