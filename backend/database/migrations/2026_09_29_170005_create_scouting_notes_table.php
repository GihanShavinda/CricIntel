<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scouting_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scouting_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scouting_report_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->boolean('is_private')->default(false);
            $table->timestamps();

            $table->index(['scouting_profile_id', 'created_at']);
            $table->index(['scouting_report_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scouting_notes');
    }
};
