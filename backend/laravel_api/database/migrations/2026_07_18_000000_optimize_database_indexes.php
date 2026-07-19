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
     * Item #1 Database Index Optimization (Bulan 2 — Medium Priority).
     *
     * Menambahkan index untuk optimalisasi query yang sering dijalankan:
     *
     * ┌─────────────────────┬──────────────────────────────────────────────────────────────┐
     * │ Query Pattern       │ Index Added                                                 │
     * ├─────────────────────┼──────────────────────────────────────────────────────────────┤
     * │ Analysis history    │ analyses(user_id, created_at) — composite DESC               │
     * │ User images         │ images(user_id, created_at) — composite                     │
     * │ Activity logs       │ activity_logs(user_id, created_at) — composite              │
     * │ Style score sorting │ analyses(style_score) — untuk filter/sort score             │
     * │ Latest analyses     │ analyses(user_id, deleted_at) — untuk soft delete query     │
     * │ Consent audit trail │ user_consents(consent_version) — untuk lookup versi         │
     * └─────────────────────┴──────────────────────────────────────────────────────────────┘
     */
    public function up(): void
    {
        // ── analyses ──────────────────────────────────────────────
        // Query: SELECT ... FROM analyses WHERE user_id = ? ORDER BY created_at DESC
        // Composite index (user_id, created_at) mengeliminasi full table scan
        // untuk history pagination dengan sorting by date.
        if (!Schema::hasIndex('analyses', 'analyses_user_id_created_at_index')) {
            Schema::table('analyses', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'analyses_user_id_created_at_index');
            });
        }

        // Index untuk soft delete queries: WHERE user_id = ? AND deleted_at IS NULL
        if (!Schema::hasIndex('analyses', 'analyses_user_id_deleted_at_index')) {
            Schema::table('analyses', function (Blueprint $table) {
                $table->index(['user_id', 'deleted_at'], 'analyses_user_id_deleted_at_index');
            });
        }

        // Index untuk sorting/filter by style_score (Dashboard: top scores)
        if (!Schema::hasIndex('analyses', 'analyses_style_score_index')) {
            Schema::table('analyses', function (Blueprint $table) {
                $table->index('style_score', 'analyses_style_score_index');
            });
        }

        // ── images ────────────────────────────────────────────────
        // Composite index untuk query gambar per user dengan sorting date
        if (!Schema::hasIndex('images', 'images_user_id_created_at_index')) {
            Schema::table('images', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'images_user_id_created_at_index');
            });
        }

        // ── activity_logs ─────────────────────────────────────────
        // Jika tabel activity_logs sudah ada, tambahkan composite index
        if (Schema::hasTable('activity_logs')) {
            if (!Schema::hasIndex('activity_logs', 'activity_logs_user_id_created_at_index')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'activity_logs_user_id_created_at_index');
                });
            }
        }

        // ── user_consents ─────────────────────────────────────────
        // Index untuk lookup consent version
        if (Schema::hasTable('user_consents')) {
            if (!Schema::hasIndex('user_consents', 'user_consents_consent_version_index')) {
                Schema::table('user_consents', function (Blueprint $table) {
                    $table->index('consent_version', 'user_consents_consent_version_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropIndex('analyses_user_id_created_at_index');
            $table->dropIndex('analyses_user_id_deleted_at_index');
            $table->dropIndex('analyses_style_score_index');
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex('images_user_id_created_at_index');
        });

        if (Schema::hasTable('activity_logs')) {
            if (Schema::hasIndex('activity_logs', 'activity_logs_user_id_created_at_index')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropIndex('activity_logs_user_id_created_at_index');
                });
            }
        }

        if (Schema::hasTable('user_consents')) {
            if (Schema::hasIndex('user_consents', 'user_consents_consent_version_index')) {
                Schema::table('user_consents', function (Blueprint $table) {
                    $table->dropIndex('user_consents_consent_version_index');
                });
            }
        }
    }
};
