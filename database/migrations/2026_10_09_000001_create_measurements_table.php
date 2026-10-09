<?php

// Creates the benchmark measurement sink used by the opt-in client exporter.
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session_code', 6);
            $table->uuid('client_id');
            $table->string('event_type');
            $table->float('value_ms')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('session_code');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
