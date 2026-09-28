<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('tournament_team', function(Blueprint $table){
  $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
  $table->foreignId('team_id')->constrained()->cascadeOnDelete();
  $table->unsignedSmallInteger('seed')->nullable(); $table->string('status',30)->default('registered'); $table->timestamps();
  $table->primary(['tournament_id','team_id']);
 });}
 public function down(): void { Schema::dropIfExists('tournament_team');}
};
