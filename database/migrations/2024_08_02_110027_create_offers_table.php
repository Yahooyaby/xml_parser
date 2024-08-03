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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('mark');
            $table->string('model');
            $table->string('generation')->nullable();
            $table->unsignedInteger('year');
            $table->unsignedInteger('run');
            $table->string('color')->nullable();
            $table->string('body_type');
            $table->string('engine_type');
            $table->string('transmission');
            $table->string('gear_type');
            $table->unsignedBigInteger('generation_id')->nullable();
            $table->timestamps();

            $table->index('mark');
            $table->index('model');
            $table->index('year');
            $table->index('run');
            $table->index('color');
            $table->index('body_type');
            $table->index('engine_type');
            $table->index('transmission');
            $table->index('gear_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
