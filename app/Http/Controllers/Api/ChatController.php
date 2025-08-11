<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatBotService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $chatBotService;

    public function __construct(ChatBotService $chatBotService)
    {
        $this->chatBotService = $chatBotService;
    }

    /**
     * Send a chat message to the bot
     */
    public function chat(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:5000',
            'otoritas' => 'required|string|max:100',
            'kategori' => 'required|string|max:100',
        ]);

        $prompt = $request->input('prompt');
        $otoritas = $request->input('otoritas');
        $kategori = $request->input('kategori');

        // Validate message
        $validation = $this->chatBotService->validateMessage($prompt);
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Prompt validation failed',
                'errors' => $validation['errors']
            ], 422);
        }

        // Send message to chat bot with retry
        $result = $this->chatBotService->sendMessageWithRetry($prompt, $otoritas, $kategori);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Chat response received',
                'response' => $result['message'],
                'raw_data' => $result['data'],
                'timestamp' => now()->toISOString()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Chat request failed',
                'error' => $result['error'],
                'details' => $result['response'] ?? null
            ], 500);
        }
    }

    /**
     * Test chat bot API connectivity
     */
    public function testConnection()
    {
        $result = $this->chatBotService->testConnection();

        return response()->json($result, $result['success'] ? 200 : 503);
    }

    /**
     * Get chat bot status and information
     */
    public function status()
    {
        return response()->json([
            'success' => true,
            'service' => 'Chat Bot API',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
            'endpoints' => [
                'chat' => 'POST /api/chat',
                'test_connection' => 'GET /api/chat/test-connection',
                'status' => 'GET /api/chat/status'
            ]
        ]);
    }
}
