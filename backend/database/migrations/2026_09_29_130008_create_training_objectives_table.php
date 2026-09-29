<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('weakness')->nullable();
            $table->string('statistic_scope', 40)->nullable();
            $table->string('metric_key', 100)->nullable();
            $table->decimal('observed_value', 14, 4)->nullable();
            $table->decimal('target_value', 14, 4)->nullable();
            $table->json('source_context')->nullable();
            $table->string('status', 30)->default('Active');
            $table->date('target_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'player_id', 'status']);
            $table->index(['player_id', 'metric_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_objectives');
    }
};
