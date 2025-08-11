<?php

use App\Http\Controllers\Api\UploadedFileController;
use Illuminate\Support\Facades\Route;

Route::get('/uploaded-files', [UploadedFileController::class, 'index']);
