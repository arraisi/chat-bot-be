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

        // TEMPORARY MOCK RESPONSE FOR TESTING
        // Remove this block when external API is working
        if (config('app.env') === 'local' || $request->has('mock')) {
            return response()->json([
                'success' => true,
                'message' => 'Mock chat response received',
                'response' => "Berikut adalah visi, misi, tata nilai, dan moto Peruri:\n\n*Visi:\nMenjadi korporasi percetakan sekuriti terintegrasi dan solusi digital sekuriti kelas dunia.\n\nMisi:\n\na. Sebagai mitra terpercaya (trusted partner) dalam menyediakan produk sekuriti tinggi dan layanan digital penjamin keaslian terintegrasi kelas dunia\nb. Memaksimalkan nilai tambah bagi negara, mitra dan karyawan\nc. Memberikan kontribusi positif terhadap lingkungan, kepada bangsa dan negara.\n\nTata Nilai:\nAKHLAK (Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif)\n\nMoto:* Cergas, Cepat, Cermat, Cerdas, Ceria\n\n 📄 *Referensi:*\n- pdf-code-of-conduct.pdf, Sub Bab A. Visi, Misi, Tata Nilai, dan Moto",
                'raw_data' => [
                    'prompt' => $prompt,
                    'otoritas' => $otoritas,
                    'kategori' => $kategori,
                    'mock' => true
                ],
                'timestamp' => now()->toISOString()
            ]);
        }
        // END MOCK RESPONSE

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
