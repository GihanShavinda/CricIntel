<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('deliveries', function(Blueprint $table){
   $table->id();
   $table->foreignId('innings_id')->constrained()->cascadeOnDelete();
   $table->foreignId('over_id')->constrained()->cascadeOnDelete();
   $table->unsignedInteger('sequence_number');
   $table->unsignedTinyInteger('ball_number');
   $table->foreignId('bowler_id')->constrained('players')->cascadeOnDelete();
   $table->foreignId('batter_id')->constrained('players')->cascadeOnDelete();
   $table->foreignId('non_striker_id')->constrained('players')->cascadeOnDelete();
   $table->unsignedTinyInteger('runs_off_bat')->default(0);
   $table->unsignedTinyInteger('extra_runs')->default(0);
   $table->unsignedTinyInteger('total_runs')->default(0);
   $table->string('extra_type',20)->default('none')->index();
   $table->boolean('is_legal')->default(true);
   $table->boolean('is_free_hit')->default(false);
   $table->boolean('wicket')->default(false);
   $table->string('wicket_type',40)->nullable();
   $table->foreignId('dismissed_player_id')->nullable()->constrained('players')->nullOnDelete();
   $table->foreignId('fielder_id')->nullable()->constrained('players')->nullOnDelete();
   $table->string('shot_type',60)->nullable();
   $table->string('delivery_type',60)->nullable();
   $table->string('pitch_zone',60)->nullable();
   $table->decimal('ball_speed',6,2)->nullable();
   $table->timestampTz('delivery_timestamp')->nullable();
   $table->timestamps();
   $table->unique(['innings_id','sequence_number']);
  });
 }
 public function down(): void { Schema::dropIfExists('deliveries'); }
};
