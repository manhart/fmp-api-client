<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/slow' || str_starts_with($path, '/slow/')) {
    sleep(2);
    echo '[]';
    return;
}
if ($path === '/large' || str_starts_with($path, '/large/')) {
    echo str_repeat('x', 65536);
    return;
}
if ($path === '/error' || str_starts_with($path, '/error/')) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['Error Message' => 'Rate limit for '.$_GET['apikey']], JSON_THROW_ON_ERROR);
    return;
}
header('Content-Type: application/json');
echo json_encode(['path' => $path, 'query' => $_GET], JSON_THROW_ON_ERROR);
