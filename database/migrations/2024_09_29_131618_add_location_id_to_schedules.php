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
        Schema::table('schedules', function (Blueprint $table) {
            $table->integer('location_id')->after('id');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->dropColumn('location_id');
        });
    }
};
