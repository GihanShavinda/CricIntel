<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scouting_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scouting_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scout_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('competition', 160)->nullable();
            $table->date('report_date');
            $table->string('observed_role', 80)->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->unsignedTinyInteger('potential')->nullable();
            $table->string('overall_recommendation', 40)->default('Monitor');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['scouting_profile_id', 'report_date']);
            $table->index(['competition', 'report_date']);
            $table->index('overall_recommendation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scouting_reports');
    }
};
