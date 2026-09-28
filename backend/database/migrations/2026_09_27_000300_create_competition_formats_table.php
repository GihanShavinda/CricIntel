<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('competition_formats', function(Blueprint $table){
  $table->id(); $table->string('name',100); $table->string('code',30)->unique();
  $table->text('description')->nullable(); $table->unsignedSmallInteger('default_overs')->nullable();
  $table->boolean('is_custom')->default(false); $table->string('status',20)->default('active')->index(); $table->timestamps();
 });}
 public function down(): void { Schema::dropIfExists('competition_formats');}
};
