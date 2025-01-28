<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->date('tanggal')->default(now());
            $table->text('alasan');
            $table->decimal('estimasi_refund', 10, 2);
            $table->decimal('persentase', 5, 2);
            $table->timestamps();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refunds');
    }
}; 