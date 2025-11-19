<?php

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$configPath = __DIR__ . '/../config.php';

function loadConfig($path) {
    return require $path;
}

function saveConfig($path, $config) {
    $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
    file_put_contents($path, $content);
}

function getPixels($config) {
    $pixels = [];
    foreach ($config['pixels'] as $id => $pixel) {
        if (is_array($pixel)) {
            $pixels[] = [
                'id' => $id,
                'name' => $pixel['name'] ?? '',
                'token' => $pixel['token']
            ];
        } else {
            $pixels[] = [
                'id' => $id,
                'name' => '',
                'token' => $pixel
            ];
        }
    }
    return $pixels;
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

if ($action === 'list') {
    $config = loadConfig($configPath);
    $pixels = getPixels($config);
    respondSuccess([
        'pixels' => $pixels, 
        'api_secret' => $config['api_secret'] ?? '',
        'logging_enabled' => $config['logging_enabled'] ?? false
    ]);
}

if ($action === 'add' || $action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondError('Method not allowed', 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $pixelId = $input['pixel_id'] ?? '';
    $name = $input['name'] ?? '';
    $token = $input['token'] ?? '';
    
    if (empty($pixelId) || !is_numeric($pixelId)) {
        respondError('Invalid pixel ID');
    }
    
    if (empty($token)) {
        respondError('Token is required');
    }
    
    $config = loadConfig($configPath);
    $config['pixels'][$pixelId] = [
        'name' => $name,
        'token' => $token
    ];
    saveConfig($configPath, $config);
    
    respondSuccess(['message' => 'Pixel saved']);
}

if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondError('Method not allowed', 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $pixelId = $input['pixel_id'] ?? '';
    
    if (empty($pixelId)) {
        respondError('Pixel ID is required');
    }
    
    $config = loadConfig($configPath);
    unset($config['pixels'][$pixelId]);
    saveConfig($configPath, $config);
    
    respondSuccess(['message' => 'Pixel deleted']);
}

if ($action === 'update_secret') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondError('Method not allowed', 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $secret = $input['secret'] ?? '';
    
    if (empty($secret)) {
        respondError('API Secret is required');
    }
    
    $config = loadConfig($configPath);
    $config['api_secret'] = $secret;
    saveConfig($configPath, $config);
    
    respondSuccess(['message' => 'API Secret updated']);
}

if ($action === 'generate_secret') {
    $secret = bin2hex(random_bytes(32));
    respondSuccess(['secret' => $secret]);
}

if ($action === 'toggle_logging') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondError('Method not allowed', 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $enabled = $input['enabled'] ?? false;
    
    $config = loadConfig($configPath);
    $config['logging_enabled'] = (bool)$enabled;
    saveConfig($configPath, $config);
    
    respondSuccess(['message' => 'Logging ' . ($enabled ? 'enabled' : 'disabled')]);
}

respondError('Invalid action', 400);

