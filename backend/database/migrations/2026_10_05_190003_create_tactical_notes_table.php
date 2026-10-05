<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tactical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategy_section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180)->nullable();
            $table->longText('body');
            $table->string('status', 20)->default('Open')->index();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['strategy_plan_id', 'status']);
            $table->index(['strategy_section_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tactical_notes');
    }
};
