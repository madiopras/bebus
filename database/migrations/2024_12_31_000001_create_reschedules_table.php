<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_previous_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('booking_new_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('schedule_rute_id')->constrained('schedule_rute')->onDelete('cascade');
            $table->decimal('harga_baru', 10, 2);
            $table->text('alasan');
            $table->timestamps();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reschedules');
    }
}; 