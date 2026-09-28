<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('players', function(Blueprint $table){
   $table->id();
   $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
   $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
   $table->string('first_name'); $table->string('last_name'); $table->string('display_name');
   $table->date('date_of_birth')->nullable(); $table->string('nationality',100)->nullable();
   $table->string('photo')->nullable(); $table->string('primary_role',50)->index();
   $table->string('batting_style',50)->nullable()->index(); $table->string('bowling_style',80)->nullable()->index();
   $table->string('fitness_status',50)->default('Fit')->index(); $table->string('status',30)->default('Active')->index();
   $table->text('notes')->nullable(); $table->timestamps();
   $table->index(['organization_id','display_name']);
  });
 }
 public function down(): void { Schema::dropIfExists('players'); }
};
