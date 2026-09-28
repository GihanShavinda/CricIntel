<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('wickets', function(Blueprint $table){
   $table->id();
   $table->foreignId('delivery_id')->unique()->constrained()->cascadeOnDelete();
   $table->foreignId('innings_id')->constrained()->cascadeOnDelete();
   $table->foreignId('dismissed_player_id')->constrained('players')->cascadeOnDelete();
   $table->string('wicket_type',40);
   $table->foreignId('bowler_id')->nullable()->constrained('players')->nullOnDelete();
   $table->foreignId('fielder_id')->nullable()->constrained('players')->nullOnDelete();
   $table->unsignedInteger('runs_at_wicket');
   $table->unsignedTinyInteger('wicket_number');
   $table->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('wickets'); }
};
