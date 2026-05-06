<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    protected $fillable = [
        'name', 'category', 'location', 'distance',
        'rating', 'review_count',
        'ticket_price', 'open_hours',
        'contact', 'social_media', 'address',
        'description',
        'photo_url',
        'lat', 'lng',
        'emoji', 'color', 'gradient',  // tetap ada untuk backward-compat data lama
        'is_active',
    ];

    protected $casts = [
        'rating'       => 'float',
        'review_count' => 'integer',
        'is_active'    => 'boolean',
        'lat'          => 'float',
        'lng'          => 'float',
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function businessClaims()
    {
        return $this->hasMany(BusinessClaim::class);
    }
}