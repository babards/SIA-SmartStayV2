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
        Schema::table('pads', function (Blueprint $table) {
            $table->json('pad_images')->nullable()->after('padImage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pads', function (Blueprint $table) {
            $table->dropColumn('pad_images');
        });
    }
};
