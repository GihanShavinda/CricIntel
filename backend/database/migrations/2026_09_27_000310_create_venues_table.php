<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('venues', function(Blueprint $table){
  $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
  $table->string('name'); $table->string('city',100)->nullable(); $table->string('country',100)->nullable();
  $table->unsignedInteger('capacity')->nullable(); $table->string('pitch_type',100)->nullable();
  $table->jsonb('boundary_dimensions')->nullable(); $table->text('notes')->nullable();
  $table->string('status',20)->default('active')->index(); $table->timestamps();
  $table->index(['organization_id','name']);
 });}
 public function down(): void { Schema::dropIfExists('venues');}
};
