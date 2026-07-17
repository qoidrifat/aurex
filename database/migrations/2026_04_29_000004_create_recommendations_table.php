<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['hairstyle', 'color', 'outfit']);
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('hex_color')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['analysis_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
