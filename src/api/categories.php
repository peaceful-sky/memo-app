<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../db/db.php';

header('Content-Type: application/json; charset=utf-8');
startSession();
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$userId = currentUserId();

// GET /api/categories.php - 목록 조회
if ($method === 'GET') {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.color, c.created_at,
               COUNT(m.id) AS memo_count
        FROM categories c
        LEFT JOIN memos m ON m.category_id = c.id AND m.user_id = c.user_id
        WHERE c.user_id = ?
        GROUP BY c.id
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$userId]);
    jsonResponse(['success' => true, 'categories' => $stmt->fetchAll()]);
}

// POST /api/categories.php - 생성
if ($method === 'POST') {
    $data  = getJsonBody();
    $name  = trim($data['name'] ?? '');
    $color = $data['color'] ?? '#888888';

    if ($name === '') jsonResponse(['success' => false, 'error' => '카테고리 이름을 입력해주세요.'], 400);
    if (mb_strlen($name) > 30) jsonResponse(['success' => false, 'error' => '이름은 30자 이하로 입력해주세요.'], 400);

    $db = getDB();
    $check = $db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ?");
    $check->execute([$userId, $name]);
    if ($check->fetch()) jsonResponse(['success' => false, 'error' => '같은 이름의 카테고리가 이미 있습니다.'], 409);

    $stmt = $db->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $name, $color]);
    $id = (int) $db->lastInsertId();

    $stmt = $db->prepare("SELECT id, name, color, created_at, 0 AS memo_count FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true, 'category' => $stmt->fetch()], 201);
}

// PUT /api/categories.php?id=X - 수정
if ($method === 'PUT' && isset($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $data = getJsonBody();

    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) jsonResponse(['success' => false, 'error' => 'Not found'], 404);

    $fields = []; $params = [];
    if (array_key_exists('name', $data)) {
        $name = trim($data['name']);
        if ($name === '') jsonResponse(['success' => false, 'error' => '이름을 입력해주세요.'], 400);
        $check = $db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND id != ?");
        $check->execute([$userId, $name, $id]);
        if ($check->fetch()) jsonResponse(['success' => false, 'error' => '같은 이름의 카테고리가 이미 있습니다.'], 409);
        $fields[] = 'name=?'; $params[] = $name;
    }
    if (array_key_exists('color', $data)) { $fields[] = 'color=?'; $params[] = $data['color']; }
    if (empty($fields)) jsonResponse(['success' => false, 'error' => 'Nothing to update'], 400);

    $params[] = $id;
    $db->prepare("UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

    $stmt = $db->prepare("
        SELECT c.id, c.name, c.color, c.created_at, COUNT(m.id) AS memo_count
        FROM categories c
        LEFT JOIN memos m ON m.category_id = c.id AND m.user_id = c.user_id
        WHERE c.id = ? GROUP BY c.id
    ");
    $stmt->execute([$id]);
    jsonResponse(['success' => true, 'category' => $stmt->fetch()]);
}

// DELETE /api/categories.php?id=X - 삭제
if ($method === 'DELETE' && isset($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $db   = getDB();
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if ($stmt->rowCount() === 0) jsonResponse(['success' => false, 'error' => 'Not found'], 404);
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'Invalid request'], 400);
