<?php

use App\Support\VoiceSessionSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        VoiceSessionSchema::ensure(force: true, createIfMissing: true);
    }

    public function down(): void
    {
        //
    }
};
