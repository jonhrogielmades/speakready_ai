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
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE users ALTER COLUMN profile_photo_path TYPE TEXT');
        } else {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE users MODIFY profile_photo_path LONGTEXT');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE users ALTER COLUMN profile_photo_path TYPE VARCHAR(2048)');
        } else {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE users MODIFY profile_photo_path VARCHAR(2048)');
        }
    }
};
