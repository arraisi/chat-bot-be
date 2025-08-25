<?php

echo "PHP Upload Configuration:\n";
echo "========================\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";

echo "\nConverted to bytes:\n";
echo "==================\n";

function convertToBytes($value) {
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

$uploadMax = convertToBytes(ini_get('upload_max_filesize'));
$postMax = convertToBytes(ini_get('post_max_size'));

echo "upload_max_filesize: " . number_format($uploadMax) . " bytes (" . round($uploadMax / 1024 / 1024, 2) . " MB)\n";
echo "post_max_size: " . number_format($postMax) . " bytes (" . round($postMax / 1024 / 1024, 2) . " MB)\n";
echo "Effective limit: " . number_format(min($uploadMax, $postMax)) . " bytes (" . round(min($uploadMax, $postMax) / 1024 / 1024, 2) . " MB)\n";
