<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

// Create a temporary file for testing
$tempFile = tmpfile();
$tempPath = stream_get_meta_data($tempFile)['uri'];
fwrite($tempFile, 'PDF test content');
rewind($tempFile);

// Create a mock UploadedFile
$uploadedFile = new UploadedFile(
    $tempPath,
    'test.pdf',
    'application/pdf',
    filesize($tempPath),
    UPLOAD_ERR_OK,
    true
);

// Test the validation
$service = new FileUploadService();
$result = $service->validateFile($uploadedFile);

echo "Validation result:\n";
echo "Valid: " . ($result['valid'] ? 'true' : 'false') . "\n";
echo "Errors: " . json_encode($result['errors']) . "\n";

// Test upload limits
$limits = $service->getUploadLimits();
echo "\nUpload limits:\n";
echo json_encode($limits, JSON_PRETTY_PRINT) . "\n";

fclose($tempFile);
