<?php
require '../config.php';

// Total converted
$total = $redis->get("stats:total_converted") ?: 0;

// Today’s converted
$todayKey = "stats:converted:" . date("Y-m-d");
$today = $redis->get($todayKey) ?: 0;

// Current queue length
$queue = $redis->llen("pdf_jobs");

header('Content-Type: application/json');
echo json_encode([
    'total_converted' => intval($total),
    'today_converted' => intval($today),
    'queue' => intval($queue)
]);
