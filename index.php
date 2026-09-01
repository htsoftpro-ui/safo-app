<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$market = __DIR__ . '/market.html';
if (is_file($market)) {
    readfile($market);
    exit;
}
http_response_code(503);
echo 'Safo market is temporarily unavailable.';
