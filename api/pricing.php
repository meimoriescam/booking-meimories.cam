<?php
// api/pricing.php
require_once __DIR__ . '/config.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-migration jika tabel pricing_settings belum ada
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pricing_settings` (
          `setting_key` VARCHAR(64) PRIMARY KEY,
          `setting_value` VARCHAR(255) NOT NULL,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Throwable $e) {
    error_log('[Pricing Migration Error] ' . $e->getMessage());
}

$defaultPricing = [
    // Regular Pack
    'mini' => 95000,
    'sweet' => 150000,
    'signature' => 185000,
    // Graduation Pack
    'moment' => 155000,
    'journey' => 250000,
    'milestone' => 300000,
    'achievement' => 325000,
    'graduatestory' => 400000,
    // Group Pack
    'bestie' => 270000,
    'circle' => 445000,
    'together' => 745000,
    'forever' => 1000000,
    'group_per_person' => 100000,
    // Foto Polaroid
    'p1' => 25000,
    'pmini' => 110000,
    'psweet' => 210000,
    'pmemories' => 299000,
    'punlimited' => 390000,
    // Addons & Kebijakan
    'addon_duration30' => 40000,
    'addon_location' => 40000,
    'dp_min_percent' => 50,
];

// --- GET: Ambil daftar harga aktif (Publik) ---
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM pricing_settings");
        $rows = $stmt->fetchAll();

        $pricing = $defaultPricing;
        foreach ($rows as $r) {
            $k = $r['setting_key'];
            if (array_key_exists($k, $defaultPricing)) {
                $pricing[$k] = (float) $r['setting_value'];
            }
        }

        jsonResponse([
            'data' => $pricing,
            'defaults' => $defaultPricing,
        ]);
    } catch (Throwable $e) {
        error_log('[Pricing GET Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        // Fallback aman ke default jika terjadi kendala query
        jsonResponse([
            'data' => $defaultPricing,
            'defaults' => $defaultPricing,
            'warning' => 'Memuat harga default.'
        ]);
    }
}

// --- POST: Update harga atau Reset ke Default (Khusus Admin) ---
if ($method === 'POST') {
    $session = requireAuth($pdo);

    $raw = file_get_contents('php://input');
    $input = [];
    if (!empty($raw)) {
        $input = json_decode($raw, true) ?: [];
    }
    if (empty($input)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? $_GET['action'] ?? 'update';

    // 1. Aksi Reset ke Default
    if ($action === 'reset') {
        try {
            $pdo->beginTransaction();
            $pdo->exec("DELETE FROM pricing_settings");

            // Catat log aktivitas
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES ('RESET_PRICING', :details)");
            $logStmt->execute([
                ':details' => 'Admin ' . ($session['email'] ?? 'Unknown') . ' mereset seluruh harga paket ke nilai awal default.'
            ]);

            $pdo->commit();
            jsonResponse([
                'success' => true,
                'data' => $defaultPricing,
                'message' => 'Harga berhasil dikembalikan ke default.'
            ]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[Pricing Reset Error] ' . $e->getMessage());
            jsonResponse(['error' => 'Gagal mereset harga: ' . $e->getMessage()], 500);
        }
    }

    // 2. Aksi Simpan Perubahan Harga
    $newPrices = $input['prices'] ?? $input;
    if (!is_array($newPrices)) {
        jsonResponse(['error' => 'Data harga tidak valid.'], 400);
    }

    $toUpdate = [];
    foreach ($defaultPricing as $key => $defaultVal) {
        if (isset($newPrices[$key])) {
            $val = floatval($newPrices[$key]);
            if ($val < 0) {
                jsonResponse(['error' => "Harga untuk item '{$key}' tidak boleh bernilai negatif."], 400);
            }
            if ($key === 'dp_min_percent' && ($val < 0 || $val > 100)) {
                jsonResponse(['error' => "Persentase minimal DP harus antara 0% hingga 100%."], 400);
            }
            $toUpdate[$key] = $val;
        }
    }

    if (empty($toUpdate)) {
        jsonResponse(['error' => 'Tidak ada perubahan harga yang dikirimkan.'], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO pricing_settings (setting_key, setting_value) 
            VALUES (:key, :val) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($toUpdate as $k => $v) {
            $stmt->execute([':key' => $k, ':val' => (string)$v]);
        }

        // Catat log aktivitas
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES ('UPDATE_PRICING', :details)");
        $logStmt->execute([
            ':details' => 'Admin ' . ($session['email'] ?? 'Unknown') . ' mengubah harga untuk ' . count($toUpdate) . ' item: ' . json_encode($toUpdate)
        ]);

        $pdo->commit();

        // Ambil data hasil update
        $pricing = $defaultPricing;
        $selectStmt = $pdo->query("SELECT setting_key, setting_value FROM pricing_settings");
        foreach ($selectStmt->fetchAll() as $r) {
            if (array_key_exists($r['setting_key'], $defaultPricing)) {
                $pricing[$r['setting_key']] = (float) $r['setting_value'];
            }
        }

        jsonResponse([
            'success' => true,
            'data' => $pricing,
            'message' => 'Perubahan harga berhasil disimpan!'
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Pricing Save Error] ' . $e->getMessage());
        jsonResponse(['error' => 'Gagal menyimpan perubahan harga: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Metode HTTP tidak didukung.'], 405);
