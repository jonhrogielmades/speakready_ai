<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->text('message')->after('title');
            $table->string('type')->default('info')->after('message');
            $table->string('target')->default('all')->after('type');
            $table->unsignedBigInteger('user_id')->nullable()->after('target');
            $table->unsignedBigInteger('sent_by')->nullable()->after('user_id');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sent_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['sent_by']);
            $table->dropColumn(['title', 'message', 'type', 'target', 'user_id', 'sent_by']);
        });
    }
};
