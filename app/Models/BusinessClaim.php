<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessClaim extends Model
{
    protected $fillable = [
        'user_id', 'destination_id', 'full_name', 'email',
        'phone', 'description', 'document_path', 'status', 'admin_notes',
    ];

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
