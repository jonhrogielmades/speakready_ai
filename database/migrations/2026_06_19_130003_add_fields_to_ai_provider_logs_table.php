<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_provider_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_provider_logs', 'provider_id')) {
                $table->unsignedBigInteger('provider_id')->nullable()->after('id');
                $table->string('module')->nullable()->after('provider_id');
                $table->string('endpoint')->nullable()->after('module');
                $table->integer('response_time_ms')->nullable()->after('endpoint');
                $table->integer('tokens_used')->nullable()->after('response_time_ms');
                $table->decimal('cost', 10, 4)->nullable()->after('tokens_used');
                $table->string('status')->nullable()->after('cost'); // success, failed, timeout
                $table->text('error_message')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_provider_logs', function (Blueprint $table) {
            $table->dropColumn([
                'provider_id',
                'module',
                'endpoint',
                'response_time_ms',
                'tokens_used',
                'cost',
                'status',
                'error_message'
            ]);
        });
    }
};
