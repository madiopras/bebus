<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleseatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduleseats', function (Blueprint $table) {
            $table->id('id');
            $table->integer('schedule_id');
            $table->string('booking_id');
            $table->integer('seat_id');
            $table->integer('schedule_rute_id');
            $table->integer('passengers_id');
            $table->boolean('is_available');
            $table->string('description');
            $table->integer('created_by_id');
            $table->timestamps();
            $table->integer('updated_by_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheduleseats');
    }
}
