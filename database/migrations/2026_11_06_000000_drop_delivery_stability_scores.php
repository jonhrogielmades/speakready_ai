<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['interview_answers', 'scores'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'delivery_stability_score')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('delivery_stability_score');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interview_answers') && ! Schema::hasColumn('interview_answers', 'delivery_stability_score')) {
            Schema::table('interview_answers', function (Blueprint $table) {
                $table->unsignedTinyInteger('delivery_stability_score')->nullable();
            });
        }

        if (Schema::hasTable('scores') && ! Schema::hasColumn('scores', 'delivery_stability_score')) {
            Schema::table('scores', function (Blueprint $table) {
                $table->unsignedTinyInteger('delivery_stability_score')->default(0);
            });
        }
    }
};
