<?php
function log_audit($message) {
    // Basic audit logging implementation
    $logFile = __DIR__ . '/../database/audit.log';
    $entry = date('c') . " | " . $message . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}
?>
