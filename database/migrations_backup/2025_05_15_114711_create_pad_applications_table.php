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
        Schema::create('pad_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pad_id');
            $table->foreign('pad_id')->references('padID')->on('pads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Tenant's ID
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('application_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pad_applications');
    }
};
