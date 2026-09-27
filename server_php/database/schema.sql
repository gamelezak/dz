
CREATE DATABASE IF NOT EXISTS sportshop
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sportshop;

CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)        NOT NULL,
    price       DECIMAL(10, 2)      NOT NULL,
    stock       INT                 NOT NULL DEFAULT 0,
    description TEXT                NULL,
    image       VARCHAR(255)        NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_name (name)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(64)  NOT NULL UNIQUE,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user','manager','admin') NOT NULL DEFAULT 'user',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    username   VARCHAR(64)  NOT NULL,
    phone      VARCHAR(32)  NOT NULL,
    address    VARCHAR(255) NOT NULL,
    comment    VARCHAR(500) NULL,
    total      DECIMAL(10, 2) NOT NULL,
    status     ENUM('new','confirmed','done','canceled') NOT NULL DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT          NOT NULL,
    product_id INT          NOT NULL,
    name       VARCHAR(255) NOT NULL,
    price      DECIMAL(10, 2) NOT NULL,
    qty        INT          NOT NULL,
    CONSTRAINT fk_order_items_order   FOREIGN KEY (order_id)   REFERENCES orders (id)   ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    INDEX idx_order_items_oid (order_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT          NOT NULL,
    filename   VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    CONSTRAINT fk_product_images_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    INDEX idx_product_images_pid (product_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_sessions (
    token      CHAR(64) PRIMARY KEY,
    username   VARCHAR(64) NOT NULL,
    expires_at DATETIME    NOT NULL,
    INDEX idx_admin_sessions_exp (expires_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO products (name, price, stock, description, image) VALUES
('Гантели 10 кг (пара)', 1990.00, 25, 'Виниловые гантели для домашних тренировок.', 'ddcfe18949b6d8e1.png'),
('Футбольный мяч',       1799.00, 40, 'Мяч размер 5, машинная сшивка.',            'bc695d10f188797c.png'),
('Коврик для йоги',       990.00, 15, 'Нескользящий TPE-коврик 183x61 см.',       NULL);

INSERT INTO product_images (product_id, filename, sort_order) VALUES
(1, '7adbbe240d93a7e2.png', 1);
