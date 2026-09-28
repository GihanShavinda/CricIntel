<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('fixtures', function(Blueprint $table){
  $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
  $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
  $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
  $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
  $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
  $table->timestampTz('scheduled_at'); $table->unsignedInteger('match_number')->nullable();
  $table->string('round',100)->nullable(); $table->string('status',30)->default('Scheduled')->index();
  $table->text('notes')->nullable(); $table->timestamps();
  $table->index(['organization_id','scheduled_at']); $table->index(['tournament_id','scheduled_at']);
  $table->unique(['tournament_id','home_team_id','away_team_id','scheduled_at'],'fixtures_unique_pair_schedule');
 });}
 public function down(): void { Schema::dropIfExists('fixtures');}
};
