<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id('id');
            $table->string('class_name');
            $table->text('description')->nullable();
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_toilet')->default(false);
            $table->boolean('has_tv')->default(false);
            $table->boolean('has_music')->default(false);
            $table->boolean('has_air_mineral')->default(false);
            $table->boolean('has_wifi')->default(false);
            $table->boolean('has_snack')->default(false);
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('classes');
    }
}
