<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->unique(['organization_id','name']);
        });

        Schema::create('season_team', function (Blueprint $table) {
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['season_id','team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_team');
        Schema::dropIfExists('seasons');
    }
};
