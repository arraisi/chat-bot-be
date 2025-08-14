<?php

namespace App\Repositories;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Support\Collection;

class ChatSessionRepository
{
    /**
     * Find session by session_id
     */
    public function findBySessionId(string $sessionId): ?ChatSession
    {
        return ChatSession::where('session_id', $sessionId)->first();
    }

    /**
     * Create a new chat session
     */
    public function create(array $data): ChatSession
    {
        return ChatSession::create($data);
    }

    /**
     * Update a chat session
     */
    public function update(ChatSession $session, array $data): bool
    {
        return $session->update($data);
    }

    /**
     * Delete a chat session
     */
    public function delete(ChatSession $session): bool
    {
        return $session->delete();
    }

    /**
     * Get all sessions for a user (optional user_id filter)
     */
    public function getUserSessions(?string $userId = null, int $limit = 50): Collection
    {
        $query = ChatSession::with(['messages' => function ($q) {
            $q->latest()->limit(1); // Get latest message for preview
        }])
            ->orderBy('last_activity_at', 'desc')
            ->limit($limit);

        if ($userId) {
            $query->byUser($userId);
        }

        return $query->get();
    }

    /**
     * Get sessions by authority
     */
    public function getSessionsByAuthority(string $authority, int $limit = 50): Collection
    {
        return ChatSession::byAuthority($authority)
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('last_activity_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get session with all messages
     */
    public function getSessionWithMessages(string $sessionId): ?ChatSession
    {
        return ChatSession::where('session_id', $sessionId)
            ->with(['orderedMessages'])
            ->first();
    }

    /**
     * Add message to session
     */
    public function addMessage(ChatSession $session, array $messageData): ChatMessage
    {
        $message = $session->messages()->create($messageData);

        // Update session activity and message count
        $session->updateActivity();
        $session->incrementMessageCount();

        return $message;
    }

    /**
     * Update message
     */
    public function updateMessage(string $messageId, array $data): bool
    {
        return ChatMessage::where('message_id', $messageId)->update($data);
    }

    /**
     * Delete message
     */
    public function deleteMessage(string $messageId): bool
    {
        $message = ChatMessage::where('message_id', $messageId)->first();

        if ($message) {
            $session = $message->chatSession;
            $deleted = $message->delete();

            if ($deleted) {
                // Update message count
                $session->decrement('message_count');
            }

            return $deleted;
        }

        return false;
    }

    /**
     * Update session title
     */
    public function updateTitle(string $sessionId, string $title): bool
    {
        return ChatSession::where('session_id', $sessionId)
            ->update(['title' => $title]);
    }

    /**
     * Search sessions by title or content
     */
    public function searchSessions(string $query, ?string $userId = null, int $limit = 20): Collection
    {
        $searchQuery = ChatSession::where('title', 'like', "%{$query}%")
            ->orWhereHas('messages', function ($q) use ($query) {
                $q->where('content', 'like', "%{$query}%");
            })
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('last_activity_at', 'desc')
            ->limit($limit);

        if ($userId) {
            $searchQuery->byUser($userId);
        }

        return $searchQuery->get();
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(?string $userId = null): array
    {
        $query = ChatSession::query();

        if ($userId) {
            $query->byUser($userId);
        }

        $totalSessions = $query->count();
        $totalMessages = ChatMessage::whereHas('chatSession', function ($q) use ($userId) {
            if ($userId) {
                $q->byUser($userId);
            }
        })->count();

        $byAuthority = ChatSession::selectRaw('authority, count(*) as count')
            ->when($userId, function ($q, $userId) {
                $q->byUser($userId);
            })
            ->groupBy('authority')
            ->pluck('count', 'authority')
            ->toArray();

        return [
            'total_sessions' => $totalSessions,
            'total_messages' => $totalMessages,
            'by_authority' => $byAuthority,
            'average_messages_per_session' => $totalSessions > 0 ? round($totalMessages / $totalSessions, 2) : 0
        ];
    }
}
