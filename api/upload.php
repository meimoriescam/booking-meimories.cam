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
    jsonResponse(['error' => 'Gagal mengupload file: kode error ' . $file['error']], 400);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'heic'];

if (!in_array($ext, $allowed)) {
    jsonResponse(['error' => 'Format file tidak didukung. Harap upload gambar (JPG, PNG, WEBP) atau PDF.'], 400);
}

$uploadDir = __DIR__ . '/../uploads/payment-proofs/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$newFilename = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $uploadDir . $newFilename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $publicUrl = '/uploads/payment-proofs/' . $newFilename;
    jsonResponse([
        'success' => true,
        'publicUrl' => $publicUrl,
        'fileName' => $file['name']
    ]);
} else {
    jsonResponse(['error' => 'Gagal memindahkan file yang diunggah ke server.'], 500);
}
