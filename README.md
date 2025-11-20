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

**GET** `/api/webhook.php`

**Параметры:**

| Параметр | Обязательный | Описание |
|----------|-------------|----------|
| `apikey` | ✅ Да | API Secret |
| `pixel_id` | ✅ Да | ID пикселя |
| `token` | Нет | Access Token пикселя (если не передан, берется из config.php) |
| `event_type` | Нет | `purchase` или `lead` (по умолчанию `purchase`) |
| `email` | Нет | Email пользователя |
| `phone` | Нет | Телефон |
| `first_name` | Нет | Имя |
| `last_name` | Нет | Фамилия |
| `value` | Нет | Сумма покупки |
| `currency` | Нет | Валюта (по умолчанию USD) |
| `event_source_url` | Нет | URL источника |
| `fbc` | Нет | Facebook Click ID |
| `fbp` | Нет | Facebook Browser ID |

**Пример запроса:**

```
https://facebook.sasypua.com/api/webhook.php?apikey=YOUR_API_SECRET&pixel_id=1572575650860967&token=EAAWBzv...&event_type=lead&email=user@example.com
```

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

## Примеры использования

### JavaScript

```javascript
const params = new URLSearchParams({
  apikey: 'YOUR_API_SECRET',
  pixel_id: '1572575650860967',
  token: 'EAAWBzv...',
  event_type: 'lead',
  email: 'user@example.com'
});

fetch(`https://facebook.sasypua.com/api/webhook.php?${params}`)
  .then(res => res.json())
  .then(data => console.log(data));
```

### PHP

```php
$url = 'https://facebook.sasypua.com/api/webhook.php?' . http_build_query([
    'apikey' => 'YOUR_API_SECRET',
    'pixel_id' => '1572575650860967',
    'token' => 'EAAWBzv...',
    'event_type' => 'purchase',
    'email' => 'user@example.com',
    'value' => 99.99
]);

$result = file_get_contents($url);
echo $result;
```

### cURL

```bash
curl "https://facebook.sasypua.com/api/webhook.php?apikey=YOUR_API_SECRET&pixel_id=1572575650860967&token=EAAWBzv...&event_type=lead&email=test@example.com"
```
