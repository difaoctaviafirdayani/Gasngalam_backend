<?php

use Illuminate\Database\Migrations\Migration;

// Migration ini dikosongkan karena kolom koordinat sudah ditambahkan
// di migration 2026_05_06_000001_add_photo_and_geo_to_destinations.php
// sebagai kolom lat dan lng. Tidak perlu duplikat.

return new class extends Migration
{
    public function up(): void
    {
        // Sudah ditangani oleh migration sebelumnya (lat, lng)
    }

    public function down(): void
    {
        // Tidak ada yang perlu di-rollback
    }
};