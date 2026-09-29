<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fitness_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('test_type', 80);
            $table->date('tested_at');
            $table->decimal('value', 12, 3)->nullable();
            $table->string('unit', 40)->nullable();
            $table->json('measurements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'player_id', 'tested_at']);
            $table->index(['player_id', 'test_type', 'tested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_tests');
    }
};
