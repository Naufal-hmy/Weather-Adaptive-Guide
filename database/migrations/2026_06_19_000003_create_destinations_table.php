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
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->enum('category', ['indoor', 'outdoor'])->default('outdoor');
            $table->string('image_url')->nullable();
            $table->string('opening_hours')->nullable()->default('08:00 - 17:00');
            $table->decimal('rating', 3, 1)->default(4.0);
            $table->integer('min_temp')->nullable();
            $table->integer('max_temp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
