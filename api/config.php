<?php
// api/config.php
error_reporting(E_ALL);
ini_set('display_errors', '0');

// CORS Headers - Whitelist Allowed Origins
$allowedOrigins = [
    'https://meimories.cam',
    'https://www.meimories.cam',
    'https://booking.meimories.cam',
    'https://meimoriescam.zedevio.com',
    'http://localhost:5173',
    'http://localhost:3000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Credentials
function loadEnvFile($path) {
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $vars = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $vars[trim($name)] = trim($value);
        }
    }
    return $vars;
}

$env = loadEnvFile(__DIR__ . '/../.env');

define('DB_HOST', getenv('DB_HOST') ?: ($env['DB_HOST'] ?? ''));
define('DB_PORT', getenv('DB_PORT') ?: ($env['DB_PORT'] ?? '3306'));
define('DB_NAME', getenv('DB_NAME') ?: ($env['DB_NAME'] ?? ''));
define('DB_USER', getenv('DB_USER') ?: ($env['DB_USER'] ?? ''));
define('DB_PASS', getenv('DB_PASS') ?: ($env['DB_PASS'] ?? ''));

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        if (!DB_HOST || !DB_NAME || !DB_USER) {
            error_log('[DB Config Error] Konfigurasi database belum lengkap di .env.');
            jsonResponse(['error' => 'Terjadi kesalahan konfigurasi server.'], 500);
        }
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

function getClientIP() {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (strpos($ip, ',') !== false) {
        $parts = explode(',', $ip);
        $ip = trim($parts[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function getBearerToken() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } else if (function_exists('apache_request_headers')) {
        $reqHeaders = apache_request_headers();
        if (isset($reqHeaders['Authorization'])) {
            $headers = trim($reqHeaders['Authorization']);
        }
    }
    if ($headers && preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
        return $matches[1];
    }
    return null;
}

function requireAuth($pdo) {
    $token = getBearerToken();
    if (!$token) {
        jsonResponse(['error' => 'Akses ditolak: Token tidak ditemukan. Silakan login.'], 401);
    }

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
        jsonResponse(['error' => 'Sesi telah kedaluwarsa atau tidak valid. Silakan login kembali.'], 401);
    }

    return $session;
}
