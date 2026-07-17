<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_image_id')->constrained('uploaded_images')->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('style_score')->nullable();
            $table->string('face_shape')->nullable();
            $table->string('skin_undertone')->nullable();
            $table->json('hairstyles')->nullable();
            $table->json('colors')->nullable();
            $table->json('outfits')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
