<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Memperbaiki beberapa isu database dari audit AUREX:
     * F-007: Tambah analysis_id FK ke images table
     * F-028: Ubah style_score integer → decimal(5,2) untuk presisi float
     * F-029: Tambah soft deletes (deleted_at) ke analyses, images, recommendations
     */
    public function up(): void
    {
        // ─── F-007: Tambah analysis_id ke images ──────────────────
        if (!Schema::hasColumn('images', 'analysis_id')) {
            Schema::table('images', function (Blueprint $table) {
                $table->foreignId('analysis_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete()
                    ->after(DB::connection()->getDriverName() === 'sqlite' ? 'id' : 'image_path');
            });
        }

        // ─── F-028: Ubah style_score integer → decimal(5,2) ──────
        // decimal(5,2) = range 0.00 - 999.99, cukup untuk style score 0-100
        // SQLite tidak mendukung ALTER COLUMN, jadi kita pakai pragma
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite workaround: buat table baru, copy data, hapus table lama
            DB::statement('PRAGMA foreign_keys = OFF');

            Schema::table('analyses', function (Blueprint $table) {
                $table->decimal('style_score', 5, 2)->change();
            });

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('analyses', function (Blueprint $table) {
                $table->decimal('style_score', 5, 2)->change();
            });
        }

        // ─── F-029: Tambah soft deletes ───────────────────────────
        if (!Schema::hasColumn('analyses', 'deleted_at')) {
            Schema::table('analyses', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('images', 'deleted_at')) {
            Schema::table('images', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('recommendations', 'deleted_at')) {
            Schema::table('recommendations', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // F-029: Hapus soft deletes
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('images', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // F-028: Kembalikan style_score ke integer
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            Schema::table('analyses', function (Blueprint $table) {
                $table->integer('style_score')->change();
            });
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('analyses', function (Blueprint $table) {
                $table->integer('style_score')->change();
            });
        }

        // F-007: Hapus analysis_id dari images
        Schema::table('images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('analysis_id');
        });
    }
};
