<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            // Foto utama destinasi (path relatif di storage/app/public/destinations/)
            $table->string('photo_url')->nullable()->after('gradient');
            // Koordinat untuk geolocation / hitung jarak real-time
            $table->decimal('lat', 10, 7)->nullable()->after('photo_url');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });

        Schema::table('reviews', function (Blueprint $table) {
            // Foto opsional yang dilampirkan user di review
            $table->string('photo_url')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn(['photo_url', 'lat', 'lng']);
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('photo_url');
        });
    }
};