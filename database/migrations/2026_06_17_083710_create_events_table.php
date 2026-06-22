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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained();
            $table->string('event_name');
            $table->string('subtitle')->nullable();
            $table->string('type')->default('Worship');
            $table->dateTime('event_date');
            $table->string('event_time')->default('8:00 AM - 10:00 AM');
            $table->string('venue'); // acts as location
            $table->integer('attendees')->default(0);
            $table->integer('max_capacity')->default(100);
            $table->string('status')->default('Upcoming');
            $table->string('organizer')->default('Pastor John');
            $table->longText('description')->nullable();
            $table->date('created_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
