<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model {
    protected $table = 'user_notifications';
    protected $fillable = ['user_id', 'type', 'title', 'body', 'data', 'is_read'];
    protected $casts = ['data' => 'array', 'is_read' => 'boolean'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}