<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pad_boarders', function (Blueprint $table) {
            $table->id();

            // Use unsignedInteger to match pads.padID type (int unsigned)
            $table->unsignedInteger('pad_id');

            // Assuming users.id is unsignedBigInteger, keep as is
            $table->unsignedBigInteger('user_id');

            $table->enum('status', ['active', 'left', 'kicked'])->default('active');
            $table->timestamps();

            $table->foreign('pad_id')->references('padID')->on('pads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pad_boarders');
    }
};
