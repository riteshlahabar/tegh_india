-- =====================================================
-- Tegh Cables CRM - MySQL Database Schema
-- =====================================================
-- Run these queries in your MySQL database
-- Make sure to create the database first:
-- CREATE DATABASE tegh_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE tegh_crm;
-- =====================================================

-- Drop tables if they exist (in reverse order due to foreign keys)
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `busy_sync_logs`;
DROP TABLE IF EXISTS `email_messages`;
DROP TABLE IF EXISTS `whatsapp_messages`;
DROP TABLE IF EXISTS `payment_promises`;
DROP TABLE IF EXISTS `calls`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `parties`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `users`;

-- =====================================================
-- 1. USERS TABLE
-- =====================================================
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) NOT NULL DEFAULT 'telecaller' COMMENT 'admin, telecaller',
  `initials` VARCHAR(8) NULL DEFAULT NULL,
  `branch` VARCHAR(255) NOT NULL DEFAULT 'All',
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. PASSWORD RESET TOKENS TABLE
-- =====================================================
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. SESSIONS TABLE
-- =====================================================
CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL DEFAULT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  INDEX `sessions_user_id_index` (`user_id`),
  INDEX `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CACHE TABLE
-- =====================================================
CREATE TABLE `cache` (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  INDEX `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. CACHE LOCKS TABLE
-- =====================================================
CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL,
  INDEX `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. JOBS TABLE
-- =====================================================
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` SMALLINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL DEFAULT NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  INDEX `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. JOB BATCHES TABLE
-- =====================================================
CREATE TABLE `job_batches` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL DEFAULT NULL,
  `cancelled_at` INT NULL DEFAULT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. FAILED JOBS TABLE
-- =====================================================
CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(255) NOT NULL UNIQUE,
  `connection` VARCHAR(255) NOT NULL,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `failed_jobs_connection_queue_failed_at_index` (`connection`, `queue`, `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. PARTIES TABLE (Customers)
-- =====================================================
CREATE TABLE `parties` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(255) NOT NULL UNIQUE,
  `contact_person` VARCHAR(255) NULL DEFAULT NULL,
  `mobile` VARCHAR(20) NULL DEFAULT NULL,
  `email` VARCHAR(255) NULL DEFAULT NULL,
  `gstin` VARCHAR(32) NULL DEFAULT NULL,
  `city` VARCHAR(255) NULL DEFAULT NULL,
  `state` VARCHAR(255) NULL DEFAULT NULL,
  `sales_person` VARCHAR(255) NULL DEFAULT NULL,
  `branch` VARCHAR(255) NULL DEFAULT NULL,
  `assigned_to` BIGINT UNSIGNED NULL DEFAULT NULL,
  `credit_limit` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `payment_terms` VARCHAR(255) NULL DEFAULT NULL,
  `busy_party_id` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `parties_busy_party_id_index` (`busy_party_id`),
  CONSTRAINT `parties_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. INVOICES TABLE
-- =====================================================
CREATE TABLE `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `party_id` BIGINT UNSIGNED NOT NULL,
  `invoice_no` VARCHAR(255) NOT NULL UNIQUE,
  `po_no` VARCHAR(255) NULL DEFAULT NULL,
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `invoice_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `received_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `outstanding` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(255) NOT NULL DEFAULT 'Not Due',
  `priority` VARCHAR(255) NOT NULL DEFAULT 'low' COMMENT 'high, med, low',
  `sales_person` VARCHAR(255) NULL DEFAULT NULL,
  `branch` VARCHAR(255) NULL DEFAULT NULL,
  `assigned_to` BIGINT UNSIGNED NULL DEFAULT NULL,
  `last_followup_at` TIMESTAMP NULL DEFAULT NULL,
  `next_followup_on` DATE NULL DEFAULT NULL,
  `busy_invoice_id` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `invoices_busy_invoice_id_index` (`busy_invoice_id`),
  CONSTRAINT `invoices_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. CALLS TABLE (Call History)
-- =====================================================
CREATE TABLE `calls` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `party_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `telecaller_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `called_at` DATETIME NOT NULL,
  `outcome` VARCHAR(255) NOT NULL,
  `remarks` TEXT NULL DEFAULT NULL,
  `next_followup_on` DATE NULL DEFAULT NULL,
  `promise_amount` DECIMAL(15, 2) NULL DEFAULT NULL,
  `promise_date` DATE NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `calls_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calls_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calls_telecaller_id_foreign` FOREIGN KEY (`telecaller_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. PAYMENT PROMISES TABLE
-- =====================================================
CREATE TABLE `payment_promises` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `party_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `telecaller_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `promise_date` DATE NOT NULL,
  `collected_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'upcoming' COMMENT 'upcoming, due_today, broken, collected',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `payment_promises_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_promises_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_promises_telecaller_id_foreign` FOREIGN KEY (`telecaller_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. WHATSAPP MESSAGES TABLE
-- =====================================================
CREATE TABLE `whatsapp_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `party_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `mobile` VARCHAR(255) NULL DEFAULT NULL,
  `template` VARCHAR(255) NULL DEFAULT NULL,
  `message` TEXT NOT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `whatsapp_messages_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_messages_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. EMAIL MESSAGES TABLE
-- =====================================================
CREATE TABLE `email_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `party_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `to_email` VARCHAR(255) NOT NULL,
  `cc` VARCHAR(255) NULL DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `email_messages_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_messages_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 15. APP SETTINGS TABLE
-- =====================================================
CREATE TABLE `app_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(255) NOT NULL UNIQUE,
  `value` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 16. BUSY SYNC LOGS
-- =====================================================
CREATE TABLE `busy_sync_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `triggered_by` VARCHAR(255) NULL DEFAULT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'running',
  `parties_upserted` INT UNSIGNED NOT NULL DEFAULT 0,
  `invoices_upserted` INT UNSIGNED NOT NULL DEFAULT 0,
  `invoices_closed` INT UNSIGNED NOT NULL DEFAULT 0,
  `error_message` TEXT NULL DEFAULT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `finished_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MIGRATIONS TABLE (Laravel tracking)
-- =====================================================
CREATE TABLE `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert migration records
-- =====================================================
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2026_08_20_100000_create_outstanding_crm_tables', 1),
('2026_08_29_111400_add_busy_integration_support', 1);

-- =====================================================
-- ADMIN USER (username: admin / password: 1234)
-- remember_token auto-generated (60 chars via UUID)
-- =====================================================
INSERT INTO `users` (
  `name`, `username`, `email`, `email_verified_at`, `password`,
  `role`, `initials`, `branch`, `remember_token`, `created_at`, `updated_at`
)
SELECT
  'Tegh Admin',
  'admin',
  'admin@teghcables.com',
  NOW(),
  '$2y$12$L2xTOAc9LI.OkynEhlH5AuNovsSrO.rf1tBpZ5IVGp8WBRNzz7xZW',
  'admin',
  'TA',
  'All',
  LEFT(CONCAT(REPLACE(UUID(), '-', ''), REPLACE(UUID(), '-', ''), REPLACE(UUID(), '-', '')), 60),
  NOW(),
  NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');

-- =====================================================
-- Optional: telecallers (password: 1234)
-- =====================================================
INSERT INTO `users` (`name`,`username`,`email`,`password`,`role`,`initials`,`branch`,`remember_token`,`created_at`,`updated_at`)
SELECT 'Aakanksha','aakanksha','aakanksha@teghcables.com','$2y$12$L2xTOAc9LI.OkynEhlH5AuNovsSrO.rf1tBpZ5IVGp8WBRNzz7xZW','telecaller','AK','Mumbai HQ',LEFT(CONCAT(REPLACE(UUID(),'-',''),REPLACE(UUID(),'-','')),60),NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username`='aakanksha');

INSERT INTO `users` (`name`,`username`,`email`,`password`,`role`,`initials`,`branch`,`remember_token`,`created_at`,`updated_at`)
SELECT 'Javed','javed','javed@teghcables.com','$2y$12$L2xTOAc9LI.OkynEhlH5AuNovsSrO.rf1tBpZ5IVGp8WBRNzz7xZW','telecaller','JV','Bhiwandi Plant',LEFT(CONCAT(REPLACE(UUID(),'-',''),REPLACE(UUID(),'-','')),60),NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username`='javed');

INSERT INTO `users` (`name`,`username`,`email`,`password`,`role`,`initials`,`branch`,`remember_token`,`created_at`,`updated_at`)
SELECT 'Amol','amol','amol@teghcables.com','$2y$12$L2xTOAc9LI.OkynEhlH5AuNovsSrO.rf1tBpZ5IVGp8WBRNzz7xZW','telecaller','AM','Pune Branch',LEFT(CONCAT(REPLACE(UUID(),'-',''),REPLACE(UUID(),'-','')),60),NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username`='amol');

-- =====================================================
-- Done! Or seed full demo data with:
-- php artisan db:seed
-- =====================================================
