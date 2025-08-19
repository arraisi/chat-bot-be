<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatSessionController extends Controller
{
    protected ChatSessionService $chatSessionService;

    public function __construct(ChatSessionService $chatSessionService)
    {
        $this->chatSessionService = $chatSessionService;
    }

    /**
     * Get all chat sessions for a user
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $limit = (int) $request->query('limit', 50);

        $result = $this->chatSessionService->getUserSessions($userId, $limit);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Create a new chat session
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'sometimes|string|max:255',
            'title' => 'sometimes|string|max:255',
            'authority' => 'sometimes|in:ALL,SDM,HUKUM,ADMIN',
            'user_id' => 'sometimes|string|max:255',
            'user_account' => 'sometimes|array',
            'user_account.id' => 'sometimes|string|max:255',
            'user_account.username' => 'sometimes|string|max:255',
            'user_account.name' => 'sometimes|string|max:255',
            'user_account.email' => 'sometimes|email|max:255',
            'user_account.authority' => 'sometimes|in:ALL,SDM,HUKUM,ADMIN',
            'user_account.roles' => 'sometimes|array',
            'user_account.employee_id' => 'sometimes|string|max:255',
            'user_account.department' => 'sometimes|string|max:255',
        ]);

        // Prepare data for session creation
        $sessionData = $request->all();

        // Extract user_id from user_account.id if user_account exists
        if ($request->has('user_account') && isset($request->user_account['id'])) {
            $sessionData['user_id'] = $request->user_account['id'];
        }

        $result = $this->chatSessionService->createSession($sessionData);

        return response()->json($result, $result['success'] ? 201 : 500);
    }

    /**
     * Get a specific chat session with all messages
     */
    public function show(string $sessionId): JsonResponse
    {
        $result = $this->chatSessionService->getSession($sessionId);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Update a chat session
     */
    public function update(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'authority' => 'sometimes|in:ALL,SDM,HUKUM,ADMIN',
        ]);

        $result = $this->chatSessionService->updateSession($sessionId, $request->all());

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Delete a chat session
     */
    public function destroy(string $sessionId): JsonResponse
    {
        $result = $this->chatSessionService->deleteSession($sessionId);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Send a message to a chat session
     */
    public function sendMessage(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'category' => 'sometimes|string|max:100',
            'authority' => 'sometimes|in:ALL,SDM,HUKUM,ADMIN',
            'message_id' => 'sometimes|string|max:255',
            'metadata' => 'sometimes|array',
        ]);

        $messageData = [
            'content' => $request->input('content'),
            'category' => $request->input('category', 'general'),
            'authority' => $request->input('authority', 'SDM'),
            'message_id' => $request->input('message_id'),
            'metadata' => $request->input('metadata'),
        ];

        $result = $this->chatSessionService->sendMessage($sessionId, $messageData);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Search chat sessions
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|max:255',
            'user_id' => 'sometimes|string|max:255',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $request->input('q');
        $userId = $request->input('user_id');
        $limit = (int) $request->input('limit', 20);

        $result = $this->chatSessionService->searchSessions($query, $userId, $limit);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get chat session statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');

        $result = $this->chatSessionService->getSessionStats($userId);

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
