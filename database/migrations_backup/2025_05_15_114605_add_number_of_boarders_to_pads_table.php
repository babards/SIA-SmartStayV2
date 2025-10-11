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
            $table->integer('number_of_boarders')->default(0)->after('padStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pads', function (Blueprint $table) {
            $table->dropColumn('number_of_boarders');
        });
    }
};
