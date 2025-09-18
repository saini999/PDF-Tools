<?php
// cleanup.php
require __DIR__ . '/../config.php';
// You can now use UPLOAD_DIR and PROCESSED_DIR
$uploadDir = UPLOAD_DIR;
$processedDir = PROCESSED_DIR;


$now = time();

echo "Ran Cleanup at" . $now;

// Helper function to check if a job is in Redis
function isJobActive($redis, $filePath)
{

    $now = time();

    // Check processed and uploaded jobs in Redis
    $jobs = $redis->keys('job:*');

    foreach ($jobs as $jobKey) {


        if (str_ends_with($jobKey, ':messages')) continue;

        $job = $redis->hgetall($jobKey);


        // Check if this file is either input (upload) or output (processed)
        $inputMatches  = isset($job['input'])  && $job['input']  === $filePath;
        $outputMatches = isset($job['output']) && $job['output'] === $filePath;
        echo "Reached a Job" . $job['id'];

        if ($inputMatches || $outputMatches) {
            $status = $job['status'] ?? '';
            $completedAt = isset($job['completed_at']) ? intval($job['completed_at']) : 0;

            // Keep if job is queued or compressing
            if (in_array($status, ['queued', 'compressing'])) {

                return true;
            };

            // Keep if done/warning but within KEEP_MINUTES
            if (in_array($status, ['done', 'warning']) && ($now - $completedAt) < 5 * 60) {

                return true;
            };


            // Otherwise, can delete
            return false;
        }
    }

    // File not tracked in Redis → safe to delete
    return false;
}

// Clean uploads
foreach (glob($uploadDir . '*') as $file) {
    if (!isJobActive($redis, $file)) {
        @unlink($file);
        echo "Deleted upload: $file\n";
    }
}

// Clean processed
foreach (glob($processedDir . '*') as $file) {
    if (!isJobActive($redis, $file)) {
        @unlink($file);
        echo "Deleted processed: $file\n";
    }
}
