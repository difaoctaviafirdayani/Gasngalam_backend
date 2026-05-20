<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom lat/lng sudah dibuat di migration 2026_05_06_000001
        // Migration ini sengaja dikosongkan untuk menghindari duplikat kolom
    }

    public function down(): void
    {
        // Tidak ada yang perlu di-rollback
    }
};