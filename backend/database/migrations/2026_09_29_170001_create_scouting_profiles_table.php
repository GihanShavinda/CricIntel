<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scouting_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('existing_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('converted_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 160);
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('role', 80)->nullable();
            $table->string('batting_style', 80)->nullable();
            $table->string('bowling_style', 100)->nullable();
            $table->string('current_team', 160)->nullable();
            $table->string('current_competition', 160)->nullable();
            $table->string('source', 120)->nullable();
            $table->string('status', 40)->default('Watching');
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'role']);
            $table->index(['organization_id', 'display_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scouting_profiles');
    }
};
