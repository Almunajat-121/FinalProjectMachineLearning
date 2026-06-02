<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            // Primary key UUID
            $table->uuid('id')->primary();

            // Konten keluhan
            $table->text('raw_text');
            $table->char('lang_hint', 2)->default('id');

            // Hasil prediksi NLP (nullable, diisi async)
            $table->enum('category', [
                'FASILITAS', 'AKADEMIK', 'JARINGAN_IT',
                'KEUANGAN', 'KEMAHASISWAAN', 'LAINNYA'
            ])->nullable();

            $table->enum('urgency', [
                'RENDAH', 'SEDANG', 'TINGGI', 'KRITIS'
            ])->nullable();

            $table->decimal('category_score', 4, 3)->nullable();
            $table->decimal('urgency_score', 4, 3)->nullable();
            $table->json('keywords')->nullable();

            // Status lifecycle
            $table->enum('status', [
                'PENDING_NLP', 'OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'
            ])->default('PENDING_NLP');

            // Timestamps
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('resolved_at', 3)->nullable();

            // Catatan admin
            $table->text('admin_note')->nullable();

            // Index
            $table->index('created_at');
        });

        // Tambah cursor_id sebagai auto_increment + unique key via raw SQL
        // karena MySQL tidak izinkan dua kolom auto_increment lewat Blueprint
        DB::statement('ALTER TABLE tickets ADD COLUMN cursor_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE');
        DB::statement('ALTER TABLE tickets ADD INDEX idx_status_cursor (status, cursor_id DESC)');
        DB::statement('ALTER TABLE tickets ADD INDEX idx_urgency_cursor (urgency, cursor_id DESC)');
        DB::statement('ALTER TABLE tickets ADD INDEX idx_category_cursor (category, cursor_id DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};