<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('player_availability', function(Blueprint $table){
   $table->id(); $table->foreignId('player_id')->constrained()->cascadeOnDelete();
   $table->date('available_from'); $table->date('available_to')->nullable();
   $table->string('status',30)->default('Available')->index(); $table->text('reason')->nullable(); $table->timestamps();
   $table->index(['player_id','available_from','available_to']);
  });
 }
 public function down(): void { Schema::dropIfExists('player_availability'); }
};
