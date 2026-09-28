<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('matches', function(Blueprint $table){
   $table->id();
   $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
   $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
   $table->foreignId('toss_winner_id')->nullable()->constrained('teams')->nullOnDelete();
   $table->string('toss_decision',20)->nullable();
   $table->string('status',30)->default('Scheduled')->index();
   $table->string('result_type',40)->nullable();
   $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
   $table->foreignId('player_of_match_id')->nullable()->constrained('players')->nullOnDelete();
   $table->unsignedInteger('target_runs')->nullable();
   $table->unsignedSmallInteger('max_overs')->nullable();
   $table->timestamps();
   $table->unique('fixture_id');
  });
 }
 public function down(): void { Schema::dropIfExists('matches'); }
};
