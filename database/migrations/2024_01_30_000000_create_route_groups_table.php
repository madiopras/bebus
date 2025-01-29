<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('start_location_id')->constrained('locations');
            $table->foreignId('end_location_id')->constrained('locations');
            $table->integer('time_difference')->comment('Selisih waktu dalam menit');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignId('updated_by_id')->constrained('users');
            $table->timestamps();
        });

        // Tambah kolom route_group_id ke tabel routes
        Schema::table('routes', function (Blueprint $table) {
            $table->foreignId('route_group_id')->nullable()->after('id')->constrained('route_groups');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['route_group_id']);
            $table->dropColumn('route_group_id');
        });
        
        Schema::dropIfExists('route_groups');
    }
}; 