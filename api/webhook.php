<?php

header('Content-Type: application/json');

$config = require __DIR__ . '/../config.php';
$apiSecret = $config['api_secret'];
$loggingEnabled = $config['logging_enabled'] ?? false;

$pixelTokens = [];
foreach ($config['pixels'] as $id => $pixel) {
    $pixelTokens[$id] = is_array($pixel) ? $pixel['token'] : $pixel;
}

function logWebhook($data, $result) {
    global $loggingEnabled;
    
    if (!$loggingEnabled) {
        return;
    }
    
    $logDir = __DIR__ . '/../storage/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/' . date('Y-m-d') . '.log';
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'pixel_id' => $data['pixel_id'] ?? null,
        'event_type' => $data['event_type'] ?? null,
        'event_data' => $data['event_data'] ?? [],
        'success' => $result['success'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ];
    
    if (!$result['success']) {
        $logEntry['error'] = $result['error'] ?? 'Unknown error';
    }
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
}

function respondError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function respondSuccess($data) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}


function sendFacebookEvent($pixelId, $accessToken, $eventType, $eventData) {
    $eventTime = time();
    
    $payload = [
        'data' => [
            [
                'event_name' => $eventType === 'lead' ? 'Lead' : 'Purchase',
                'event_time' => $eventTime,
                'action_source' => 'website',
                'event_source_url' => $eventData['event_source_url'] ?? '',
                'user_data' => [
                    'em' => !empty($eventData['email']) ? hash('sha256', strtolower(trim($eventData['email']))) : null,
                    'ph' => !empty($eventData['phone']) ? hash('sha256', preg_replace('/[^0-9]/', '', $eventData['phone'])) : null,
                    'fn' => !empty($eventData['first_name']) ? hash('sha256', strtolower(trim($eventData['first_name']))) : null,
                    'ln' => !empty($eventData['last_name']) ? hash('sha256', strtolower(trim($eventData['last_name']))) : null,
                    'client_ip_address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
                    'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'fbc' => $eventData['fbc'] ?? null,
                    'fbp' => $eventData['fbp'] ?? null,
                ],
            ]
        ],
        'access_token' => $accessToken,
    ];
    
    if ($eventType === 'purchase' || $eventType === 'Purchase') {
        $payload['data'][0]['custom_data'] = [
            'currency' => $eventData['currency'] ?? 'USD',
            'value' => $eventData['value'] ?? 0,
        ];
    }
    
    $payload['data'][0]['user_data'] = array_filter($payload['data'][0]['user_data']);
    
    $url = "https://graph.facebook.com/v18.0/{$pixelId}/events";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Facebook API curl error: {$curlError}");
        return ['success' => false, 'error' => 'Network error'];
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode !== 200) {
        error_log("Facebook API error (HTTP {$httpCode}): {$response}");
        return ['success' => false, 'error' => 'Facebook API error', 'details' => $responseData];
    }
    
    return ['success' => true, 'response' => $responseData];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Method not allowed', 405);
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$apiKey = str_replace('Bearer ', '', $authHeader);

if ($apiKey !== $apiSecret) {
    respondError('Unauthorized', 401);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    respondError('Invalid JSON');
}

if (empty($data['pixel_id'])) {
    respondError('pixel_id is required');
}

$pixelId = $data['pixel_id'];
$eventType = $data['event_type'] ?? 'purchase';
$eventData = $data['event_data'] ?? [];

if (!isset($pixelTokens[$pixelId])) {
    error_log("Unknown pixel_id: {$pixelId}");
    respondError('Unknown pixel_id', 404);
}

$accessToken = $pixelTokens[$pixelId];

$result = sendFacebookEvent($pixelId, $accessToken, $eventType, $eventData);

logWebhook($data, $result);

if ($result['success']) {
    respondSuccess([
        'events_sent' => 1,
        'facebook_response' => $result['response'],
    ]);
} else {
    respondError($result['error'], 500);
}

