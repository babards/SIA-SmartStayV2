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
        Schema::create('pads', function (Blueprint $table) {
            $table->id('padID');
            $table->unsignedBigInteger('userID');
            $table->foreign('userID')->references('id')->on('users')->onDelete('cascade');
            $table->string('padName');
            $table->text('padDescription');
            $table->string('padLocation');
            $table->decimal('padRent', 10, 2);
            $table->string('padImage')->nullable();
            $table->enum('padStatus', ['Available', 'Fullyoccupied', 'Maintenance'])->default('Available');
            $table->timestamp('padCreatedAt')->useCurrent();
            $table->timestamp('padUpdatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pads');
    }
};
