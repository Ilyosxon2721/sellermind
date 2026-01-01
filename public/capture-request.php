<?php

// Этот скрипт перехватывает и сохраняет все запросы для отладки

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Получаем все данные запроса
$input = file_get_contents('php://input');
$headers = getallheaders();

// Парсим JSON
$jsonData = json_decode($input, true);

// Создаем детальный лог
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'] ?? '/',
    'headers' => $headers,
    'raw_body' => $input,
    'parsed_json' => $jsonData,
    'json_error' => json_last_error_msg(),
    'get' => $_GET,
    'post' => $_POST,
    'server' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
        'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? '',
        'CONTENT_LENGTH' => $_SERVER['CONTENT_LENGTH'] ?? 0,
    ]
];

// Сохраняем в файл для анализа
$logFile = __DIR__ . '/request-log.json';
file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Возвращаем красивый ответ
$response = [
    'status' => 'captured',
    'message' => 'Запрос успешно перехвачен и сохранён',
    'log_file' => 'request-log.json',
    'analysis' => [
        'has_authorization' => isset($headers['Authorization']) || isset($headers['authorization']),
        'authorization_value' => $headers['Authorization'] ?? $headers['authorization'] ?? null,
        'has_content_type' => isset($headers['Content-Type']) || isset($headers['content-type']),
        'content_type' => $headers['Content-Type'] ?? $headers['content-type'] ?? null,
        'body_length' => strlen($input),
        'is_valid_json' => json_last_error() === JSON_ERROR_NONE,
        'json_error' => json_last_error() === JSON_ERROR_NONE ? null : json_last_error_msg(),
    ],
    'received_data' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'headers' => $headers,
        'body' => $input,
        'parsed' => $jsonData,
    ],
    'validation_checks' => []
];

// Проверяем что нужно для создания аккаунта
if ($jsonData) {
    $checks = [];

    $checks['has_company_id'] = isset($jsonData['company_id']);
    $checks['company_id_type'] = isset($jsonData['company_id']) ? gettype($jsonData['company_id']) : null;
    $checks['company_id_value'] = $jsonData['company_id'] ?? null;

    $checks['has_marketplace'] = isset($jsonData['marketplace']);
    $checks['marketplace_value'] = $jsonData['marketplace'] ?? null;
    $checks['marketplace_valid'] = in_array($jsonData['marketplace'] ?? '', ['wb', 'uzum', 'ozon', 'ym']);

    $checks['has_credentials'] = isset($jsonData['credentials']);
    $checks['credentials_type'] = isset($jsonData['credentials']) ? gettype($jsonData['credentials']) : null;
    $checks['credentials_is_array'] = isset($jsonData['credentials']) && is_array($jsonData['credentials']);
    $checks['credentials_keys'] = isset($jsonData['credentials']) && is_array($jsonData['credentials'])
        ? array_keys($jsonData['credentials'])
        : null;

    $response['validation_checks'] = $checks;
}

// Выводим ответ
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
