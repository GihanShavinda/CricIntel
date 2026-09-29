<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('squads', function(Blueprint $t){$t->id();$t->foreignId('organization_id')->constrained()->cascadeOnDelete();$t->foreignId('tournament_id')->constrained()->cascadeOnDelete();$t->foreignId('team_id')->constrained()->cascadeOnDelete();$t->string('name');$t->unsignedTinyInteger('min_players')->default(11);$t->unsignedTinyInteger('max_players')->default(18);$t->string('status')->default('Draft');$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->unique(['tournament_id','team_id']);}); }
 public function down(): void { Schema::dropIfExists('squads'); }
};