<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voice_assistance_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_language', 12)->default('auto');
            $table->string('response_language', 12)->default('en');
            $table->string('audio_disk')->default('private');
            $table->string('audio_path');
            $table->string('audio_mime', 100);
            $table->unsignedBigInteger('audio_size_bytes');
            $table->char('audio_checksum', 64);
            $table->string('status', 24)->default('processing');
            $table->text('transcript')->nullable();
            $table->text('translated_transcript')->nullable();
            $table->text('guidance')->nullable();
            $table->text('safety_note')->nullable();
            $table->string('provider', 40);
            $table->string('provider_reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['farm_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_assistance_requests');
    }
};
