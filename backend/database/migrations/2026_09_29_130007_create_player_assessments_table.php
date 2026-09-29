<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessed_at');
            $table->unsignedTinyInteger('technical_rating')->nullable();
            $table->unsignedTinyInteger('tactical_rating')->nullable();
            $table->unsignedTinyInteger('fitness_rating')->nullable();
            $table->unsignedTinyInteger('attitude_rating')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'player_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_assessments');
    }
};
