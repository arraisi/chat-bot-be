<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class FileUploadService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.upload_api.url', 'http://10.30.14.40:8888/predict');
    }

    /**
     * Upload file to external API
     *
     * @param UploadedFile $file
     * @param string $otoritas
     * @param string $category
     * @param string $tipeData
     * @return array
     * @throws \Exception
     */
    public function uploadFile(UploadedFile $file, string $otoritas, string $category, string $tipeData): array
    {
        try {
            Log::info('Starting file upload to external API', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'otoritas' => $otoritas,
                'category' => $category,
                'tipe_data' => $tipeData
            ]);

            $response = Http::timeout(120) // 2 minutes timeout for file upload
                ->attach(
                    'prompt',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )
                ->post($this->apiUrl, [
                    'otoritas' => $otoritas,
                    'category' => $category,
                    'tipe_data' => $tipeData
                ]);

            if ($response->successful()) {
                Log::info('File upload successful', ['response' => $response->json()]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status_code' => $response->status()
                ];
            } else {
                Log::error('File upload failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Upload failed with status: ' . $response->status(),
                    'response' => $response->body(),
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('File upload exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload file with automatic retry mechanism
     *
     * @param UploadedFile $file
     * @param string $otoritas
     * @param string $category
     * @param string $tipeData
     * @param int $maxRetries
     * @return array
     */
    public function uploadFileWithRetry(UploadedFile $file, string $otoritas, string $category, string $tipeData, int $maxRetries = 3): array
    {
        $attempt = 1;

        while ($attempt <= $maxRetries) {
            Log::info("File upload attempt {$attempt}/{$maxRetries}");

            $result = $this->uploadFile($file, $otoritas, $category, $tipeData);

            if ($result['success']) {
                return $result;
            }

            if ($attempt < $maxRetries) {
                $delay = $attempt * 2; // Exponential backoff: 2s, 4s, 6s
                Log::info("Retrying upload in {$delay} seconds...");
                sleep($delay);
            }

            $attempt++;
        }

        Log::error("File upload failed after {$maxRetries} attempts");
        return $result; // Return the last failed result
    }

    /**
     * Validate file before upload
     *
     * @param UploadedFile $file
     * @return array
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        // Check if file is valid
        if (!$file->isValid()) {
            $errors[] = 'File is not valid';
        }

        // Check file size (max 50MB)
        $maxSize = 50 * 1024 * 1024; // 50MB in bytes
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum limit of 50MB';
        }

        // Check file extension
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get supported file types and size limits
     *
     * @return array
     */
    public function getUploadLimits(): array
    {
        return [
            'max_size_mb' => 50,
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'txt'],
            'allowed_mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ]
        ];
    }
}
