<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('scheduleseats', function (Blueprint $table) {
        $table->integer('passangers_id')->after('schedule_rute_id')->nullable(); // Menambahkan kolom booking_id setelah schedule_id
    });
}

public function down()
{
    Schema::table('scheduleseats', function (Blueprint $table) {
        $table->dropColumn('passangers_id');// Menghapus kolom booking_id jika rollback
    });
}
};
