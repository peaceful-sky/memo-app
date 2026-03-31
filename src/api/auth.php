<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../db/db.php';

header('Content-Type: application/json; charset=utf-8');
startSession();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'register') {
    $data = getJsonBody();
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$username || !$email || !$password) {
        jsonResponse(['success' => false, 'error' => '모든 필드를 입력해주세요.'], 400);
    }
    if (strlen($username) < 2 || strlen($username) > 20) {
        jsonResponse(['success' => false, 'error' => '닉네임은 2~20자여야 합니다.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'error' => '이메일 형식이 올바르지 않습니다.'], 400);
    }
    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'error' => '비밀번호는 6자 이상이어야 합니다.'], 400);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'error' => '이미 사용 중인 닉네임 또는 이메일입니다.'], 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $userId = (int) $db->lastInsertId();

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;

        jsonResponse(['success' => true, 'username' => $username]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => '서버 오류가 발생했습니다.'], 500);
    }
}

if ($method === 'POST' && $action === 'login') {
    $data = getJsonBody();
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'error' => '이메일과 비밀번호를 입력해주세요.'], 400);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['success' => false, 'error' => '이메일 또는 비밀번호가 틀렸습니다.'], 401);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        jsonResponse(['success' => true, 'username' => $user['username']]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => '서버 오류가 발생했습니다.'], 500);
    }
}

if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true]);
}

if ($method === 'GET' && $action === 'me') {
    if (isLoggedIn()) {
        jsonResponse(['success' => true, 'user_id' => currentUserId(), 'username' => $_SESSION['username']]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Not logged in'], 401);
    }
}

jsonResponse(['success' => false, 'error' => 'Invalid request'], 400);
