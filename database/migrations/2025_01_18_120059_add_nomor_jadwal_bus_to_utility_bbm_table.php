<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('utility_bbm', function (Blueprint $table) {
            $table->string('nomor_jadwal_bus')->after('schedule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_bbm', function (Blueprint $table) {
            $table->dropColumn('nomor_jadwal_bus');
        });
    }
};
