-- ========== USERS ==========
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========== CONTENT ==========
CREATE TABLE IF NOT EXISTS content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    tags VARCHAR(255),                 -- CSV или JSON со списком тегов
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========== INTERACTIONS ==========
CREATE TABLE IF NOT EXISTS interactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    content_id INT NOT NULL,
    type ENUM('view','like','rating') NOT NULL DEFAULT 'view',
    rating TINYINT NULL,
    weight FLOAT NOT NULL DEFAULT 1.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX (user_id),
    INDEX (content_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
);

-- ========== RECOMMENDATION CACHE ==========
CREATE TABLE IF NOT EXISTS recommendations_cache (
    user_id INT NOT NULL,
    content_ids TEXT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);