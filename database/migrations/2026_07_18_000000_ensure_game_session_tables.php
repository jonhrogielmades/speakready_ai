<?php

use App\Support\GameSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        GameSchema::ensure(true);
    }

    public function down(): void
    {
        //
    }
};
