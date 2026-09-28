<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('backgrounds', function(Blueprint $t){$t->id();$t->string('filename');$t->string('path');$t->timestamp('created_at')->useCurrent();}); } public function down(): void {Schema::dropIfExists('backgrounds');}};
