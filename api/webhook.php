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

$apiKey = $_GET['apikey'] ?? '';

if ($apiKey !== $apiSecret) {
    respondError('Unauthorized', 401);
}

if (empty($_GET['pixel_id'])) {
    respondError('pixel_id is required');
}

if (empty($_GET['token'])) {
    respondError('token is required');
}

$pixelId = $_GET['pixel_id'];
$accessToken = $_GET['token'];
$eventType = $_GET['event_type'] ?? 'purchase';

$eventData = [];
if (!empty($_GET['email'])) $eventData['email'] = $_GET['email'];
if (!empty($_GET['phone'])) $eventData['phone'] = $_GET['phone'];
if (!empty($_GET['first_name'])) $eventData['first_name'] = $_GET['first_name'];
if (!empty($_GET['last_name'])) $eventData['last_name'] = $_GET['last_name'];
if (!empty($_GET['value'])) $eventData['value'] = (float)$_GET['value'];
if (!empty($_GET['currency'])) $eventData['currency'] = $_GET['currency'];
if (!empty($_GET['event_source_url'])) $eventData['event_source_url'] = $_GET['event_source_url'];
if (!empty($_GET['fbc'])) $eventData['fbc'] = $_GET['fbc'];
if (!empty($_GET['fbp'])) $eventData['fbp'] = $_GET['fbp'];

$data = [
    'pixel_id' => $pixelId,
    'event_type' => $eventType,
    'event_data' => $eventData
];

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

