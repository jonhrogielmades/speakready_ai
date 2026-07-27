<?php

use App\Support\QuestionSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        QuestionSchema::ensure(force: true, createIfMissing: true);
    }

    public function down(): void
    {
        //
    }
};
