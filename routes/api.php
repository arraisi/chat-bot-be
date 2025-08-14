<?php

use App\Http\Controllers\Api\UploadedFileController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatSessionController;
use Illuminate\Support\Facades\Route;

// CORS test route
Route::get('test-cors', function () {
    return response()->json([
        'success' => true,
        'message' => 'CORS is working!',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::apiResource('uploaded-files', UploadedFileController::class);

// File upload routes
Route::post('upload', [FileUploadController::class, 'upload']);
Route::get('upload/limits', [FileUploadController::class, 'limits']);
Route::get('upload/test-connection', [FileUploadController::class, 'testConnection']);

// Chat bot routes
Route::post('chat', [ChatController::class, 'chat']);
Route::get('chat/test-connection', [ChatController::class, 'testConnection']);
Route::get('chat/status', [ChatController::class, 'status']);

// Chat session routes
Route::apiResource('chat-sessions', ChatSessionController::class);
Route::post('chat-sessions/{sessionId}/messages', [ChatSessionController::class, 'sendMessage']);
Route::get('chat-sessions-search', [ChatSessionController::class, 'search']);
Route::get('chat-sessions-stats', [ChatSessionController::class, 'stats']);
