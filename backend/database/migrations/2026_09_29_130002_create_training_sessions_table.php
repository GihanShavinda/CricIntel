<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('session_date');
            $table->time('start_time')->nullable();
            $table->string('location', 180)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(90);
            $table->string('session_type', 80);
            $table->string('status', 30)->default('Scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'session_date']);
            $table->index(['team_id', 'session_date']);
            $table->index(['coach_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
