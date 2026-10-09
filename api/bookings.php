<?php
// api/bookings.php
require_once __DIR__ . '/config.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: List all bookings (Admin only)
if ($method === 'GET') {
    requireAuth($pdo);
    try {
        $stmt = $pdo->query("SELECT * FROM bookings ORDER BY date DESC, time DESC");
        $bookings = $stmt->fetchAll();

        foreach ($bookings as &$b) {
            if (!empty($b['addons'])) {
                $decoded = json_decode($b['addons'], true);
                if (is_array($decoded)) {
                    $b['addons'] = $decoded;
                }
            } else {
                $b['addons'] = [];
            }
        }

        jsonResponse(['data' => $bookings]);
    } catch (Throwable $e) {
        error_log('[Bookings List Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        jsonResponse(['error' => 'Terjadi kendala pada server saat memuat data booking.'], 500);
    }
}

// Handle POST: Create booking (Public)
if ($method === 'POST') {
    $input = [];
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $input = json_decode($raw, true) ?: [];
    }
    if (empty($input)) {
        $input = $_POST;
    }

    $ip = getClientIP();

    try {
        // 1. Anti-Spam Rate Limiting: Maksimal 5 booking per 5 menit per IP
        $rateStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM booking_rate_limits 
            WHERE ip_address = :ip AND created_at > (NOW() - INTERVAL 5 MINUTE)
        ");
        $rateStmt->execute([':ip' => $ip]);
        if ((int)$rateStmt->fetchColumn() >= 5) {
            jsonResponse([
                'error' => 'Terlalu banyak permintaan booking. Harap tunggu beberapa saat sebelum mencoba kembali.'
            ], 429);
        }

        $date = trim($input['date'] ?? '');
        $time = trim($input['time'] ?? '');
        $name = trim($input['name'] ?? '');
        $wa = trim($input['wa'] ?? '');

        if (empty($date) || empty($time) || empty($name)) {
            jsonResponse(['error' => 'Tanggal, jam, dan nama pemesan wajib diisi.'], 400);
        }

        // 2. ID Booking Sepenuhnya Di-generate oleh Server (Server Authority)
        $id = 'bkg_' . bin2hex(random_bytes(16));

        $category = $input['category'] ?? '';
        $packageName = $input['package_name'] ?? '';
        $addons = isset($input['addons']) ? (is_array($input['addons']) ? json_encode($input['addons']) : $input['addons']) : '[]';
        $total = floatval($input['total'] ?? 0);
        $paymentType = $input['payment_type'] ?? 'dp';
        $amountToPay = floatval($input['amount_to_pay'] ?? 0);
        $sisaBayar = floatval($input['sisa_bayar'] ?? 0);
        $lokasi = $input['lokasi'] ?? '';
        $notes = $input['notes'] ?? '';
        $paymentProofUrl = $input['payment_proof_url'] ?? null;
        $paymentProofName = $input['payment_proof_name'] ?? null;

        // 3. Handle file upload jika dikirim langsung via multipart form-data
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['payment_proof'];
            $maxFileSize = 5 * 1024 * 1024; // 5 MB

            if ($file['size'] > $maxFileSize) {
                jsonResponse(['error' => 'Ukuran file bukti pembayaran terlalu besar (maksimal 5 MB).'], 400);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedMimes = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'webp' => 'image/webp',
                'pdf'  => 'application/pdf',
            ];

            if (!array_key_exists($ext, $allowedMimes)) {
                jsonResponse(['error' => 'Format file bukti transfer tidak didukung.'], 400);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($finfo->file($file['tmp_name']) !== $allowedMimes[$ext]) {
                jsonResponse(['error' => 'Tipe file bukti transfer tidak valid atau rusak.'], 400);
            }

            $uploadDir = __DIR__ . '/../uploads/payment-proofs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFilename = 'proof_' . bin2hex(random_bytes(16)) . '.' . $ext;
            $targetPath = $uploadDir . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $paymentProofUrl = '/uploads/payment-proofs/' . $newFilename;
                $paymentProofName = htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8');
            } else {
                error_log('[Upload Error] Failed to move file in bookings.php to ' . $targetPath);
                jsonResponse(['error' => 'Gagal menyimpan file bukti transfer di server.'], 500);
            }
        }

        // 4. Simpan ke database
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                id, date, time, category, package_name, addons, total,
                payment_type, amount_to_pay, sisa_bayar, name, wa, lokasi,
                notes, payment_proof_url, payment_proof_name, created_at
            ) VALUES (
                :id, :date, :time, :category, :package_name, :addons, :total,
                :payment_type, :amount_to_pay, :sisa_bayar, :name, :wa, :lokasi,
                :notes, :payment_proof_url, :payment_proof_name, NOW()
            )
        ");

        $stmt->execute([
            ':id' => $id,
            ':date' => $date,
            ':time' => $time,
            ':category' => $category,
            ':package_name' => $packageName,
            ':addons' => $addons,
            ':total' => $total,
            ':payment_type' => $paymentType,
            ':amount_to_pay' => $amountToPay,
            ':sisa_bayar' => $sisaBayar,
            ':name' => $name,
            ':wa' => $wa,
            ':lokasi' => $lokasi,
            ':notes' => $notes,
            ':payment_proof_url' => $paymentProofUrl,
            ':payment_proof_name' => $paymentProofName,
        ]);

        // Catat IP ke rate limit log
        $pdo->prepare("INSERT INTO booking_rate_limits (ip_address, created_at) VALUES (:ip, NOW())")
            ->execute([':ip' => $ip]);

        jsonResponse([
            'success' => true,
            'data' => [
                'id' => $id,
                'payment_proof_url' => $paymentProofUrl,
            ]
        ], 201);
    } catch (Throwable $e) {
        error_log('[Create Booking Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        jsonResponse(['error' => 'Terjadi kendala pada server saat menyimpan booking. Silakan coba beberapa saat lagi.'], 500);
    }
}

// Handle DELETE: Cancel booking (Admin only)
if ($method === 'DELETE' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE')) {
    requireAuth($pdo);

    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
        $id = $body['id'] ?? '';
    }

    if (empty($id)) {
        jsonResponse(['error' => 'ID booking wajib disertakan.'], 400);
    }

    try {
        // Ambil data booking sebelum dihapus untuk activity log
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $booking = $stmt->fetch();

        if (!$booking) {
            jsonResponse(['error' => 'Booking tidak ditemukan.'], 404);
        }

        // Hapus dari bookings
        $del = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
        $del->execute([':id' => $id]);

        // Catat ke activity_logs
        $logDetails = 'Booking atas nama ' . ($booking['name'] ?? '-') .
            ' (' . ($booking['wa'] ?? '-') . ') pada ' . ($booking['date'] ?? '-') .
            ' jam ' . ($booking['time'] ?? '-') . ' untuk paket ' . ($booking['package_name'] ?? '-') .
            ' telah dibatalkan/dihapus.';

        $logStmt = $pdo->prepare("INSERT INTO activity_logs (action, details, created_at) VALUES ('BOOKING_CANCELLED', :details, NOW())");
        $logStmt->execute([':details' => $logDetails]);

        // Hapus file fisik bukti transfer jika ada
        if (!empty($booking['payment_proof_url'])) {
            $filename = basename($booking['payment_proof_url']);
            $filePath = __DIR__ . '/../uploads/payment-proofs/' . $filename;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        jsonResponse(['success' => true, 'message' => 'Booking berhasil dibatalkan dan dihapus.']);
    } catch (Throwable $e) {
        error_log('[Cancel Booking Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        jsonResponse(['error' => 'Terjadi kendala pada server saat membatalkan booking. Silakan coba beberapa saat lagi.'], 500);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
