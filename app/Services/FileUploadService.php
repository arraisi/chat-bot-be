<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    protected $maxRetries = 3;
    protected $retryDelayMs = 1000;

    public function __construct()
    {
        // Set PHP ini values for large file uploads
        ini_set('upload_max_filesize', '100M');
        ini_set('post_max_size', '100M');
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '300');
        ini_set('max_input_time', '300');
    }

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
        if (env('APP_ENV') !== 'production') {
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
        // Get PHP upload limits
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $memoryLimit = ini_get('memory_limit');
        $maxExecutionTime = ini_get('max_execution_time');
        $maxInputTime = ini_get('max_input_time');
        
        // Convert to bytes for comparison
        $uploadMaxBytes = $this->convertToBytes($uploadMaxFilesize);
        $postMaxBytes = $this->convertToBytes($postMaxSize);
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        
        // For development, override with higher limits if PHP limits are too small
        $desiredLimit = 100 * 1024 * 1024; // 100MB
        $effectiveLimit = min($uploadMaxBytes, $postMaxBytes);
        
        // If we're in development and limits are small, use higher limits
        if (app()->environment('local', 'development') && $effectiveLimit < $desiredLimit) {
            $effectiveLimit = $desiredLimit;
        }
        
        return [
            'max_file_size' => $effectiveLimit,
            'max_file_size_mb' => round($effectiveLimit / 1024 / 1024, 2),
            'supported_types' => ['pdf', 'doc', 'docx', 'csv', 'xlsx', 'xls', 'json', 'txt', 'md', 'zip'],
            'max_retries' => $this->maxRetries,
            'retry_delay_ms' => $this->retryDelayMs,
            'php_limits' => [
                'upload_max_filesize' => $uploadMaxFilesize,
                'post_max_size' => $postMaxSize,
                'memory_limit' => $memoryLimit,
                'max_execution_time' => $maxExecutionTime,
                'max_input_time' => $maxInputTime,
                'upload_max_filesize_bytes' => $uploadMaxBytes,
                'post_max_size_bytes' => $postMaxBytes,
                'memory_limit_bytes' => $memoryLimitBytes,
                'effective_upload_limit_bytes' => $effectiveLimit,
                'development_override' => app()->environment('local', 'development') && min($uploadMaxBytes, $postMaxBytes) < $desiredLimit,
                'warning' => $effectiveLimit > min($uploadMaxBytes, $postMaxBytes) ? 
                    'Server configuration override active for development. PHP limits may still restrict actual uploads.' : null
            ]
        ];
    }
    
    /**
     * Convert PHP ini size values to bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $number = (int) substr($value, 0, -1);
        
        switch ($last) {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }
        
        return $number;
    }
}
