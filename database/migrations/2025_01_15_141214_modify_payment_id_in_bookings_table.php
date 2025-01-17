<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Hapus foreign key constraint
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });

        // Ubah tipe kolom payment_id menjadi string
        DB::statement('ALTER TABLE bookings MODIFY payment_id VARCHAR(100)');
    }

    public function down()
    {
        // Kembalikan tipe kolom payment_id menjadi integer
        DB::statement('ALTER TABLE bookings MODIFY payment_id BIGINT');

        // Kembalikan foreign key constraint
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments');
        });
    }
};
