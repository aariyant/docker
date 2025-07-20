<?php
// Serve own ping response
if ($_SERVER['REQUEST_URI'] === '/ping') {
    header('Content-Type: text/plain');
    echo "pong from PHP";
    exit;
}

// Call nginx service
$nginxResponse = @file_get_contents('http://nginx/ping');

echo "<h1>PHP ↔ NGINX via Docker Network</h1>";
echo "<p><strong>PHP says:</strong> pong from PHP</p>";
echo "<p><strong>NGINX says:</strong> " . htmlspecialchars($nginxResponse ?: 'no response') . "</p>";
