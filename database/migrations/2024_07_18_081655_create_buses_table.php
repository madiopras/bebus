<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buses', function (Blueprint $table) {
            $table->id('id');
            $table->string('bus_number');
            $table->integer('capacity');
            $table->string('bus_name');
            $table->integer('class_id');
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
        Schema::dropIfExists('buses');
    }
}
