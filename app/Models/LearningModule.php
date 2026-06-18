<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningModule extends Model
{
    protected $fillable = ['title', 'description', 'type', 'url'];
}
