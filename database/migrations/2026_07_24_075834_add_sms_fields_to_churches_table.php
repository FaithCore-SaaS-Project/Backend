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
            $table->integer('free_sms_limit')->default(0)->after('department_limit');
        });

        Schema::table('churches', function (Blueprint $table) {
            $table->integer('monthly_sms_used')->default(0)->after('status');
            $table->integer('topup_sms_balance')->default(0)->after('monthly_sms_used');
            $table->string('sms_sender_id')->nullable()->after('topup_sms_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn(['monthly_sms_used', 'topup_sms_balance', 'sms_sender_id']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('free_sms_limit');
        });
    }
};
