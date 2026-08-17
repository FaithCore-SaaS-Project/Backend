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
        Schema::table('finance_income', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable()->after('church_id');
            $table->unsignedBigInteger('recorded_by')->nullable()->after('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_income', function (Blueprint $table) {
            $table->dropColumn(['member_id', 'recorded_by']);
        });
    }
};
