<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scouting_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scouting_report_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('technical_rating');
            $table->unsignedTinyInteger('tactical_rating');
            $table->unsignedTinyInteger('physical_rating');
            $table->unsignedTinyInteger('fielding_rating');
            $table->unsignedTinyInteger('mental_decision_rating');
            $table->decimal('overall_rating', 4, 2);
            $table->timestamps();

            $table->unique('scouting_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scouting_ratings');
    }
};
