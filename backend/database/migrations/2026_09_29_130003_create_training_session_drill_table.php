<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_session_drill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_drill_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->unsignedSmallInteger('planned_duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(
                ['training_session_id', 'training_drill_id'],
                'training_session_drill_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_drill');
    }
};
