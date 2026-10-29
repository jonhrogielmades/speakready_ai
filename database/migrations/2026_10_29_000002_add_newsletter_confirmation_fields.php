<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscriptions', function (Blueprint $table): void {
            $table->char('confirmation_token_hash', 64)
                ->nullable()
                ->unique('newsletter_confirmation_token_unique')
                ->after('source');
            $table->timestamp('confirmation_expires_at')->nullable()->after('confirmation_token_hash');
            $table->timestamp('confirmed_at')->nullable()->after('confirmation_expires_at');
        });

        // Records created before double opt-in were already treated as active.
        // Keep them subscribed and mark their original subscription time as the
        // confirmation time instead of forcing an unexpected reconfirmation.
        DB::table('newsletter_subscriptions')
            ->where('status', 'subscribed')
            ->whereNull('confirmed_at')
            ->update(['confirmed_at' => DB::raw('subscribed_at')]);
    }

    public function down(): void
    {
        Schema::table('newsletter_subscriptions', function (Blueprint $table): void {
            $table->dropUnique('newsletter_confirmation_token_unique');
            $table->dropColumn([
                'confirmation_token_hash',
                'confirmation_expires_at',
                'confirmed_at',
            ]);
        });
    }
};
