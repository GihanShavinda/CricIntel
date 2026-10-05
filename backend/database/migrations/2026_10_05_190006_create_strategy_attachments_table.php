<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 30)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('attachment_type', 40);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('label', 220)->nullable();
            $table->string('disk', 40)->nullable();
            $table->string('path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->timestamps();

            $table->index(['strategy_plan_id', 'attachment_type']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
