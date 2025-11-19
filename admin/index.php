<?php

$config = require __DIR__ . '/../config.php';
$validUsers = $config['admin_users'];

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

if (!isset($validUsers[$user]) || $validUsers[$user] !== $pass) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Invalid credentials';
    exit;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Pixel Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>🎯 Pixel Admin</h1>
        </div>
        <div class="sidebar-menu">
            <div class="menu-item active" data-section="test">
                <span>🧪</span> Test Pixel
            </div>
            <div class="menu-item" data-section="manage">
                <span>⚙️</span> Manage Pixels
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="content-section active" id="test">
            <div class="card">
                <h2>Test Pixel Event</h2>
                <div id="testMessage"></div>
                
                <form id="testForm">
                    <div class="form-group">
                        <label>Select Pixel</label>
                        <select id="testPixelSelect" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Event Type</label>
                        <select id="testEventType">
                            <option value="purchase">Purchase</option>
                            <option value="lead">Lead</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Email (optional)</label>
                        <input type="email" id="testEmail" placeholder="test@example.com">
                    </div>
                    
                    <div class="form-group" id="purchaseFields">
                        <label>Value</label>
                        <input type="number" id="testValue" step="0.01" placeholder="99.99">
                        
                        <label style="margin-top: 15px;">Currency</label>
                        <input type="text" id="testCurrency" value="USD" maxlength="3">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Event</button>
                </form>
            </div>
        </div>

        <div class="content-section" id="manage">
            <div class="card">
                <h2>Manage Pixels</h2>
                <div id="manageMessage"></div>
                
                <div class="form-group">
                    <label>Add New Pixel</label>
                    <div class="input-group">
                        <input type="text" id="newPixelName" placeholder="Pixel Name">
                        <input type="text" id="newPixelId" placeholder="Pixel ID">
                        <input type="text" id="newPixelToken" placeholder="Access Token">
                        <button class="btn btn-success" onclick="addPixel()">Add</button>
                    </div>
                </div>
                
                <div class="pixel-list" id="pixelList">
                    <p style="color: #64748b; text-align: center;">Loading...</p>
                </div>
            </div>
            
            <div class="card">
                <h2>API Secret</h2>
                <div id="secretMessage"></div>
                
                <div class="form-group">
                    <label>Current API Secret</label>
                    <div class="input-group">
                        <input type="text" id="apiSecret" readonly>
                        <button class="btn btn-secondary" onclick="generateSecret()">Generate New</button>
                        <button class="btn btn-primary" onclick="updateSecret()">Save</button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>Logging</h2>
                <div id="loggingMessage"></div>
                
                <div class="form-group">
                    <label>Webhook Logging</label>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <label class="toggle-switch">
                            <input type="checkbox" id="loggingToggle" onchange="toggleLogging()">
                            <span class="toggle-slider"></span>
                        </label>
                        <span id="loggingStatus" style="color: #64748b; font-weight: 600;"></span>
                    </div>
                    <p style="color: #94a3b8; font-size: 13px; margin-top: 8px;">Logs are saved to storage/logs/YYYY-MM-DD.log</p>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
