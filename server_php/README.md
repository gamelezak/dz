# SportShop — серверная часть на PHP (MVC) БЕЗ API (классический рендеринг)

Это **вариация "без API"**: весь сайт работает целиком на PHP.
Страницы (каталог, товар, корзина, админка) рендерятся сервером из шаблонов
`views/*.php` (часть "V" паттерна MVC). Данные берутся напрямую из MySQL
через модели (PDO), без промежуточного JSON-API. Формы админки отправляют
`POST`-запросы на этот же сервер (multipart/form-data), а не через fetch.

## Структура (MVC)
- `core/`     — ядро: App, Router, Request, Response, Database (PDO), Controller, View;
- `models/`   — M: ProductModel, AdminSessionModel (работа с таблицами через PDO);
- `controllers/` — C: CatalogController, CartController, ProductController, AdminController;
- `views/`    — V: шаблоны страниц (catalog, product, cart, admin);
- `config/config.php` — настройки MySQL (OpenServer/phpMyAdmin);
- `database/schema.sql` — схема БД sportshop (импортируется через phpMyAdmin);
- `public/images/products/` — ★ папка для картинок товаров.

## Запуск (OpenServer + phpMyAdmin)
1. Запустите OpenServer (Apache + MySQL).
2. Откройте phpMyAdmin → создайте БД `sportshop` → импортируйте `database/schema.sql`.
3. Скопируйте проект в домен OpenServer (точка входа — `public/index.php`,
   включите mod_rewrite или используйте `php -S localhost:8000 -t public public/router.php`).
4. Магазин: http://localhost:8000/ · Админка: http://localhost:8000/admin?action=login
   Логин: admin / admin123

Внутренние AJAX-вызовы корзины (`?action=cart:add`) идут на тот же PHP-сервер
и не являются публичным REST API.
