<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('strategy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('event_type', 60);
            $table->string('entity_type', 60);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('change_summary', 255);
            $table->jsonb('snapshot')->nullable();
            $table->jsonb('changes')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(
                ['strategy_plan_id', 'version_number'],
                'strategy_versions_plan_version_unique'
            );
            $table->index(['strategy_plan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_versions');
    }
};
