<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'source_name')) {
                $table->string('source_name')->nullable()->after('mapped_skills');
            }

            if (!Schema::hasColumn('questions', 'source_url')) {
                $table->text('source_url')->nullable()->after('source_name');
            }

            if (!Schema::hasColumn('questions', 'source_type')) {
                $table->string('source_type')->nullable()->after('source_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('questions', 'source_name') ? 'source_name' : null,
                Schema::hasColumn('questions', 'source_url') ? 'source_url' : null,
                Schema::hasColumn('questions', 'source_type') ? 'source_type' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
