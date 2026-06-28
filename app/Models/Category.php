<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['title', 'type', 'description', 'icon', 'status', 'is_featured', 'sort_order'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

}
