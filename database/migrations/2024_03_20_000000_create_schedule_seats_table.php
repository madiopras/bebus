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
        Schema::create('scheduleseats', function (Blueprint $table) {
            $table->id();
            $table->integer('schedule_id');
            $table->integer('booking_Id')->nullable();
            $table->integer('seat_id');
            $table->integer('schedule_rute_id')->nullable();
            $table->integer('passengers_id')->nullable();
            $table->boolean('is_available');
            $table->string('description');
            $table->integer('created_by_id');
            $table->integer('updated_by_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduleseats');
    }
}; 