<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UploadedFileService;
use Illuminate\Http\Request;

class UploadedFileController extends Controller
{
    protected $service;

    public function __construct(UploadedFileService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return response()->json($this->service->datatables($request));
    }
}
