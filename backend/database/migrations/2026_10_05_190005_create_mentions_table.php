<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id');
            $table->string('token', 190)->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestamps();

            $table->index(
                ['strategy_plan_id', 'source_type', 'source_id'],
                'mentions_source_index'
            );
            $table->index(['mentioned_user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};
