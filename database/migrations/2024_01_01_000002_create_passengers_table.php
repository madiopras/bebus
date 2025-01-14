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
        Schema::create('passengers', function (Blueprint $table) {
            $table->id('id');
            $table->integer('booking_id');
            $table->integer('schedule_seat_id');
            $table->string('name');
            $table->string('phone_number');
            $table->string('description')->nullable();
            $table->integer('created_by_id');
            $table->timestamps();
            $table->integer('updated_by_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passangers');
    }
};
