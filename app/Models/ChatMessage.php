<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'message_id',
        'chat_session_id',
        'role',
        'content',
        'category',
        'authority',
        'metadata',
        'is_typing',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_typing' => 'boolean',
    ];

    /**
     * Get the chat session that owns this message
     */
    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    /**
     * Scope for filtering by role (user/assistant)
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for filtering by authority
     */
    public function scopeByAuthority($query, $authority)
    {
        return $query->where('authority', $authority);
    }

    /**
     * Scope for filtering by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if this is a user message
     */
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if this is an assistant message
     */
    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }
}
