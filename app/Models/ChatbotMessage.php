<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotMessage extends Model
{
    use HasFactory;

    protected $fillable = ['chatbot_conversation_id', 'role', 'content', 'quality_metadata'];

    protected function casts(): array
    {
        return [
            'quality_metadata' => 'array',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(ChatbotConversation::class, 'chatbot_conversation_id');
    }
}
