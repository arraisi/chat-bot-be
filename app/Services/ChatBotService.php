<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatBotService
{
    private string $apiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('services.chatbot_api.url', 'http://10.30.14.40:8889/predict');
        $this->timeout = config('services.chatbot_api.timeout', 60);
    }

    /**
     * Send chat message to external API
     *
     * @param string $prompt
     * @param string $otoritas
     * @param string $kategori
     * @return array
     */
    public function sendMessage(string $prompt, string $otoritas, string $kategori): array
    {
        try {
            Log::info('Sending chat message to external API', [
                'prompt' => $prompt,
                'otoritas' => $otoritas,
                'kategori' => $kategori
            ]);

            $payload = [
                'prompt' => $prompt,
                'otoritas' => $otoritas,
                'kategori' => $kategori
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('Chat message sent successfully', [
                    'response' => $responseData
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => $this->extractMessageFromResponse($responseData),
                    'status_code' => $response->status()
                ];
            } else {
                Log::error('Chat message failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Chat request failed with status: ' . $response->status(),
                    'response' => $response->body(),
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Chat message exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Chat request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send chat message with retry mechanism
     *
     * @param string $prompt
     * @param string $otoritas
     * @param string $kategori
     * @param int $maxRetries
     * @return array
     */
    public function sendMessageWithRetry(string $prompt, string $otoritas, string $kategori, int $maxRetries = 3): array
    {
        $attempt = 1;

        while ($attempt <= $maxRetries) {
            Log::info("Chat message attempt {$attempt}/{$maxRetries}");

            $result = $this->sendMessage($prompt, $otoritas, $kategori);

            if ($result['success']) {
                return $result;
            }

            if ($attempt < $maxRetries) {
                $delay = $attempt * 2; // Exponential backoff: 2s, 4s, 6s
                Log::info("Retrying chat message in {$delay} seconds...");
                sleep($delay);
            }

            $attempt++;
        }

        Log::error("Chat message failed after {$maxRetries} attempts");
        return $result; // Return the last failed result
    }

    /**
     * Extract message from API response
     *
     * @param mixed $response
     * @return string
     */
    private function extractMessageFromResponse($response): string
    {
        // Handle different response formats
        if (is_string($response)) {
            return $response;
        }

        if (is_array($response)) {
            // Check common response keys
            if (isset($response['message'])) {
                return $response['message'];
            }
            if (isset($response['response'])) {
                return $response['response'];
            }
            if (isset($response['text'])) {
                return $response['text'];
            }
            if (isset($response['answer'])) {
                return $response['answer'];
            }

            // If it's just the response text directly
            return json_encode($response);
        }

        return 'No message content found';
    }

    /**
     * Validate chat message
     *
     * @param string $prompt
     * @return array
     */
    public function validateMessage(string $prompt): array
    {
        $errors = [];

        // Check message length
        if (empty(trim($prompt))) {
            $errors[] = 'Prompt cannot be empty';
        }

        if (strlen($prompt) > 5000) {
            $errors[] = 'Prompt is too long (maximum 5000 characters)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Test external API connectivity
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(10)
                ->get(str_replace('/predict', '/health', $this->apiUrl));

            return [
                'success' => true,
                'message' => 'Chat API is reachable',
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Chat API is not reachable',
                'error' => $e->getMessage()
            ];
        }
    }
}
