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
        // Create the enum type first
        DB::statement("CREATE TYPE application_status AS ENUM ('pending', 'approved', 'rejected', 'cancelled')");
        
        // Drop the default constraint first
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status DROP DEFAULT");
        
        // Alter the column to use the new enum type
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status TYPE application_status USING status::application_status");
        
        // Set the new default value
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status SET DEFAULT 'pending'::application_status");
        
        // Set NOT NULL constraint
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status SET NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the default constraint first
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status DROP DEFAULT");
        
        // Change back to original enum type
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status TYPE varchar(255) USING status::text");
        
        // Set back the original default
        DB::statement("ALTER TABLE pad_applications ALTER COLUMN status SET DEFAULT 'pending'");
        
        // Drop the enum type
        DB::statement("DROP TYPE IF EXISTS application_status");
    }
};
