<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_days', function (Blueprint $table) {
            $table->id('id');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('description');
            $table->float('price_percentage');
            $table->boolean('is_increase');
            $table->boolean('is_active');
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
        Schema::dropIfExists('special_days');
    }
}
