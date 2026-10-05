<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('strategy_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('section_key', 80);
            $table->string('title', 160);
            $table->longText('content')->nullable();
            $table->jsonb('structured_data')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['strategy_plan_id', 'section_key'],
                'strategy_sections_plan_key_unique'
            );
            $table->index(['strategy_plan_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_sections');
    }
};
