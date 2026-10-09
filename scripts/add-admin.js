// scripts/add-admin.js
// Tool developer untuk membuat akun admin baru langsung dari terminal lokal
// Penggunaan: npm run add-admin <email> <password>

import * as ftp from 'basic-ftp';
import { Readable } from 'stream';
import dotenv from 'dotenv';
dotenv.config();

const email = process.argv[2];
const password = process.argv[3];

if (!email || !password) {
  console.log('=====================================================');
  console.log('🔑 Meimories.cam — Developer Admin Creation Tool');
  console.log('=====================================================');
  console.log('Cara Penggunaan:');
  console.log('  npm run add-admin <email> <password>\n');
  console.log('Contoh:');
  console.log('  npm run add-admin dev@meimories.cam PasswordSuperKuat123!\n');
  console.log('=====================================================');
  process.exit(1);
}

if (!email.includes('@') || !email.includes('.')) {
  console.error('❌ Error: Format email tidak valid.');
  process.exit(1);
}

if (password.length < 8) {
  console.error('❌ Error: Password minimal harus 8 karakter.');
  process.exit(1);
}

async function createAdmin() {
  console.log(`⏳ Sedang mendaftarkan user admin [${email}] ke database server...`);

  const oneTimeToken = Math.random().toString(36).slice(2) + Date.now().toString(36);
  const tempFileName = `_create_admin_${oneTimeToken}.php`;

  // PHP code to execute on the server
  const phpCode = `<?php
header('Content-Type: application/json');
if (($_GET['token'] ?? '') !== '${oneTimeToken}') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

require_once __DIR__ . '/api/config.php';

try {
    $pdo = getDB();
    $email = base64_decode('${Buffer.from(email).toString('base64')}');
    $password = base64_decode('${Buffer.from(password).toString('base64')}');

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

    echo json_encode(['success' => true, 'email' => $email]);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
`;

  const client = new ftp.Client();
  try {
    await client.access({
      host: process.env.FTP_HOST,
      port: parseInt(process.env.FTP_PORT || '2614', 10),
      user: process.env.FTP_USER,
      password: process.env.FTP_PASS,
      secure: false
    });

    await client.uploadFrom(Readable.from([phpCode]), tempFileName);
  } catch (err) {
    console.error('❌ Gagal terhubung ke FTP server:', err.message);
    process.exit(1);
  } finally {
    client.close();
  }

  // Trigger remote execution
  try {
    const appUrl = process.env.APP_URL || 'https://meimoriescam.zedevio.com';
    const res = await fetch(`${appUrl}/${tempFileName}?token=${oneTimeToken}`);
    const data = await res.json();

    if (data.success) {
      console.log('🎉 =====================================================');
      console.log(`✅ BERHASIL! Akun admin baru [${data.email}] telah aktif.`);
      console.log('🌐 Anda sekarang bisa langsung login di website:');
      console.log(`   ${appUrl} -> Menu "Jadwal & Pengingat"`);
      console.log('🎉 =====================================================');
    } else {
      console.error('❌ Gagal membuat admin:', data.error);
    }
  } catch (err) {
    console.error('❌ Error saat memproses request:', err.message);
  }

  // Cleanup temporary file from server
  const clientClean = new ftp.Client();
  try {
    await clientClean.access({
      host: process.env.FTP_HOST,
      port: parseInt(process.env.FTP_PORT || '2614', 10),
      user: process.env.FTP_USER,
      password: process.env.FTP_PASS,
      secure: false
    });
    await clientClean.remove(tempFileName);
  } catch (e) {
    // ignore
  } finally {
    clientClean.close();
  }
}

createAdmin();
