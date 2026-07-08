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
        Schema::table('members', function (Blueprint $table) {
            $table->string('nic')->nullable()->after('last_name');
            $table->string('address_type')->nullable()->after('address'); // permanent, postal
            $table->text('permanent_address')->nullable()->after('address_type');
            $table->text('postal_address')->nullable()->after('permanent_address');
            $table->boolean('is_baptized')->default(false)->after('dob');
            $table->string('baptism_church')->nullable()->after('is_baptized');
            $table->string('baptism_partner_name')->nullable()->after('baptism_church');
            $table->string('baptism_certificate')->nullable()->after('baptism_partner_name');
            $table->string('marital_status')->nullable()->after('occupation'); // single, married
            $table->date('marriage_date')->nullable()->after('marital_status');
            $table->string('marriage_certificate')->nullable()->after('marriage_date');
            $table->string('birth_certificate')->nullable()->after('dob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'nic',
                'address_type',
                'permanent_address',
                'postal_address',
                'is_baptized',
                'baptism_church',
                'baptism_partner_name',
                'baptism_certificate',
                'marital_status',
                'marriage_date',
                'marriage_certificate',
                'birth_certificate'
            ]);
        });
    }
};
