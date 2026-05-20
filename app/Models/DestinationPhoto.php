<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DestinationPhoto extends Model {
    protected $fillable = ['destination_id', 'photo_url', 'caption', 'sort_order'];

    public function destination() {
        return $this->belongsTo(Destination::class);
    }
}