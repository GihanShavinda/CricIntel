<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('player_positions', function(Blueprint $table){
   $table->id(); $table->foreignId('player_id')->constrained()->cascadeOnDelete();
   $table->string('position',80); $table->unsignedTinyInteger('priority')->default(1); $table->timestamps();
   $table->unique(['player_id','position']);
  });
 }
 public function down(): void { Schema::dropIfExists('player_positions'); }
};
