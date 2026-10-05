<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('strategy_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategy_section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('Todo')->index();
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->index(['strategy_plan_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_assignments');
    }
};
