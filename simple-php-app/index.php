<?php
$logPath = "logs/access.log";
file_put_contents($logPath, "[" . date("Y-m-d H:i:s") . "] Accessed\n", FILE_APPEND);
echo "Hello from PHP inside Docker!";
?>
