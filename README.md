# Facebook Pixel API

Простой PHP API для отправки событий в Facebook Conversions API.

## Быстрый старт

### 1. Запуск локально

```bash
./start-server.sh
```

Откроется на http://localhost:8000

### 2. Админка

**URL:** http://localhost:8000/admin/

**Логин:** `admin` / **Пароль:** `admin123`

В админке можно:
- Управлять пикселями (добавить/удалить/именовать)
- Генерировать API Secret
- Включать/выключать логирование
- Тестировать отправку событий

**Логирование:** Все запросы логируются в `storage/logs/YYYY-MM-DD.log` (можно отключить)

## API Endpoint

**POST** `/api/webhook.php`

**Headers:**

```
Content-Type: application/json
Authorization: Bearer YOUR_API_SECRET
```

**Body:**

```json
{
  "pixel_id": "1572575650860967",
  "event_type": "purchase",
  "event_data": {
    "email": "user@example.com",
    "value": 99.99,
    "currency": "USD"
  }
}
```

**Event types:**
- `purchase` - покупка (по умолчанию)
- `lead` - лид

**Все параметры опциональны**, кроме `pixel_id`

## Структура

```
admin/
  └── index.php          # Админ-панель
api/
  ├── webhook.php        # Публичный API
  └── admin-api.php      # Админ API
config.php              # Все настройки здесь
```

## Настройка

### Через админку (рекомендуется)

Откройте админку и управляйте пикселями и API Secret

### Вручную в config.php

```php
return [
    'pixels' => [
        '1572575650860967' => 'EAAxxxxx...',
    ],
  
    'api_secret' => 'ваш_api_secret',
  
    'admin_users' => [
        'admin' => 'admin123',
    ]
];
```

## Production

1. Смените пароль админки в `config.php`
2. Сгенерируйте API_SECRET через админку
3. Настройте веб-сервер (Apache/Nginx)
4. Включите HTTPS

## Пример использования

```javascript
fetch('https://yourdomain.com/api/webhook.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_API_SECRET'
  },
  body: JSON.stringify({
    pixel_id: '1572575650860967',
    event_type: 'purchase',
    event_data: {
      email: 'user@example.com',
      value: 99.99,
      currency: 'USD'
    }
  })
});
```
