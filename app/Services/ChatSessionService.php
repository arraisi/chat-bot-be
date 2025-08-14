<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Repositories\ChatSessionRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatSessionService
{
    protected ChatSessionRepository $repository;
    protected ChatBotService $chatBotService;

    public function __construct(ChatSessionRepository $repository, ChatBotService $chatBotService)
    {
        $this->repository = $repository;
        $this->chatBotService = $chatBotService;
    }

    /**
     * Create a new chat session
     */
    public function createSession(array $data = []): array
    {
        try {
            $sessionData = array_merge([
                'session_id' => $data['session_id'] ?? Str::uuid(),
                'title' => $data['title'] ?? 'New Chat',
                'authority' => $data['authority'] ?? 'SDM',
                'user_id' => $data['user_id'] ?? null,
                'message_count' => 0,
                'last_activity_at' => now(),
            ], $data);

            $session = $this->repository->create($sessionData);

            return [
                'success' => true,
                'message' => 'Chat session created successfully',
                'session' => $this->formatSession($session),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create chat session',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get session by session_id
     */
    public function getSession(string $sessionId): array
    {
        try {
            $session = $this->repository->getSessionWithMessages($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Session not found',
                ];
            }

            return [
                'success' => true,
                'message' => 'Session retrieved successfully',
                'session' => $this->formatSessionWithMessages($session),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve session',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all sessions for a user
     */
    public function getUserSessions(?string $userId = null, int $limit = 50): array
    {
        try {
            $sessions = $this->repository->getUserSessions($userId, $limit);

            return [
                'success' => true,
                'message' => 'Sessions retrieved successfully',
                'sessions' => $sessions->map(function ($session) {
                    return $this->formatSession($session);
                })->toArray(),
                'count' => $sessions->count(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve sessions',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send message and get bot response
     */
    public function sendMessage(string $sessionId, array $messageData): array
    {
        try {
            // Find or create session
            $session = $this->repository->findBySessionId($sessionId);

            if (!$session) {
                // Create session if it doesn't exist
                $sessionResult = $this->createSession([
                    'session_id' => $sessionId,
                    'authority' => $messageData['authority'] ?? 'SDM',
                ]);

                if (!$sessionResult['success']) {
                    return $sessionResult;
                }

                $session = $this->repository->findBySessionId($sessionId);
            }

            // Add user message
            $userMessageData = [
                'message_id' => $messageData['message_id'] ?? Str::uuid(),
                'role' => 'user',
                'content' => $messageData['content'],
                'category' => $messageData['category'] ?? null,
                'authority' => $messageData['authority'] ?? $session->authority,
                'metadata' => $messageData['metadata'] ?? null,
            ];

            $userMessage = $this->repository->addMessage($session, $userMessageData);

            // Update session title if this is the first user message
            if ($session->message_count == 1) {
                $title = Str::limit($messageData['content'], 50);
                $this->repository->updateTitle($sessionId, $title);
            }

            // Get bot response - Skip external API for development
            $isDevelopment = config('app.env') === 'local' || config('app.env') === 'development';

            if ($isDevelopment) {
                // Mock response for development
                $botResponse = [
                    'success' => true,
                    'message' => "Terima kasih atas pertanyaan Anda tentang '{$messageData['content']}'. Ini adalah respons simulasi untuk pengembangan. API eksternal belum siap. Authority: " . ($messageData['authority'] ?? $session->authority) . ", Category: " . ($messageData['category'] ?? 'general'),
                    'data' => [
                        'mock' => true,
                        'authority' => $messageData['authority'] ?? $session->authority,
                        'category' => $messageData['category'] ?? 'general',
                        'timestamp' => now()->toISOString(),
                    ]
                ];
            } else {
                $botResponse = $this->chatBotService->sendMessageWithRetry(
                    $messageData['content'],
                    $messageData['authority'] ?? $session->authority,
                    $messageData['category'] ?? 'general'
                );
            }

            if ($botResponse['success']) {
                // Add assistant message
                $assistantMessageData = [
                    'message_id' => Str::uuid(),
                    'role' => 'assistant',
                    'content' => $botResponse['message'],
                    'category' => $messageData['category'] ?? null,
                    'authority' => $messageData['authority'] ?? $session->authority,
                    'metadata' => $botResponse['data'] ?? null,
                ];

                $assistantMessage = $this->repository->addMessage($session, $assistantMessageData);

                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'session_id' => $sessionId,
                    'user_message' => $this->formatMessage($userMessage),
                    'assistant_message' => $this->formatMessage($assistantMessage),
                    'bot_response' => $botResponse,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get bot response',
                    'session_id' => $sessionId,
                    'user_message' => $this->formatMessage($userMessage),
                    'error' => $botResponse['error'] ?? 'Unknown error',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update session
     */
    public function updateSession(string $sessionId, array $data): array
    {
        try {
            $session = $this->repository->findBySessionId($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Session not found',
                ];
            }

            $updated = $this->repository->update($session, $data);

            if ($updated) {
                $session->refresh();
                return [
                    'success' => true,
                    'message' => 'Session updated successfully',
                    'session' => $this->formatSession($session),
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update session',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update session',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete session
     */
    public function deleteSession(string $sessionId): array
    {
        try {
            $session = $this->repository->findBySessionId($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Session not found',
                ];
            }

            $deleted = $this->repository->delete($session);

            return [
                'success' => $deleted,
                'message' => $deleted ? 'Session deleted successfully' : 'Failed to delete session',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete session',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Search sessions
     */
    public function searchSessions(string $query, ?string $userId = null, int $limit = 20): array
    {
        try {
            $sessions = $this->repository->searchSessions($query, $userId, $limit);

            return [
                'success' => true,
                'message' => 'Search completed successfully',
                'sessions' => $sessions->map(function ($session) {
                    return $this->formatSession($session);
                })->toArray(),
                'count' => $sessions->count(),
                'query' => $query,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(?string $userId = null): array
    {
        try {
            $stats = $this->repository->getSessionStats($userId);

            return [
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format session for API response
     */
    private function formatSession(ChatSession $session): array
    {
        $latestMessage = $session->latestMessage();

        return [
            'id' => $session->session_id,
            'title' => $session->title,
            'authority' => $session->authority,
            'message_count' => $session->message_count,
            'last_activity_at' => $session->last_activity_at?->toISOString(),
            'created_at' => $session->created_at->toISOString(),
            'updated_at' => $session->updated_at->toISOString(),
            'latest_message' => $latestMessage ? $this->formatMessage($latestMessage) : null,
        ];
    }

    /**
     * Format session with all messages for API response
     */
    private function formatSessionWithMessages(ChatSession $session): array
    {
        return [
            'id' => $session->session_id,
            'title' => $session->title,
            'authority' => $session->authority,
            'message_count' => $session->message_count,
            'last_activity_at' => $session->last_activity_at?->toISOString(),
            'created_at' => $session->created_at->toISOString(),
            'updated_at' => $session->updated_at->toISOString(),
            'messages' => $session->orderedMessages->map(function ($message) {
                return $this->formatMessage($message);
            })->toArray(),
        ];
    }

    /**
     * Format message for API response
     */
    private function formatMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->message_id,
            'role' => $message->role,
            'content' => $message->content,
            'category' => $message->category,
            'authority' => $message->authority,
            'metadata' => $message->metadata,
            'is_typing' => $message->is_typing,
            'timestamp' => $message->created_at->toISOString(),
        ];
    }
}
