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
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn([
                'has_ac',
                'has_toilet',
                'has_tv',
                'has_music',
                'has_air_mineral',
                'has_wifi',
                'has_snack',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_toilet')->default(false);
            $table->boolean('has_tv')->default(false);
            $table->boolean('has_music')->default(false);
            $table->boolean('has_air_mineral')->default(false);
            $table->boolean('has_wifi')->default(false);
            $table->boolean('has_snack')->default(false);
        });
    }
}; 