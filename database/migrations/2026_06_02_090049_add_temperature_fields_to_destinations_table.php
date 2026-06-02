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
        Schema::table('destinations', function (Blueprint $table) {
            $table->integer('min_temp')->nullable()->after('city')->comment('Suhu minimal dalam Celcius');
            $table->integer('max_temp')->nullable()->after('min_temp')->comment('Suhu maksimal dalam Celcius');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn(['min_temp', 'max_temp']);
        });
    }
};
