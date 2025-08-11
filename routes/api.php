<?php

use App\Http\Controllers\Api\UploadedFileController;
use Illuminate\Support\Facades\Route;

Route::apiResource('uploaded-files', UploadedFileController::class);
