<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('id');
            $table->integer('user_id');
            $table->integer('schedule_id');
            $table->dateTime('booking_date');
            $table->string('payment_status');
            $table->decimal('final_price', 15, 2);
            $table->integer('voucher_id')->nullable();
            $table->integer('specialdays_id')->nullable();
            $table->string('description')->nullable();
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
        Schema::dropIfExists('bookings');
    }
}
