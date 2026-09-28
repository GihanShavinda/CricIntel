<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 50)->nullable();
            $table->string('gender', 30)->nullable()->index();
            $table->string('category', 50)->nullable()->index();
            $table->string('age_group', 50)->nullable()->index();
            $table->jsonb('format_preferences')->nullable();
            $table->string('home_ground')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->index(['club_id','name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
