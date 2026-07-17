<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analysis_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('face_shape_summary')->nullable();
            $table->text('hairstyle_summary')->nullable();
            $table->text('color_summary')->nullable();
            $table->text('outfit_summary')->nullable();
            $table->text('improvement_tips')->nullable();
            $table->boolean('is_saved')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_reports');
    }
};
