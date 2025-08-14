<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = [
        'session_id',
        'title',
        'authority',
        'user_id',
        'message_count',
        'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get all messages for this chat session
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get messages ordered by creation time
     */
    public function orderedMessages(): HasMany
    {
        return $this->messages()->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message for this session
     */
    public function latestMessage()
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Update the last activity timestamp
     */
    public function updateActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Increment message count
     */
    public function incrementMessageCount()
    {
        $this->increment('message_count');
    }

    /**
     * Scope for filtering by authority
     */
    public function scopeByAuthority($query, $authority)
    {
        return $query->where('authority', $authority);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
