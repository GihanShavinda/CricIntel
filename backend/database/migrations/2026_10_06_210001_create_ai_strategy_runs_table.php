<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_strategy_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->text('question');
            $table->char('context_hash', 64);
            $table->jsonb('context_snapshot');
            $table->jsonb('deterministic_recommendations')->nullable();

            $table->string('provider', 80)->nullable();
            $table->string('model', 160)->nullable();

            $table->jsonb('raw_llm_response')->nullable();
            $table->jsonb('validated_response')->nullable();

            $table->string('validation_status', 30)->default('pending')->index();
            $table->jsonb('validation_errors')->nullable();

            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();

            $table->timestampTz('generated_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'match_id', 'created_at']);
            $table->index(['organization_id', 'user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_strategy_runs');
    }
};
