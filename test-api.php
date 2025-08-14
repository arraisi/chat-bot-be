<?php

// Simple test script to test the chat session API
$baseUrl = 'http://localhost:8000/api';

// Test data
$testData = [
    'content' => 'Halo, saya ingin bertanya tentang kebijakan SDM',
    'category' => 'question',
    'authority' => 'SDM',
    'message_id' => 'msg_' . uniqid(),
    'metadata' => [
        'timestamp' => date('c'),
        'source' => 'test'
    ]
];

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat-sessions/session_test_123/messages');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "Testing Chat Session API...\n";
echo "URL: " . $baseUrl . '/chat-sessions/session_test_123/messages' . "\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "Response Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: $response\n";

// Also test a simple GET request
echo "\n" . str_repeat("-", 50) . "\n";
echo "Testing GET all sessions...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/chat-sessions');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "Response Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: $response\n";
