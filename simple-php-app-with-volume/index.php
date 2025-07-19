<?php
$logPath = __DIR__ . "/logs/access.log";

// Ensure the logs directory exists
if (!file_exists(dirname($logPath))) {
    mkdir(dirname($logPath), 0777, true);
}

// Write a log entry
file_put_contents($logPath, "[" . date("Y-m-d H:i:s") . "] Page visited\n", FILE_APPEND);

// Display the log content
echo "<h1>Access Log</h1>";
if (file_exists($logPath)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($logPath)) . "</pre>";
} else {
    echo "<p>No log file found.</p>";
}

