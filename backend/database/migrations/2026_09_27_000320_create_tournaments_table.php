<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('tournaments', function(Blueprint $table){
  $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
  $table->foreignId('season_id')->constrained()->cascadeOnDelete();
  $table->foreignId('competition_format_id')->nullable()->constrained()->nullOnDelete();
  $table->string('name'); $table->string('format',30)->index(); $table->date('start_date'); $table->date('end_date');
  $table->string('status',30)->default('Scheduled')->index(); $table->string('organizer')->nullable();
  $table->jsonb('rules_json')->nullable(); $table->timestamps();
  $table->unique(['organization_id','season_id','name']);
 });}
 public function down(): void { Schema::dropIfExists('tournaments');}
};
