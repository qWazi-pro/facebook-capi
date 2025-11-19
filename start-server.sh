#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║     Facebook Pixel API - Local Development Server          ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

PORT=8000

while lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1 ; do
    echo "⚠️  Port $PORT is busy, trying $((PORT+1))..."
    PORT=$((PORT+1))
done

echo "🚀 Starting PHP development server on port $PORT..."
echo ""
echo "  🌐 Admin Panel: http://localhost:$PORT/admin/"
echo "  📡 API Endpoint: http://localhost:$PORT/api/webhook.php"
echo ""
echo "  🔐 Admin Login:"
echo "      Username: admin"
echo "      Password: admin123"
echo ""
echo "  💡 Change password in: admin/index.php"
echo ""
echo "Press Ctrl+C to stop the server"
echo "═══════════════════════════════════════════════════════════════"
echo ""

php -S localhost:$PORT

