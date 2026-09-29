<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scouting_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scouting_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('media_type', 30);
            $table->string('video_url', 2048)->nullable();
            $table->string('disk', 40)->nullable();
            $table->string('path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('caption', 255)->nullable();
            $table->timestamps();

            $table->index(['scouting_report_id', 'media_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scouting_media');
    }
};
