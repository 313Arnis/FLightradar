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
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('icao24', 10); // e.g. "4b1815"
            $table->string('callsign', 10)->nullable(); // e.g. "SWR4HE  "
            $table->string('origin_country', 64)->nullable(); // e.g. "Switzerland"
            $table->bigInteger('time_position')->nullable(); // e.g. 1769151287
            $table->bigInteger('last_contact')->nullable(); // e.g. 1769151289
            $table->double('longitude')->nullable();
            $table->double('latitude')->nullable();
            $table->double('baro_altitude')->nullable();
            $table->boolean('on_ground')->default(false);
            $table->double('velocity')->nullable();
            $table->double('true_track')->nullable();
            $table->double('vertical_rate')->nullable();
            $table->double('sensors')->nullable(); // usually null
            $table->double('geo_altitude')->nullable();
            $table->string('squawk', 10)->nullable();
            $table->boolean('spi')->default(false);
            $table->integer('position_source')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
