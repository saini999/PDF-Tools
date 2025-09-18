<?php
require __DIR__ . '/vendor/autoload.php';



$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
]);


// Directories
define('UPLOAD_DIR', __DIR__ . '/tmp/uploads/');
define('PROCESSED_DIR', __DIR__ . '/tmp/processed/');

// Ensure dirs exist
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
if (!is_dir(PROCESSED_DIR)) mkdir(PROCESSED_DIR, 0775, true);
