<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('finish_state')->default('drawing')->after('status');
            $table->string('finished_by_ip')->nullable()->after('finish_state');
            $table->timestamp('finish_deadline_at')->nullable()->after('finished_by_ip');
            $table->timestamp('finished_at')->nullable()->after('finish_deadline_at');
            $table->text('ai_image_url')->nullable()->after('finished_at');
            $table->text('ai_prompt')->nullable()->after('ai_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['finish_state', 'finished_by_ip', 'finish_deadline_at', 'finished_at', 'ai_image_url', 'ai_prompt']);
        });
    }
};
