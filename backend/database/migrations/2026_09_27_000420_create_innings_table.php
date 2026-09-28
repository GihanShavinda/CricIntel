<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('innings', function(Blueprint $table){
   $table->id();
   $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
   $table->foreignId('batting_team_id')->constrained('teams')->cascadeOnDelete();
   $table->foreignId('bowling_team_id')->constrained('teams')->cascadeOnDelete();
   $table->unsignedTinyInteger('innings_number');
   $table->unsignedInteger('runs')->default(0);
   $table->unsignedTinyInteger('wickets')->default(0);
   $table->unsignedInteger('legal_balls')->default(0);
   $table->decimal('overs_completed',5,1)->default(0);
   $table->string('status',30)->default('In Progress')->index();
   $table->foreignId('striker_id')->nullable()->constrained('players')->nullOnDelete();
   $table->foreignId('non_striker_id')->nullable()->constrained('players')->nullOnDelete();
   $table->foreignId('current_bowler_id')->nullable()->constrained('players')->nullOnDelete();
   $table->boolean('free_hit_next')->default(false);
   $table->unsignedInteger('target')->nullable();
   $table->timestamps();
   $table->unique(['match_id','innings_number']);
  });
 }
 public function down(): void { Schema::dropIfExists('innings'); }
};
