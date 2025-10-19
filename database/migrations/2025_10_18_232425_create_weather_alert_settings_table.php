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
        Schema::create('weather_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('property_id')->nullable(); // null means all properties
            $table->boolean('enabled')->default(true);
            $table->enum('min_severity', ['minor', 'moderate', 'severe'])->default('moderate');
            $table->json('alert_types')->nullable(); // specific alert types to monitor
            $table->time('quiet_hours_start')->nullable(); // e.g., 22:00
            $table->time('quiet_hours_end')->nullable(); // e.g., 08:00
            $table->integer('max_alerts_per_day')->default(3);
            $table->timestamp('last_alert_sent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_id')->references('propertyID')->on('properties')->onDelete('cascade');
            
            $table->unique(['user_id', 'property_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_alert_settings');
    }
};
