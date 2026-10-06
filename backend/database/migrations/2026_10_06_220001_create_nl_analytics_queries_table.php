<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nl_analytics_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->text('natural_language_query');
            $table->string('intent', 100)->nullable()->index();
            $table->jsonb('controlled_query')->nullable();
            $table->jsonb('resolved_filters')->nullable();

            $table->jsonb('structured_result')->nullable();
            $table->text('explanation')->nullable();
            $table->jsonb('visualization')->nullable();

            $table->string('status', 30)->default('pending')->index();
            $table->jsonb('errors')->nullable();

            $table->timestampTz('executed_at')->nullable();
            $table->timestamps();

            $table->index([
                'organization_id',
                'user_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nl_analytics_queries');
    }
};
