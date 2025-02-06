<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListGroupRouteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('list_group_route', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_group_id')->constrained('route_groups')->onDelete('cascade');
            $table->unsignedBigInteger('route_id');
            $table->integer('time_difference');
            $table->unsignedBigInteger('start_location_id');
            $table->unsignedBigInteger('end_location_id');
            $table->integer('created_by_id');
            $table->integer('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('start_location_id')->references('id')->on('locations');
            $table->foreign('end_location_id')->references('id')->on('locations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('list_group_route');
    }
} 