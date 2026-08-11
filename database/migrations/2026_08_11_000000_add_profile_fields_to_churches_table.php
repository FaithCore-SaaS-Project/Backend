<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('cover_image')->nullable();
            $table->text('about')->nullable();
            $table->string('year_established')->nullable();
            $table->string('website')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('twitter')->nullable();
            $table->json('visibility_settings')->nullable();
            $table->string('currency')->default('LKR');
            $table->string('timezone')->default('Asia/Colombo');
            $table->string('date_format')->default('Y-m-d');
        });
    }

    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn([
                'cover_image',
                'about',
                'year_established',
                'website',
                'facebook',
                'instagram',
                'youtube',
                'twitter',
                'visibility_settings',
                'currency',
                'timezone',
                'date_format'
            ]);
        });
    }
};
