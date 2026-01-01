<?php

// Простой скрипт для отладки запросов от frontend

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$response = [
    'received_method' => $_SERVER['REQUEST_METHOD'],
    'received_headers' => getallheaders(),
    'received_body_raw' => $input,
    'received_body_parsed' => $data,
    'received_get' => $_GET,
    'received_post' => $_POST,
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
