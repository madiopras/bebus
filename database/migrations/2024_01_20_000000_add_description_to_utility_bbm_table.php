<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('utility_bbm', function (Blueprint $table) {
            $table->text('description')->nullable()->after('total_aktual_harga_bbm');
        });
    }

    public function down()
    {
        Schema::table('utility_bbm', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}; 