-- ============================================================
-- meimories.cam booking website — MySQL Database Schema
-- Database: if061026_meimories_sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(128) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`token`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` VARCHAR(64) PRIMARY KEY,
  `date` VARCHAR(20) NOT NULL,
  `time` VARCHAR(20) NOT NULL,
  `category` VARCHAR(100) NULL,
  `package_name` VARCHAR(100) NULL,
  `addons` LONGTEXT NULL,
  `total` DECIMAL(12, 2) DEFAULT 0,
  `payment_type` VARCHAR(50) NULL,
  `amount_to_pay` DECIMAL(12, 2) DEFAULT 0,
  `sisa_bayar` DECIMAL(12, 2) DEFAULT 0,
  `name` VARCHAR(191) NULL,
  `wa` VARCHAR(50) NULL,
  `lokasi` TEXT NULL,
  `notes` TEXT NULL,
  `payment_proof_url` TEXT NULL,
  `payment_proof_name` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_date_time` (`date`, `time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Akun Default:
-- Email: admin@meimories.cam
-- Password: AdminMeimories123!
INSERT INTO `admin_users` (`email`, `password_hash`)
VALUES ('admin@meimories.cam', '$2y$10$ThkyqsSEPBAYPbjiogTamefFMEYVSOwh.VNUF8dMEbZ.UTF9XMAIK')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);
