<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'feedback_interview_session_id_unique';

    public function up(): void
    {
        if (! Schema::hasTable('feedback') || ! Schema::hasColumn('feedback', 'interview_session_id') || $this->hasIndex()) {
            return;
        }

        $duplicates = DB::table('feedback')
            ->select('interview_session_id')
            ->whereNotNull('interview_session_id')
            ->groupBy('interview_session_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicates) {
            throw new RuntimeException('Duplicate feedback rows exist for an interview session. Resolve them before adding the feedback uniqueness constraint.');
        }

        Schema::table('feedback', fn (Blueprint $table) => $table->unique('interview_session_id', self::INDEX));
    }

    public function down(): void
    {
        if (Schema::hasTable('feedback') && $this->hasIndex()) {
            Schema::table('feedback', fn (Blueprint $table) => $table->dropUnique(self::INDEX));
        }
    }

    private function hasIndex(): bool
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
            return false;
        }

        foreach (Schema::getIndexes('feedback') as $index) {
            if (($index['name'] ?? null) === self::INDEX) {
                return true;
            }
        }

        return false;
    }
};
