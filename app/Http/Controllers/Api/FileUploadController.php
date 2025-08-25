<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Services\UploadedFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    protected $uploadService;
    protected $uploadedFileService;

    public function __construct(FileUploadService $uploadService, UploadedFileService $uploadedFileService)
    {
        $this->uploadService = $uploadService;
        $this->uploadedFileService = $uploadedFileService;
    }

    /**
     * Upload file to external API
     */
    public function upload(Request $request)
    {
        info('Received file upload request', ['request' => $request->all()]);

        $request->validate([
            'prompt' => 'required|file',
            'otoritas' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'tipe_data' => 'sometimes|string'
        ]);

        $file = $request->file('prompt');
        $otoritas = $request->input('otoritas');
        $category = $request->input('category');
        $tipeData = $request->input('tipe_data');

        // Validate file before upload
        $validation = $this->uploadService->validateFile($file);
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $validation['errors']
            ], 422);
        }

        // Upload to external API with retry
        $result = $this->uploadService->uploadFileWithRetry($file, $otoritas, $category, $tipeData);

        if ($result['success']) {
            // Store file record in database
            try {
                $uploadedFile = $this->uploadedFileService->create([
                    'filename' => $file->getClientOriginalName(),
                    'path' => 'external-api', // Since file is uploaded to external API
                    'size' => $file->getSize(),
                    'authority' => $otoritas,
                    'category' => $category,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded to external API successfully',
                    'data' => $result['data'],
                    'file_info' => [
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'authority' => $otoritas,
                        'category' => $category,
                        'tipe_data' => $tipeData
                    ]
                ], 201);
            } catch (\Exception $e) {
                // External upload succeeded but database save failed
                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded to external API but failed to save record',
                    'external_api_response' => $result['data'],
                    'database_error' => $e->getMessage()
                ], 201);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed',
                'error' => $result['error'],
                'details' => $result['response'] ?? null
            ], 500);
        }
    }

    /**
     * Get upload limits and supported file types
     */
    public function limits()
    {
        return response()->json([
            'success' => true,
            'data' => $this->uploadService->getUploadLimits()
        ]);
    }

    /**
     * Test external API connectivity
     */
    public function testConnection()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get(config('services.upload_api.url'));

            return response()->json([
                'success' => true,
                'message' => 'External API is reachable',
                'status_code' => $response->status()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'External API is not reachable',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Get current PHP configuration for debugging
     */
    public function phpInfo()
    {
        return response()->json([
            'success' => true,
            'php_config' => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'max_input_time' => ini_get('max_input_time'),
            ]
        ]);
    }
}
