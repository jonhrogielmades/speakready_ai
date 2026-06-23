<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'message',
        'type',
        'target',
        'user_id',
        'sent_by'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sender() {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
