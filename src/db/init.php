<?php
$dbPath = '/var/db/memo.db';

try {
    $isNew = !file_exists($dbPath);
    $pdo   = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("PRAGMA journal_mode=WAL;");
    $pdo->exec("PRAGMA foreign_keys=ON;");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT    NOT NULL UNIQUE,
            email      TEXT    NOT NULL UNIQUE,
            password   TEXT    NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL,
            name       TEXT    NOT NULL,
            color      TEXT    NOT NULL DEFAULT '#888888',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS memos (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id     INTEGER NOT NULL,
            category_id INTEGER DEFAULT NULL,
            title       TEXT    NOT NULL DEFAULT 'Untitled',
            content     TEXT    NOT NULL DEFAULT '',
            color       TEXT    NOT NULL DEFAULT '#1e1e1e',
            is_pinned   INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        );
    ");

    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS memos_updated_at
        AFTER UPDATE ON memos
        BEGIN
            UPDATE memos SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
        END;
    ");

    $cols     = $pdo->query("PRAGMA table_info(memos)")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'name');
    if (!in_array('category_id', $colNames)) {
        $pdo->exec("ALTER TABLE memos ADD COLUMN category_id INTEGER DEFAULT NULL REFERENCES categories(id) ON DELETE SET NULL;");
        echo "Migration: category_id column added.\n";
    }

    if ($isNew) {
        chmod($dbPath, 0664);
        chown($dbPath, 'nginx');
    }

    echo "Database initialized successfully.\n";
} catch (Exception $e) {
    echo "DB init error: " . $e->getMessage() . "\n";
    exit(1);
}
