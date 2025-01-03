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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->unsignedBigInteger('created_by_id')->nullable()->after('is_active');
            $table->unsignedBigInteger('updated_by_id')->nullable()->after('created_by_id');

            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['updated_by_id']);
            $table->dropColumn(['role', 'is_active', 'created_by_id', 'updated_by_id']);
        });
    }
};
