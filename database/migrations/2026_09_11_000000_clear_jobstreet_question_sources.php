<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('questions')
            || ! Schema::hasColumn('questions', 'source_name')
            || ! Schema::hasColumn('questions', 'source_url')
        ) {
            return;
        }

        DB::table('questions')
            ->where(function ($query) {
                $query
                    ->where('source_name', 'like', '%JobStreet%')
                    ->orWhere('source_url', 'like', '%jobstreet%');
            })
            ->update([
                'source_name' => null,
                'source_url' => null,
            ]);
    }

    public function down(): void
    {
        // Source labels are intentionally not restored.
    }
};
