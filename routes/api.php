<?php

use App\Http\Controllers\Api\UploadedFileController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

Route::apiResource('uploaded-files', UploadedFileController::class);

// File upload routes
Route::post('upload', [FileUploadController::class, 'upload']);
Route::get('upload/limits', [FileUploadController::class, 'limits']);
Route::get('upload/test-connection', [FileUploadController::class, 'testConnection']);

// Chat bot routes
Route::post('chat', [ChatController::class, 'chat']);
Route::get('chat/test-connection', [ChatController::class, 'testConnection']);
Route::get('chat/status', [ChatController::class, 'status']);
