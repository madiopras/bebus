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
            $table->string('description', 255)->nullable()->change();
        });

        Schema::table('schedule_rute', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('description', 255)->nullable(false)->change();
        });

        Schema::table('schedule_rute', function (Blueprint $table) {
            $table->string('description', 255)->nullable(false)->change();
        });
    }
};
