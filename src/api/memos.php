<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../db/db.php';

header('Content-Type: application/json; charset=utf-8');
startSession();
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$userId = currentUserId();

// GET /api/memos.php - list all memos (category_id 필터 지원)
if ($method === 'GET' && !isset($_GET['id'])) {
    $db = getDB();

    if (isset($_GET['category_id'])) {
        $catId = (int) $_GET['category_id'];
        if ($catId === 0) {
            // 미분류
            $stmt = $db->prepare("
                SELECT id, title, content, color, is_pinned, category_id, created_at, updated_at
                FROM memos WHERE user_id = ? AND category_id IS NULL
                ORDER BY is_pinned DESC, updated_at DESC
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $db->prepare("
                SELECT id, title, content, color, is_pinned, category_id, created_at, updated_at
                FROM memos WHERE user_id = ? AND category_id = ?
                ORDER BY is_pinned DESC, updated_at DESC
            ");
            $stmt->execute([$userId, $catId]);
        }
    } else {
        $stmt = $db->prepare("
            SELECT id, title, content, color, is_pinned, category_id, created_at, updated_at
            FROM memos WHERE user_id = ?
            ORDER BY is_pinned DESC, updated_at DESC
        ");
        $stmt->execute([$userId]);
    }

    $memos = $stmt->fetchAll();
    jsonResponse(['success' => true, 'memos' => $memos]);
}

// GET /api/memos.php?id=X - get single memo
if ($method === 'GET' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM memos WHERE id=? AND user_id=?");
    $stmt->execute([$id, $userId]);
    $memo = $stmt->fetch();
    if (!$memo) jsonResponse(['success' => false, 'error' => 'Not found'], 404);
    jsonResponse(['success' => true, 'memo' => $memo]);
}

// POST /api/memos.php - create
if ($method === 'POST') {
    $data = getJsonBody();
    $title      = trim($data['title'] ?? 'Untitled');
    $content    = $data['content'] ?? '';
    $color      = $data['color'] ?? '#1e1e1e';
    $categoryId = isset($data['category_id']) && $data['category_id'] !== '' ? (int) $data['category_id'] : null;

    if (strlen($title) === 0) $title = 'Untitled';

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO memos (user_id, title, content, color, category_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $content, $color, $categoryId]);
    $id = (int) $db->lastInsertId();

    $stmt = $db->prepare("SELECT * FROM memos WHERE id=?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true, 'memo' => $stmt->fetch()], 201);
}

// PUT /api/memos.php?id=X - update
if ($method === 'PUT' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $data = getJsonBody();

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM memos WHERE id=? AND user_id=?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) jsonResponse(['success' => false, 'error' => 'Not found'], 404);

    $fields = [];
    $params = [];
    if (array_key_exists('title', $data)) { $fields[] = 'title=?'; $params[] = trim($data['title']) ?: 'Untitled'; }
    if (array_key_exists('content', $data)) { $fields[] = 'content=?'; $params[] = $data['content']; }
    if (array_key_exists('color', $data)) { $fields[] = 'color=?'; $params[] = $data['color']; }
    if (array_key_exists('is_pinned', $data)) { $fields[] = 'is_pinned=?'; $params[] = (int) $data['is_pinned']; }
    if (array_key_exists('category_id', $data)) {
        $fields[] = 'category_id=?';
        $params[] = ($data['category_id'] === null || $data['category_id'] === '') ? null : (int) $data['category_id'];
    }

    if (empty($fields)) jsonResponse(['success' => false, 'error' => 'Nothing to update'], 400);

    $params[] = $id;
    $db->prepare("UPDATE memos SET " . implode(', ', $fields) . " WHERE id=?")->execute($params);

    $stmt = $db->prepare("SELECT * FROM memos WHERE id=?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true, 'memo' => $stmt->fetch()]);
}

// DELETE /api/memos.php?id=X
if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM memos WHERE id=? AND user_id=?");
    $stmt->execute([$id, $userId]);
    if ($stmt->rowCount() === 0) jsonResponse(['success' => false, 'error' => 'Not found'], 404);
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'Invalid request'], 400);
