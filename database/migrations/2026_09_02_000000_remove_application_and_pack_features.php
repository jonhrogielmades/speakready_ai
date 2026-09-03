<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            $this->dropForeignIdColumn('interview_sessions', 'job_application_id');
            $this->dropForeignIdColumn('interview_sessions', 'interview_pack_id');

            Schema::dropIfExists('practice_plan_items');
            Schema::dropIfExists('job_applications');
            Schema::dropIfExists('interview_packs');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        //
    }

    private function dropForeignIdColumn(string $tableName, string $column): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $column)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropConstrainedForeignId($column);
            });
        } catch (\Throwable) {
            if (Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
