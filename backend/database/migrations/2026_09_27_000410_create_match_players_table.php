<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('match_players', function(Blueprint $table){
   $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
   $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
   $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
   $table->string('role',60)->nullable();
   $table->boolean('playing_xi')->default(true);
   $table->timestamps();
   $table->primary(['match_id','player_id']);
  });
 }
 public function down(): void { Schema::dropIfExists('match_players'); }
};
