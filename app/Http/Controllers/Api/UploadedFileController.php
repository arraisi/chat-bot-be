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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'filename' => 'required|string|max:255',
            'path' => 'required|string|max:500',
            'size' => 'required|integer|min:0',
            'authority' => 'required|string|max:100',
            'category' => 'required|string|max:100',
        ]);

        $uploadedFile = $this->service->create($validated);

        return response()->json([
            'message' => 'File created successfully',
            'data' => $uploadedFile
        ], 201);
    }

    public function show($id)
    {
        $uploadedFile = $this->service->findById($id);

        if (!$uploadedFile) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->json(['data' => $uploadedFile]);
    }

    public function update(Request $request, $id)
    {
        $uploadedFile = $this->service->findById($id);

        if (!$uploadedFile) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $validated = $request->validate([
            'filename' => 'sometimes|string|max:255',
            'path' => 'sometimes|string|max:500',
            'size' => 'sometimes|integer|min:0',
            'authority' => 'sometimes|string|max:100',
            'category' => 'sometimes|string|max:100',
        ]);

        $updatedFile = $this->service->update($id, $validated);

        return response()->json([
            'message' => 'File updated successfully',
            'data' => $updatedFile
        ]);
    }

    public function destroy($id)
    {
        $uploadedFile = $this->service->findById($id);

        if (!$uploadedFile) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $this->service->delete($id);

        return response()->json(['message' => 'File deleted successfully']);
    }
}
