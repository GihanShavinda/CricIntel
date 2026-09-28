<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('overs', function(Blueprint $table){
   $table->id();
   $table->foreignId('innings_id')->constrained()->cascadeOnDelete();
   $table->unsignedSmallInteger('over_number');
   $table->foreignId('bowler_id')->constrained('players')->cascadeOnDelete();
   $table->unsignedTinyInteger('legal_balls')->default(0);
   $table->unsignedSmallInteger('runs')->default(0);
   $table->unsignedTinyInteger('wickets')->default(0);
   $table->string('status',20)->default('In Progress');
   $table->timestamps();
   $table->unique(['innings_id','over_number']);
  });
 }
 public function down(): void { Schema::dropIfExists('overs'); }
};
