<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // Step 1: Update values first — BEFORE changing the ENUM
        DB::table('pads')->where('padStatus', 'available')->update(['padStatus' => 'Available']);
        DB::table('pads')->where('padStatus', 'fullyoccupied')->update(['padStatus' => 'Fullyoccupied']);
        DB::table('pads')->where('padStatus', 'maintenance')->update(['padStatus' => 'Maintenance']);

        // Step 2: Create new enum type with capitalized values
        DB::statement("CREATE TYPE pad_status_caps AS ENUM ('Available', 'Fullyoccupied', 'Maintenance')");
        
        // Step 3: Alter column to use new enum type
        DB::statement("ALTER TABLE pads ALTER COLUMN \"padStatus\" TYPE pad_status_caps USING \"padStatus\"::text::pad_status_caps");
        
        // Step 4: Drop old enum and rename new one
        DB::statement("DROP TYPE IF EXISTS pad_status");
        DB::statement("ALTER TYPE pad_status_caps RENAME TO pad_status");
    }

    public function down(): void
    {
        // Step 1: Revert capitalized values back to lowercase
        DB::table('pads')->where('padStatus', 'Available')->update(['padStatus' => 'available']);
        DB::table('pads')->where('padStatus', 'Fullyoccupied')->update(['padStatus' => 'fullyoccupied']);
        DB::table('pads')->where('padStatus', 'Maintenance')->update(['padStatus' => 'maintenance']);

        // Step 2: Create enum type with lowercase values
        DB::statement("CREATE TYPE pad_status_lower AS ENUM ('available', 'fullyoccupied', 'maintenance')");
        
        // Step 3: Alter column to use lowercase enum type
        DB::statement("ALTER TABLE pads ALTER COLUMN \"padStatus\" TYPE pad_status_lower USING \"padStatus\"::text::pad_status_lower");
        
        // Step 4: Drop current enum and rename new one
        DB::statement("DROP TYPE IF EXISTS pad_status");
        DB::statement("ALTER TYPE pad_status_lower RENAME TO pad_status");
    }

};