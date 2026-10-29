-- ============================================================
-- TPI CRM patch — fix API login (500) + sales leads visibility
-- Import in phpMyAdmin on database: turnkeyi_tpi
-- ============================================================

-- 1) Sanctum tokens table (missing table causes API login 500)
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Ensure sales + telecaller users exist with known passwords
-- Sales: sales@tpi.com / password
-- Telecaller: telecaller@tpi.com / password
-- Admin: admin@gmail.com / 12345678

INSERT INTO `users` (`name`, `email`, `password`, `role`, `branch`, `created_at`, `updated_at`)
SELECT 'Rakesh Jadhav', 'sales@tpi.com', '$2y$12$SovtI6PPtaWJ3FmTsRHjiefrh2/cCefvjQoaPlbY2LtMqo9fKLW/.', 'sales_executive', 'Mumbai', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'sales@tpi.com');

UPDATE `users`
SET `password` = '$2y$12$SovtI6PPtaWJ3FmTsRHjiefrh2/cCefvjQoaPlbY2LtMqo9fKLW/.',
    `role` = 'sales_executive',
    `name` = COALESCE(NULLIF(`name`, ''), 'Rakesh Jadhav'),
    `updated_at` = NOW()
WHERE `email` = 'sales@tpi.com';

INSERT INTO `users` (`name`, `email`, `password`, `role`, `branch`, `created_at`, `updated_at`)
SELECT 'Pooja Shinde', 'telecaller@tpi.com', '$2y$12$SovtI6PPtaWJ3FmTsRHjiefrh2/cCefvjQoaPlbY2LtMqo9fKLW/.', 'telecaller', 'Mumbai', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'telecaller@tpi.com');

UPDATE `users`
SET `password` = '$2y$12$SovtI6PPtaWJ3FmTsRHjiefrh2/cCefvjQoaPlbY2LtMqo9fKLW/.',
    `role` = 'telecaller',
    `updated_at` = NOW()
WHERE `email` = 'telecaller@tpi.com';

UPDATE `users`
SET `password` = '$2y$12$sn0wK2FQxdWjyAaPoSmnMeMBIG7vio58ejsBT.F5b3POsIRjQUW9W',
    `role` = 'admin',
    `updated_at` = NOW()
WHERE `email` = 'admin@gmail.com';

-- 3) Assign all unassigned companies to the sales executive
UPDATE `companies` c
JOIN `users` u ON u.email = 'sales@tpi.com'
SET c.assigned_exec_id = u.id,
    c.updated_at = NOW()
WHERE c.assigned_exec_id IS NULL;

-- 4) If no companies exist, seed sample leads for salesperson
INSERT INTO `companies`
(`name`, `industry`, `city`, `address`, `gst_number`, `credit_limit`, `payment_terms`, `outstanding_amount`, `stage`, `assigned_exec_id`, `assigned_tc_id`, `created_at`, `updated_at`)
SELECT
  'Shree Balaji Electricals',
  'Industrial Distribution',
  'Pune',
  'Pune Industrial Zone, Sector 4',
  '27BALAJI1234A1Z1',
  850000,
  '30 Days',
  120000,
  'Follow-up',
  u.id,
  (SELECT id FROM users WHERE email = 'telecaller@tpi.com' LIMIT 1),
  NOW(),
  NOW()
FROM users u
WHERE u.email = 'sales@tpi.com'
  AND NOT EXISTS (SELECT 1 FROM companies WHERE name = 'Shree Balaji Electricals');

INSERT INTO `companies`
(`name`, `industry`, `city`, `address`, `gst_number`, `credit_limit`, `payment_terms`, `outstanding_amount`, `stage`, `assigned_exec_id`, `assigned_tc_id`, `created_at`, `updated_at`)
SELECT
  'Om Sai Infra Projects',
  'EPC Contractor',
  'Mumbai',
  'Mumbai Marine Drive Road, Building C',
  '27OMSAI5678B1Z2',
  1200000,
  '45 Days',
  340000,
  'Quotation Sent',
  u.id,
  (SELECT id FROM users WHERE email = 'telecaller@tpi.com' LIMIT 1),
  NOW(),
  NOW()
FROM users u
WHERE u.email = 'sales@tpi.com'
  AND NOT EXISTS (SELECT 1 FROM companies WHERE name = 'Om Sai Infra Projects');

INSERT INTO `companies`
(`name`, `industry`, `city`, `address`, `gst_number`, `credit_limit`, `payment_terms`, `outstanding_amount`, `stage`, `assigned_exec_id`, `assigned_tc_id`, `created_at`, `updated_at`)
SELECT
  'Gujarat Power Solutions',
  'Utility / Govt Vendor',
  'Gujarat',
  'Ahmedabad Corporate Hub, Tower 2',
  '24GUJPOW9012C1Z3',
  600000,
  '15 Days',
  0,
  'New',
  u.id,
  (SELECT id FROM users WHERE email = 'telecaller@tpi.com' LIMIT 1),
  NOW(),
  NOW()
FROM users u
WHERE u.email = 'sales@tpi.com'
  AND NOT EXISTS (SELECT 1 FROM companies WHERE name = 'Gujarat Power Solutions');
