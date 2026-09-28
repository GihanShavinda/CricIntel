<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('player_documents', function(Blueprint $table){
   $table->id(); $table->foreignId('player_id')->constrained()->cascadeOnDelete();
   $table->string('type',50); $table->string('name'); $table->string('path');
   $table->date('expires_at')->nullable(); $table->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('player_documents'); }
};
