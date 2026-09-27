# SportShop — серверная часть на PHP (MVC) с JSON API

Это **вариация "с API"**: PHP-сервер (MVC, PDO + MySQL/OpenServer) предоставляет
только REST/JSON-эндпоинты (`/api/...`), а клиент — статические HTML-страницы
из `public/` (api.js + style.css), которые обращаются к API через `fetch`.

## Отличие от server_php (вариация без API)
| | server_php (без API) | server_php_api (с API) |
|---|---|---|
| Страницы | Рендерятся на сервере (views/*.php) | Статические HTML+JS из public/ |
| Данные | Контроллеры отдают HTML | Контроллеры отдают только JSON |
| JS-логика | Встроена в страницы | `public/api.js` ходит в API |

## Запуск (OpenServer)
1. MySQL: создайте БД и импортируйте `database/schema.sql` через phpMyAdmin
   (или `php database/init.php`).
2. Укажите домен OpenServer на папку `public/` этого проекта.
3. Клиент (public/api.js) обращается к этому же домену —
   CORS уже включён в `public/index.php`.

Эндпоинты: GET /api/products, GET /api/products/{id}, POST /api/admin/login,
POST /api/admin/logout, POST /api/admin/products, PUT /api/admin/products/{id},
DELETE /api/admin/products/{id}.
