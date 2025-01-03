<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('id');
            $table->integer('booking_id');
            $table->string('payment_method');
            $table->dateTime('payment_date');
            $table->decimal('amount', 15, 2);
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
        Schema::dropIfExists('payments');
    }
}
