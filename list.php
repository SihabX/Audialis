<?php
header('Content-Type: application/json');
$audioDir = __DIR__ . '/audio';
$files = glob($audioDir . '/*.{mp3,wav,ogg,flac,aac,m4a,wma}', GLOB_BRACE);
$audioFiles = [];
foreach ($files as $file) {
    $audioFiles[] = [
        'name' => pathinfo($file, PATHINFO_FILENAME),
        'filename' => basename($file),
        'path' => 'audio/' . basename($file),
        'size' => filesize($file),
        'ext' => pathinfo($file, PATHINFO_EXTENSION)
    ];
}
usort($audioFiles, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});
echo json_encode($audioFiles, JSON_PRETTY_PRINT);
