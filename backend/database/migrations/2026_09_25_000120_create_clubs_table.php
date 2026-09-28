<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->string('logo')->nullable();
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['organization_id','code']);
            $table->index(['organization_id','name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
