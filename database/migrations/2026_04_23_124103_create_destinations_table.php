<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('location')->nullable();
            $table->string('distance')->nullable();
            $table->decimal('rating', 3, 1)->default(0);
            $table->integer('review_count')->default(0);
            $table->string('ticket_price')->nullable();
            $table->string('open_hours')->nullable();
            $table->string('contact')->nullable();
            $table->string('social_media')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('emoji')->nullable();
            $table->string('color')->nullable();
            $table->string('gradient')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
