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
        Schema::create('property_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('property_id');
            $table->foreign('property_id')->references('propertyID')->on('properties')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'kicked'])->default('pending');
            $table->timestamp('application_date')->useCurrent();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_applications');
    }
};
