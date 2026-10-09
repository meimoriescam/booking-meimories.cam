<?php
// api/upload.php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$fileKey = isset($_FILES['file']) ? 'file' : (isset($_FILES['payment_proof']) ? 'payment_proof' : null);

if (!$fileKey || empty($_FILES[$fileKey]['name'])) {
    jsonResponse(['error' => 'File tidak ditemukan.'], 400);
}

$file = $_FILES[$fileKey];

if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'Gagal mengupload file: error kode ' . $file['error']], 400);
}

// 1. Validasi Ukuran File (Maksimal 5 MB)
$maxFileSize = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxFileSize) {
    jsonResponse(['error' => 'Ukuran file terlalu besar (maksimal 5 MB).'], 400);
}

// 2. Validasi Ekstensi File
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedMimes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'pdf'  => 'application/pdf',
];

if (!array_key_exists($ext, $allowedMimes)) {
    jsonResponse(['error' => 'Format file tidak didukung. Harap upload gambar (JPG, PNG, WEBP) atau PDF.'], 400);
}

// 3. Validasi Magic Bytes / Real MIME Type via finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$realMime = $finfo->file($file['tmp_name']);

if ($realMime !== $allowedMimes[$ext]) {
    jsonResponse(['error' => 'Tipe file tidak valid atau rusak.'], 400);
}

// 4. Pastikan Direktori Upload Aman (0755)
$uploadDir = __DIR__ . '/../uploads/payment-proofs/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 5. Nama File Acak Kriptografis Aman
$newFilename = 'proof_' . bin2hex(random_bytes(16)) . '.' . $ext;
$targetPath = $uploadDir . $newFilename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $publicUrl = '/uploads/payment-proofs/' . $newFilename;
    jsonResponse([
        'success' => true,
        'publicUrl' => $publicUrl,
        'fileName' => htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8')
    ]);
} else {
    error_log('[Upload Error] Failed to move uploaded file to ' . $targetPath);
    jsonResponse(['error' => 'Terjadi kendala pada server saat menyimpan file.'], 500);
}
