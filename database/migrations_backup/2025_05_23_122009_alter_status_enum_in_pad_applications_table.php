<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create new enum type with 'kicked' status
        DB::statement("CREATE TYPE application_status_with_kicked AS ENUM ('pending', 'approved', 'rejected', 'cancelled', 'kicked')");
        
        // Drop the default constraint first
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status DROP DEFAULT");
        
        // Alter the column to use the new enum type
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status TYPE application_status_with_kicked USING status::text::application_status_with_kicked");
        
        // Set the new default value
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status SET DEFAULT 'pending'::application_status_with_kicked");
        
        // Drop old enum and rename new one
        DB::statement("DROP TYPE IF EXISTS application_status");
        DB::statement("ALTER TYPE application_status_with_kicked RENAME TO application_status");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create enum type without 'kicked' status
        DB::statement("CREATE TYPE application_status_old AS ENUM ('pending', 'approved', 'rejected', 'cancelled')");
        
        // Drop the default constraint first
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status DROP DEFAULT");
        
        // Alter the column to use the old enum type
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status TYPE application_status_old USING status::text::application_status_old");
        
        // Set the default value
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status SET DEFAULT 'pending'::application_status_old");
        
        // Drop current enum and rename old one
        DB::statement("DROP TYPE IF EXISTS application_status");
        DB::statement("ALTER TYPE application_status_old RENAME TO application_status");
    }
};
