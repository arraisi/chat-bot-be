<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    protected $maxRetries = 3;
    protected $retryDelayMs = 1000;

    /**
     * Validate uploaded file
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        // No file size limit - allow any size

        // Check file type
        $allowedTypes = ['pdf', 'doc', 'docx', 'csv', 'xlsx', 'xls', 'json', 'txt', 'md', 'zip'];
        $extension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $allowedTypes)) {
            $errors[] = 'File type not supported. Allowed types: ' . implode(', ', $allowedTypes);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Upload file with retry mechanism
     */
    public function uploadFileWithRetry(UploadedFile $file, string $authority, string $category, ?string $tipeData = null): array
    {
        // Skip external API upload in development environment
        if (app()->environment('local', 'development')) {
            Log::info('Skipping external API upload in development environment', [
                'filename' => $file->getClientOriginalName(),
                'authority' => $authority,
                'category' => $category
            ]);

            return [
                'success' => true,
                'data' => [
                    'message' => 'File upload skipped in development environment',
                    'file_id' => 'dev_' . uniqid(),
                    'filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'authority' => $authority,
                    'category' => $category,
                    'tipe_data' => $tipeData
                ]
            ];
        }

        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                Log::info("Attempting external API upload (attempt {$attempt})", [
                    'filename' => $file->getClientOriginalName()
                ]);

                $response = Http::timeout(60)
                    ->attach('prompt', file_get_contents($file->path()), $file->getClientOriginalName())
                    ->post(config('services.upload_api.url', 'https://api.example.com/upload'), [
                        'otoritas' => $authority,
                        'category' => $category,
                        'tipe_data' => $tipeData
                    ]);

                if ($response->successful()) {
                    Log::info('External API upload successful', [
                        'filename' => $file->getClientOriginalName(),
                        'attempt' => $attempt
                    ]);

                    return [
                        'success' => true,
                        'data' => $response->json()
                    ];
                }

                $lastError = "HTTP {$response->status()}: " . $response->body();
                Log::warning("External API upload failed (attempt {$attempt})", [
                    'error' => $lastError,
                    'status' => $response->status()
                ]);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error("External API upload exception (attempt {$attempt})", [
                    'error' => $lastError,
                    'filename' => $file->getClientOriginalName()
                ]);
            }

            // Wait before retry (except on last attempt)
            if ($attempt < $this->maxRetries) {
                usleep($this->retryDelayMs * 1000);
            }
        }

        return [
            'success' => false,
            'error' => "Failed after {$this->maxRetries} attempts",
            'response' => $lastError
        ];
    }

    /**
     * Get upload limits and supported file types
     */
    public function getUploadLimits(): array
    {
        return [
            'max_file_size' => null, // No size limit
            'max_file_size_mb' => 'unlimited',
            'supported_types' => ['pdf', 'doc', 'docx', 'csv', 'xlsx', 'xls', 'json', 'txt', 'md', 'zip'],
            'max_retries' => $this->maxRetries,
            'retry_delay_ms' => $this->retryDelayMs
        ];
    }
}
