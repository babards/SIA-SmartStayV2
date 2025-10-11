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
        Schema::create('properties', function (Blueprint $table) {
            $table->id('propertyID');
            $table->unsignedBigInteger('userID');
            $table->foreign('userID')->references('id')->on('users')->onDelete('cascade');
            $table->string('propertyName');
            $table->text('propertyDescription')->nullable();
            $table->string('propertyLocation');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('propertyRent', 10, 2);
            $table->string('propertyImage')->nullable();
            $table->json('property_images')->nullable();
            $table->enum('propertyStatus', ['Available', 'Fullyoccupied', 'Maintenance'])->default('Available');
            $table->integer('number_of_boarders')->default(0);
            $table->timestamp('propertyCreatedAt')->useCurrent();
            $table->datetime('propertyUpdatedAt')->nullable();
            $table->unsignedInteger('vacancy')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
