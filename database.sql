-- ============================================================
-- AI Notes Generator - Database Schema
-- Engine: MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ai-notes-generator`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ai-notes-generator`;

-- ============================================================
-- TABLE: users
-- Stores registered user accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)       NOT NULL UNIQUE,
    email       VARCHAR(150)      NOT NULL UNIQUE,
    password    VARCHAR(255)      NOT NULL,          -- bcrypt hash
    avatar_url  VARCHAR(500)      NULL DEFAULT NULL,
    is_admin    TINYINT(1)        NOT NULL DEFAULT 0,
    status      ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: password_resets
-- Stores secure tokens for password recovery
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(150)      NOT NULL,
    `token`      VARCHAR(64)       NOT NULL,
    `expires_at` DATETIME          NOT NULL,
    `created_at` DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    INDEX idx_password_resets_email (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: categories
-- User-defined note categories / folders
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED      NOT NULL,
    name        VARCHAR(100)      NOT NULL,
    color       VARCHAR(7)        NOT NULL DEFAULT '#6366f1',   -- hex colour
    created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_cat_user (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_cat_user (user_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: notes
-- Core notes table - stores AI-generated and manual notes
-- ============================================================
CREATE TABLE IF NOT EXISTS notes (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    category_id     INT UNSIGNED    NULL DEFAULT NULL,
    title           VARCHAR(255)    NOT NULL,
    raw_input       LONGTEXT        NOT NULL,         -- original user text
    ai_summary      LONGTEXT        NULL DEFAULT NULL, -- AI-generated summary
    ai_key_points   JSON            NULL DEFAULT NULL, -- bullet points array
    ai_tags         JSON            NULL DEFAULT NULL, -- suggested tags
    word_count      INT UNSIGNED    NOT NULL DEFAULT 0,
    is_pinned       TINYINT(1)      NOT NULL DEFAULT 0,
    is_archived     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_note_user (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY fk_note_cat (category_id)
        REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_note_user (user_id),
    INDEX idx_note_cat (category_id),
    INDEX idx_note_pinned (is_pinned),
    INDEX idx_note_archived (is_archived),
    FULLTEXT INDEX ft_notes (title, raw_input, ai_summary)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: ai_requests
-- Audit / usage log for every Gemini API call
-- ============================================================
CREATE TABLE IF NOT EXISTS ai_requests (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    note_id         INT UNSIGNED    NULL DEFAULT NULL,
    model           VARCHAR(50)     NOT NULL DEFAULT 'gemini-flash-latest',
    prompt_tokens   INT UNSIGNED    NOT NULL DEFAULT 0,
    completion_tokens INT UNSIGNED  NOT NULL DEFAULT 0,
    total_tokens    INT UNSIGNED    NOT NULL DEFAULT 0,
    status          ENUM('success','error') NOT NULL DEFAULT 'success',
    error_message   TEXT            NULL DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_req_user (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY fk_req_note (note_id)
        REFERENCES notes(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_req_user (user_id),
    INDEX idx_req_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: sessions
-- Server-side session store (optional — used if not relying
-- on PHP default file sessions)
-- ============================================================
CREATE TABLE IF NOT EXISTS sessions (
    session_id  VARCHAR(128)    NOT NULL,
    user_id     INT UNSIGNED    NOT NULL,
    ip_address  VARCHAR(45)     NULL DEFAULT NULL,
    user_agent  VARCHAR(500)    NULL DEFAULT NULL,
    payload     TEXT            NULL DEFAULT NULL,
    last_active DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at  DATETIME        NOT NULL,
    PRIMARY KEY (session_id),
    FOREIGN KEY fk_sess_user (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_sess_user (user_id),
    INDEX idx_sess_expires (expires_at)
) ENGINE=InnoDB;

-- ============================================================
-- SEED: default categories for demo user
-- (Comment out if not needed in production)
-- ============================================================
-- INSERT INTO users (username, email, password)
-- VALUES ('demo', 'demo@example.com', '$2y$12$...');  -- run after hashing

-- ============================================================
-- VIEW: note_stats  — per-user summary statistics
-- ============================================================
CREATE OR REPLACE VIEW note_stats AS
SELECT
    u.id            AS user_id,
    u.username,
    COUNT(n.id)             AS total_notes,
    SUM(n.is_pinned)        AS pinned_notes,
    SUM(n.is_archived)      AS archived_notes,
    SUM(n.word_count)       AS total_words,
    COUNT(r.id)             AS total_ai_requests,
    SUM(r.total_tokens)     AS total_tokens_used
FROM users u
LEFT JOIN notes n ON n.user_id = u.id
LEFT JOIN ai_requests r ON r.user_id = u.id
GROUP BY u.id, u.username;
