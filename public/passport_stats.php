<?php
require '../config.php';


$total = $redis->get('passport_total') ?: 0;
$todayKey = 'passport_today:' . date('Y-m-d');
$today = $redis->get($todayKey) ?: 0;
$queue = $redis->llen('passport_queue') ?: 0;

echo json_encode([
    'total' => $total,
    'today' => $today,
    'queue' => $queue
]);
