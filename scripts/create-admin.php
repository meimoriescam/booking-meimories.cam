<?php
// scripts/create-admin.php
// Script CLI aman untuk membuat atau mereset password akun admin tanpa hardcode di version control.
// Penggunaan: php scripts/create-admin.php <email> <password>

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Forbidden: Script ini hanya dapat dijalankan melalui terminal CLI.');
}

require_once __DIR__ . '/../api/config.php';

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$email || !$password) {
    echo "=====================================================\n";
    echo "🔑 Meimories.cam — Admin Provisioning Tool (CLI)\n";
    echo "=====================================================\n";
    echo "Penggunaan:\n";
    echo "  php scripts/create-admin.php <email> <password>\n\n";
    echo "Contoh:\n";
    echo "  php scripts/create-admin.php admin@meimories.cam RahasiaKuat2026!\n";
    echo "=====================================================\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Error: Format email tidak valid.\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "❌ Error: Password minimal harus 8 karakter.\n";
    exit(1);
}

try {
    $pdo = getDB();
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare("
        INSERT INTO admin_users (email, password_hash, created_at)
        VALUES (:email, :hash, NOW())
        ON DUPLICATE KEY UPDATE password_hash = :hash_update
    ");

    $stmt->execute([
        ':email' => $email,
        ':hash' => $hash,
        ':hash_update' => $hash
    ]);

    echo "✅ Akun admin [{$email}] berhasil dibuat/diperbarui dengan aman.\n";
} catch (Throwable $e) {
    echo "❌ Gagal membuat akun admin: " . $e->getMessage() . "\n";
    exit(1);
}
