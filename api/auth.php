<?php
// api/auth.php
require_once __DIR__ . '/config.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Parse input
$input = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $input = json_decode($raw, true) ?: [];
    }
    if (empty($input)) {
        $input = $_POST;
    }
}

$action = $_GET['action'] ?? $input['action'] ?? '';

if ($method === 'POST' && ($action === 'login' || empty($action))) {
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Email dan password wajib diisi.'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM admin_users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['error' => 'Email atau password salah.'], 401);
    }

    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt = $pdo->prepare("
        INSERT INTO admin_sessions (user_id, token, expires_at)
        VALUES (:user_id, :token, :expires_at)
    ");
    $stmt->execute([
        ':user_id' => $user['id'],
        ':token' => $token,
        ':expires_at' => $expiresAt
    ]);

    jsonResponse([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email']
        ]
    ]);
}

if ($method === 'GET' && ($action === 'session' || empty($action))) {
    $token = getBearerToken();
    if (!$token) {
        jsonResponse(['session' => null]);
    }

    $stmt = $pdo->prepare("
        SELECT s.token, s.expires_at, u.id as user_id, u.email 
        FROM admin_sessions s
        JOIN admin_users u ON s.user_id = u.id
        WHERE s.token = :token AND s.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $session = $stmt->fetch();

    if (!$session) {
        jsonResponse(['session' => null]);
    }

    jsonResponse([
        'session' => [
            'token' => $session['token'],
            'user' => [
                'id' => $session['user_id'],
                'email' => $session['email']
            ],
            'expires_at' => $session['expires_at']
        ]
    ]);
}

if ($method === 'POST' && $action === 'logout') {
    $token = getBearerToken();
    if ($token) {
        $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE token = :token");
        $stmt->execute([':token' => $token]);
    }
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Endpoint tidak ditemukan.'], 404);
