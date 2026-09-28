<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('player_team', function(Blueprint $table){
   $table->id();
   $table->foreignId('player_id')->constrained()->cascadeOnDelete();
   $table->foreignId('team_id')->constrained()->cascadeOnDelete();
   $table->unsignedSmallInteger('jersey_number')->nullable();
   $table->date('joined_at')->nullable(); $table->date('left_at')->nullable();
   $table->boolean('is_current')->default(true)->index(); $table->timestamps();
   $table->index(['player_id','team_id','is_current']);
  });
 }
 public function down(): void { Schema::dropIfExists('player_team'); }
};
