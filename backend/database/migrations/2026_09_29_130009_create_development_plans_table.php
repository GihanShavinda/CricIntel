<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('development_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('weakness')->nullable();
            $table->json('objectives')->nullable();
            $table->json('objective_ids')->nullable();
            $table->json('drill_ids')->nullable();
            $table->date('start_date');
            $table->date('target_date')->nullable();
            $table->string('status', 30)->default('Active');
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'player_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_plans');
    }
};
