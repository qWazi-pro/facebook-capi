let pixels = [];
let apiSecret = '';

document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        
        item.classList.add('active');
        document.getElementById(item.dataset.section).classList.add('active');
    });
});

document.getElementById('testEventType').addEventListener('change', (e) => {
    document.getElementById('purchaseFields').style.display = 
        e.target.value === 'purchase' ? 'block' : 'none';
});

document.getElementById('testForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const pixelId = document.getElementById('testPixelSelect').value;
    const eventType = document.getElementById('testEventType').value;
    const email = document.getElementById('testEmail').value;
    const value = document.getElementById('testValue').value;
    const currency = document.getElementById('testCurrency').value;
    
    const eventData = {
        event_source_url: window.location.href
    };
    
    if (email) eventData.email = email;
    if (eventType === 'purchase') {
        if (value) eventData.value = parseFloat(value);
        if (currency) eventData.currency = currency;
    }
    
    showMessage('testMessage', 'Sending...', 'success');
    
    try {
        const response = await fetch('/api/webhook.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiSecret}`
            },
            body: JSON.stringify({
                pixel_id: pixelId,
                event_type: eventType,
                event_data: eventData
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('testMessage', '✅ Event sent successfully!', 'success');
        } else {
            showMessage('testMessage', '❌ ' + data.error, 'error');
        }
    } catch (error) {
        showMessage('testMessage', '❌ ' + error.message, 'error');
    }
});

async function loadPixels() {
    try {
        const response = await fetch('/api/admin-api.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            pixels = data.data.pixels;
            apiSecret = data.data.api_secret;
            
            updatePixelSelect();
            updatePixelList();
            document.getElementById('apiSecret').value = apiSecret;
            
            const loggingEnabled = data.data.logging_enabled;
            document.getElementById('loggingToggle').checked = loggingEnabled;
            updateLoggingStatus(loggingEnabled);
        }
    } catch (error) {
        console.error('Error loading pixels:', error);
    }
}

function updatePixelSelect() {
    const select = document.getElementById('testPixelSelect');
    select.innerHTML = pixels.length > 0 
        ? pixels.map(p => `<option value="${p.id}">${p.name || 'Pixel ' + p.id}</option>`).join('')
        : '<option value="">No pixels configured</option>';
}

function updatePixelList() {
    const list = document.getElementById('pixelList');
    
    if (pixels.length === 0) {
        list.innerHTML = '<p style="color: #64748b; text-align: center;">No pixels configured</p>';
        return;
    }
    
    list.innerHTML = pixels.map(p => `
        <div class="pixel-item">
            <div class="pixel-info">
                <div class="pixel-id">${p.name || 'Pixel ' + p.id}</div>
                <div class="pixel-token">ID: ${p.id} • ${p.token.substring(0, 20)}...</div>
            </div>
            <div class="pixel-actions">
                <button class="btn btn-danger" onclick="deletePixel('${p.id}')">Delete</button>
            </div>
        </div>
    `).join('');
}

async function addPixel() {
    const name = document.getElementById('newPixelName').value;
    const pixelId = document.getElementById('newPixelId').value;
    const token = document.getElementById('newPixelToken').value;
    
    if (!pixelId || !token) {
        showMessage('manageMessage', '❌ Fill Pixel ID and Token', 'error');
        return;
    }
    
    try {
        const response = await fetch('/api/admin-api.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, pixel_id: pixelId, token: token })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('manageMessage', '✅ ' + data.data.message, 'success');
            document.getElementById('newPixelName').value = '';
            document.getElementById('newPixelId').value = '';
            document.getElementById('newPixelToken').value = '';
            loadPixels();
        } else {
            showMessage('manageMessage', '❌ ' + data.error, 'error');
        }
    } catch (error) {
        showMessage('manageMessage', '❌ ' + error.message, 'error');
    }
}

async function deletePixel(pixelId) {
    if (!confirm(`Delete pixel ${pixelId}?`)) return;
    
    try {
        const response = await fetch('/api/admin-api.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pixel_id: pixelId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('manageMessage', '✅ Pixel deleted', 'success');
            loadPixels();
        } else {
            showMessage('manageMessage', '❌ ' + data.error, 'error');
        }
    } catch (error) {
        showMessage('manageMessage', '❌ ' + error.message, 'error');
    }
}

async function generateSecret() {
    try {
        const response = await fetch('/api/admin-api.php?action=generate_secret');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('apiSecret').value = data.data.secret;
            showMessage('secretMessage', '✅ New secret generated. Click Save to apply.', 'success');
        }
    } catch (error) {
        showMessage('secretMessage', '❌ ' + error.message, 'error');
    }
}

async function updateSecret() {
    const secret = document.getElementById('apiSecret').value;
    
    if (!secret) {
        showMessage('secretMessage', '❌ Secret cannot be empty', 'error');
        return;
    }
    
    try {
        const response = await fetch('/api/admin-api.php?action=update_secret', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ secret: secret })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('secretMessage', '✅ API Secret updated', 'success');
            apiSecret = secret;
        } else {
            showMessage('secretMessage', '❌ ' + data.error, 'error');
        }
    } catch (error) {
        showMessage('secretMessage', '❌ ' + error.message, 'error');
    }
}

function showMessage(elementId, text, type) {
    const el = document.getElementById(elementId);
    el.innerHTML = `<div class="message ${type}">${text}</div>`;
    setTimeout(() => el.innerHTML = '', 5000);
}

async function toggleLogging() {
    const enabled = document.getElementById('loggingToggle').checked;
    
    try {
        const response = await fetch('/api/admin-api.php?action=toggle_logging', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enabled: enabled })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('loggingMessage', '✅ ' + data.data.message, 'success');
            updateLoggingStatus(enabled);
        } else {
            showMessage('loggingMessage', '❌ ' + data.error, 'error');
            document.getElementById('loggingToggle').checked = !enabled;
        }
    } catch (error) {
        showMessage('loggingMessage', '❌ ' + error.message, 'error');
        document.getElementById('loggingToggle').checked = !enabled;
    }
}

function updateLoggingStatus(enabled) {
    const status = document.getElementById('loggingStatus');
    status.textContent = enabled ? 'Enabled' : 'Disabled';
    status.style.color = enabled ? '#10b981' : '#64748b';
}

loadPixels();

