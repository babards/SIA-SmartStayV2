<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Step 1: Create new enum type with 'fullyoccupied'
        DB::statement("CREATE TYPE pad_status_new AS ENUM ('available', 'occupied', 'fullyoccupied', 'maintenance')");
        
        // Step 2: Update 'occupied' values to 'fullyoccupied'
        DB::table('pads')->where('padStatus', 'occupied')->update(['padStatus' => 'fullyoccupied']);
        
        // Step 3: Alter column to use new enum type
        DB::statement("ALTER TABLE pads ALTER COLUMN \"padStatus\" TYPE pad_status_new USING \"padStatus\"::text::pad_status_new");
        
        // Step 4: Drop old enum type and rename new one
        DB::statement("DROP TYPE IF EXISTS pad_status");
        DB::statement("ALTER TYPE pad_status_new RENAME TO pad_status");
    }

    public function down(): void
    {
        // Step 1: Create enum type with 'occupied' back
        DB::statement("CREATE TYPE pad_status_old AS ENUM ('available', 'occupied', 'fullyoccupied', 'maintenance')");
        
        // Step 2: Change back to 'occupied'
        DB::table('pads')->where('padStatus', 'fullyoccupied')->update(['padStatus' => 'occupied']);
        
        // Step 3: Alter column to use old enum type
        DB::statement("ALTER TABLE pads ALTER COLUMN \"padStatus\" TYPE pad_status_old USING \"padStatus\"::text::pad_status_old");
        
        // Step 4: Drop current enum and rename old one
        DB::statement("DROP TYPE IF EXISTS pad_status");
        DB::statement("ALTER TYPE pad_status_old RENAME TO pad_status");
    }
};