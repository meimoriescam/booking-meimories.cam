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

// --- LOGIN ---
if ($method === 'POST' && ($action === 'login' || empty($action))) {
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Email dan password wajib diisi.'], 400);
    }

    $ip = getClientIP();

    try {
        // 1. Rate Limiting: Cek percobaan gagal dalam 15 menit terakhir
        $rateStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM login_attempts 
            WHERE ip_address = :ip AND attempted_at > (NOW() - INTERVAL 15 MINUTE)
        ");
        $rateStmt->execute([':ip' => $ip]);
        $failedAttempts = (int) $rateStmt->fetchColumn();

        if ($failedAttempts >= 5) {
            jsonResponse([
                'error' => 'Terlalu banyak percobaan login yang gagal. Akun dikunci sementara demi keamanan. Silakan coba lagi setelah 15 menit.'
            ], 429);
        }

        // 2. Verifikasi Kredensial
        $stmt = $pdo->prepare("SELECT id, email, password_hash FROM admin_users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Catat kegagalan login untuk rate limiting
            $logStmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempted_at) VALUES (:ip, NOW())");
            $logStmt->execute([':ip' => $ip]);

            jsonResponse(['error' => 'Email atau password salah.'], 401);
        }

        // Login berhasil: Bersihkan riwayat kegagalan IP ini
        $clearStmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
        $clearStmt->execute([':ip' => $ip]);

        // 3. Generate Token & Simpan Hash di Database (At-Rest Hashing)
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $sessionStmt = $pdo->prepare("
            INSERT INTO admin_sessions (user_id, token_hash, expires_at)
            VALUES (:user_id, :token_hash, :expires_at)
        ");
        $sessionStmt->execute([
            ':user_id' => $user['id'],
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt
        ]);

        jsonResponse([
            'success' => true,
            'token' => $rawToken,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email']
            ]
        ]);
    } catch (Throwable $e) {
        error_log('[Auth Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        jsonResponse(['error' => 'Terjadi kendala pada server saat autentikasi. Silakan coba beberapa saat lagi.'], 500);
    }
}

// --- SESSION CHECK ---
if ($method === 'GET' && ($action === 'session' || empty($action))) {
    $token = getBearerToken();
    if (!$token) {
        jsonResponse(['session' => null]);
    }

    try {
        $tokenHash = hash('sha256', $token);

        $stmt = $pdo->prepare("
            SELECT s.token_hash, s.expires_at, u.id as user_id, u.email 
            FROM admin_sessions s
            JOIN admin_users u ON s.user_id = u.id
            WHERE (s.token_hash = :token_hash OR s.token_hash = :raw_token) AND s.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':raw_token' => $token
        ]);
        $session = $stmt->fetch();

        if (!$session) {
            jsonResponse(['session' => null]);
        }

        jsonResponse([
            'session' => [
                'token' => $token,
                'user' => [
                    'id' => $session['user_id'],
                    'email' => $session['email']
                ],
                'expires_at' => $session['expires_at']
            ]
        ]);
    } catch (Throwable $e) {
        error_log('[Session Check Error] ' . $e->getMessage());
        jsonResponse(['session' => null]);
    }
}

// --- LOGOUT ---
if ($method === 'POST' && $action === 'logout') {
    $token = getBearerToken();
    if ($token) {
        try {
            $tokenHash = hash('sha256', $token);
            $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE token_hash = :token_hash OR token_hash = :raw_token");
            $stmt->execute([
                ':token_hash' => $tokenHash,
                ':raw_token' => $token
            ]);
        } catch (Throwable $e) {
            error_log('[Logout Error] ' . $e->getMessage());
        }
    }
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Endpoint tidak ditemukan.'], 404);
