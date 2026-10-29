<?php

/**
 * One-time server repair for TPI CRM on shared hosting.
 * Upload to: public_html/tpi.turnkeyinfotech.live/public/fix-server.php
 * Open: https://tpi.turnkeyinfotech.live/fix-server.php?key=tpi-fix-2026
 * DELETE this file after it shows OK.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? '';
if ($key !== 'tpi-fix-2026') {
    http_response_code(403);
    echo "Forbidden. Use ?key=tpi-fix-2026\n";
    exit;
}

echo "=== TPI CRM Server Fix ===\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'Time: '.date('c')."\n\n";

$root = dirname(__DIR__);
$envPath = $root.'/.env';

if (! is_file($envPath)) {
    echo "FAIL: .env not found at {$envPath}\n";
    exit(1);
}

function env_val(string $path, string $key, ?string $default = null): ?string
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        if (trim($k) !== $key) {
            continue;
        }
        $v = trim($v);
        if (
            (str_starts_with($v, '"') && str_ends_with($v, '"')) ||
            (str_starts_with($v, "'") && str_ends_with($v, "'"))
        ) {
            $v = substr($v, 1, -1);
        }

        return $v;
    }

    return $default;
}

$host = env_val($envPath, 'DB_HOST', 'localhost');
$port = env_val($envPath, 'DB_PORT', '3306');
$db = env_val($envPath, 'DB_DATABASE');
$user = env_val($envPath, 'DB_USERNAME');
$pass = env_val($envPath, 'DB_PASSWORD', '');

echo "DB host={$host} db={$db} user={$user}\n";

if (! $db || ! $user) {
    echo "FAIL: DB credentials missing in .env\n";
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "OK: Database connected\n";
} catch (Throwable $e) {
    echo 'FAIL: DB connection: '.$e->getMessage()."\n";
    echo "Tip: try DB_HOST=127.0.0.1 in .env\n";
    exit(1);
}

$statements = [
    // Sanctum tokens (API login)
    'CREATE TABLE IF NOT EXISTS personal_access_tokens (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        tokenable_type varchar(255) NOT NULL,
        tokenable_id bigint unsigned NOT NULL,
        name text NOT NULL,
        token varchar(64) NOT NULL,
        abilities text NULL,
        last_used_at timestamp NULL DEFAULT NULL,
        expires_at timestamp NULL DEFAULT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY personal_access_tokens_token_unique (token),
        KEY personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id),
        KEY personal_access_tokens_expires_at_index (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

    // Core CRM tables if missing
    "CREATE TABLE IF NOT EXISTS users (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        email_verified_at timestamp NULL DEFAULT NULL,
        password varchar(255) NOT NULL,
        role varchar(255) NOT NULL DEFAULT 'sales_executive',
        branch varchar(255) NOT NULL DEFAULT 'All',
        remember_token varchar(100) DEFAULT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY users_email_unique (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'CREATE TABLE IF NOT EXISTS cities (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY cities_name_unique (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

    "CREATE TABLE IF NOT EXISTS companies (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        enquiry_product_name varchar(255) DEFAULT NULL,
        contact_person_name varchar(255) DEFAULT NULL,
        mobile_number varchar(20) DEFAULT NULL,
        whatsapp_number varchar(20) DEFAULT NULL,
        email varchar(255) DEFAULT NULL,
        industry varchar(255) DEFAULT NULL,
        city varchar(255) DEFAULT NULL,
        sub_city varchar(255) DEFAULT NULL,
        address text,
        gst_number varchar(255) DEFAULT NULL,
        credit_limit decimal(15,2) NOT NULL DEFAULT 0.00,
        payment_terms varchar(255) DEFAULT NULL,
        outstanding_amount decimal(15,2) NOT NULL DEFAULT 0.00,
        stage varchar(255) NOT NULL DEFAULT 'New',
        assigned_exec_id bigint unsigned DEFAULT NULL,
        assigned_tc_id bigint unsigned DEFAULT NULL,
        created_by bigint unsigned DEFAULT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        KEY companies_assigned_exec_id_foreign (assigned_exec_id),
        KEY companies_assigned_tc_id_foreign (assigned_tc_id),
        KEY companies_created_by_foreign (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS visits (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        company_id bigint unsigned NOT NULL,
        executive_id bigint unsigned NOT NULL,
        checked_in_at datetime NOT NULL,
        checked_out_at datetime DEFAULT NULL,
        latitude decimal(10,7) DEFAULT NULL,
        longitude decimal(10,7) DEFAULT NULL,
        proof_photo_path varchar(255) DEFAULT NULL,
        notes text,
        discount_amount decimal(15,2) DEFAULT NULL,
        discount_note text,
        status varchar(255) NOT NULL DEFAULT 'checked_in',
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        KEY visits_company_id_foreign (company_id),
        KEY visits_executive_id_foreign (executive_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo 'OK: schema step '.($i + 1)."\n";
    } catch (Throwable $e) {
        echo 'WARN schema step '.($i + 1).': '.$e->getMessage()."\n";
    }
}

try {
    $cols = $pdo->query("SHOW COLUMNS FROM companies LIKE 'enquiry_product_name'")->fetchAll();
    if (! $cols) {
        $pdo->exec('ALTER TABLE companies ADD COLUMN enquiry_product_name varchar(255) DEFAULT NULL AFTER name');
        echo "OK: added companies.enquiry_product_name\n";
    } else {
        echo "OK: companies.enquiry_product_name already present\n";
    }
} catch (Throwable $e) {
    echo 'WARN enquiry_product_name: '.$e->getMessage()."\n";
}

try {
    $contactCols = [
        'contact_person_name' => 'ALTER TABLE companies ADD COLUMN contact_person_name varchar(255) DEFAULT NULL AFTER enquiry_product_name',
        'mobile_number' => 'ALTER TABLE companies ADD COLUMN mobile_number varchar(20) DEFAULT NULL AFTER contact_person_name',
        'whatsapp_number' => 'ALTER TABLE companies ADD COLUMN whatsapp_number varchar(20) DEFAULT NULL AFTER mobile_number',
        'email' => 'ALTER TABLE companies ADD COLUMN email varchar(255) DEFAULT NULL AFTER whatsapp_number',
        'created_by' => 'ALTER TABLE companies ADD COLUMN created_by bigint unsigned DEFAULT NULL AFTER assigned_tc_id',
    ];
    foreach ($contactCols as $col => $alter) {
        $cols = $pdo->query("SHOW COLUMNS FROM companies LIKE '{$col}'")->fetchAll();
        if (! $cols) {
            $pdo->exec($alter);
            echo "OK: added companies.{$col}\n";
        } else {
            echo "OK: companies.{$col} already present\n";
        }
    }
} catch (Throwable $e) {
    echo 'WARN contact/created_by fields: '.$e->getMessage()."\n";
}

try {
    $discountCols = [
        'house_wire_discount' => 'ALTER TABLE companies ADD COLUMN house_wire_discount tinyint(1) NOT NULL DEFAULT 0 AFTER enquiry_product_name',
        'house_wire_discount_note' => 'ALTER TABLE companies ADD COLUMN house_wire_discount_note varchar(1000) DEFAULT NULL AFTER house_wire_discount',
        'flexible_wire_discount' => 'ALTER TABLE companies ADD COLUMN flexible_wire_discount tinyint(1) NOT NULL DEFAULT 0 AFTER house_wire_discount_note',
        'flexible_wire_discount_note' => 'ALTER TABLE companies ADD COLUMN flexible_wire_discount_note varchar(1000) DEFAULT NULL AFTER flexible_wire_discount',
        'other_wire_discount' => 'ALTER TABLE companies ADD COLUMN other_wire_discount tinyint(1) NOT NULL DEFAULT 0 AFTER flexible_wire_discount_note',
        'other_wire_discount_note' => 'ALTER TABLE companies ADD COLUMN other_wire_discount_note varchar(1000) DEFAULT NULL AFTER other_wire_discount',
        'enquiry_admin_note' => 'ALTER TABLE companies ADD COLUMN enquiry_admin_note text DEFAULT NULL AFTER other_wire_discount_note',
    ];
    foreach ($discountCols as $col => $alter) {
        $cols = $pdo->query("SHOW COLUMNS FROM companies LIKE '{$col}'")->fetchAll();
        if (! $cols) {
            $pdo->exec($alter);
            echo "OK: added companies.{$col}\n";
        } else {
            echo "OK: companies.{$col} already present\n";
        }
    }
} catch (Throwable $e) {
    echo 'WARN wire discount/admin note fields: '.$e->getMessage()."\n";
}

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS products (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY products_name_unique (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    echo "OK: products table ready\n";

    $pdo->exec('CREATE TABLE IF NOT EXISTS company_product (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        company_id bigint unsigned NOT NULL,
        product_id bigint unsigned NOT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY company_product_unique (company_id, product_id),
        KEY company_product_company_id_foreign (company_id),
        KEY company_product_product_id_foreign (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    echo "OK: company_product table ready\n";

    $pdo->exec('CREATE TABLE IF NOT EXISTS visit_product (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        visit_id bigint unsigned NOT NULL,
        product_id bigint unsigned NOT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY visit_product_unique (visit_id, product_id),
        KEY visit_product_visit_id_foreign (visit_id),
        KEY visit_product_product_id_foreign (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    echo "OK: visit_product table ready\n";

    $productExtraCols = [
        'product_code' => 'ALTER TABLE products ADD COLUMN product_code varchar(255) DEFAULT NULL AFTER name',
        'usage_unit' => 'ALTER TABLE products ADD COLUMN usage_unit varchar(255) DEFAULT NULL AFTER product_code',
        'description' => 'ALTER TABLE products ADD COLUMN description text DEFAULT NULL AFTER usage_unit',
        'category' => 'ALTER TABLE products ADD COLUMN category varchar(255) DEFAULT NULL AFTER description',
        'hsn_code' => 'ALTER TABLE products ADD COLUMN hsn_code varchar(50) DEFAULT NULL AFTER category',
        'mts' => 'ALTER TABLE products ADD COLUMN mts varchar(50) DEFAULT NULL AFTER hsn_code',
        'colour' => 'ALTER TABLE products ADD COLUMN colour varchar(100) DEFAULT NULL AFTER mts',
        'size_sqmm' => 'ALTER TABLE products ADD COLUMN size_sqmm varchar(50) DEFAULT NULL AFTER colour',
        'core' => 'ALTER TABLE products ADD COLUMN core varchar(50) DEFAULT NULL AFTER size_sqmm',
        'spec' => 'ALTER TABLE products ADD COLUMN spec varchar(100) DEFAULT NULL AFTER core',
        'unit' => 'ALTER TABLE products ADD COLUMN unit varchar(50) DEFAULT NULL AFTER spec',
        'des1' => 'ALTER TABLE products ADD COLUMN des1 varchar(255) DEFAULT NULL AFTER unit',
        'des2' => 'ALTER TABLE products ADD COLUMN des2 varchar(255) DEFAULT NULL AFTER des1',
    ];
    foreach ($productExtraCols as $col => $alter) {
        $cols = $pdo->query("SHOW COLUMNS FROM products LIKE '{$col}'")->fetchAll();
        if (! $cols) {
            $pdo->exec($alter);
            echo "OK: added products.{$col}\n";
        } else {
            echo "OK: products.{$col} already present\n";
        }
    }
} catch (Throwable $e) {
    echo 'WARN products tables: '.$e->getMessage()."\n";
}

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS sub_cities (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        city_id bigint unsigned NOT NULL,
        name varchar(255) NOT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY sub_cities_city_id_name_unique (city_id, name),
        KEY sub_cities_city_id_foreign (city_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    echo "OK: sub_cities table ready\n";
} catch (Throwable $e) {
    echo 'WARN sub_cities table: '.$e->getMessage()."\n";
}

try {
    $cols = $pdo->query("SHOW COLUMNS FROM companies LIKE 'sub_city'")->fetchAll();
    if (! $cols) {
        $pdo->exec('ALTER TABLE companies ADD COLUMN sub_city varchar(255) DEFAULT NULL AFTER city');
        echo "OK: added companies.sub_city\n";
    } else {
        echo "OK: companies.sub_city already present\n";
    }
} catch (Throwable $e) {
    echo 'WARN companies.sub_city: '.$e->getMessage()."\n";
}

try {
    $cols = $pdo->query("SHOW COLUMNS FROM visits LIKE 'discount_amount'")->fetchAll();
    if (! $cols) {
        $pdo->exec('ALTER TABLE visits ADD COLUMN discount_amount decimal(15,2) DEFAULT NULL AFTER notes');
        echo "OK: added visits.discount_amount\n";
    } else {
        echo "OK: visits.discount_amount already present\n";
    }
    $cols = $pdo->query("SHOW COLUMNS FROM visits LIKE 'discount_note'")->fetchAll();
    if (! $cols) {
        $pdo->exec('ALTER TABLE visits ADD COLUMN discount_note text DEFAULT NULL AFTER discount_amount');
        echo "OK: added visits.discount_note\n";
    } else {
        echo "OK: visits.discount_note already present\n";
    }
} catch (Throwable $e) {
    echo 'WARN visit discount fields: '.$e->getMessage()."\n";
}

// Seed / repair users
$pass123 = '$2y$12$byl1AvmT.3x4iGiwCJl9wumIC.XZIIFq/cviT18VJjZyxkAlvwyqa'; // 12345678

$upsertUser = $pdo->prepare(
    'INSERT INTO users (name, email, password, role, branch, created_at, updated_at)
     VALUES (:name, :email, :password, :role, :branch, NOW(), NOW())
     ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role), name = VALUES(name), updated_at = NOW()'
);

$users = [
    ['Admin User', 'admin@gmail.com', $pass123, 'admin', 'All'],
    ['Rakesh Jadhav', 'sales@tpi.com', $pass123, 'sales_executive', 'Mumbai'],
    ['Pooja Shinde', 'telecaller@tpi.com', $pass123, 'telecaller', 'Mumbai'],
];

foreach ($users as [$name, $email, $password, $role, $branch]) {
    $upsertUser->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $password,
        ':role' => $role,
        ':branch' => $branch,
    ]);
    echo "OK: user {$email}\n";
}

$salesId = (int) $pdo->query("SELECT id FROM users WHERE email='sales@tpi.com' LIMIT 1")->fetchColumn();
$tcId = (int) $pdo->query("SELECT id FROM users WHERE email='telecaller@tpi.com' LIMIT 1")->fetchColumn();

if ($salesId > 0) {
    $pdo->exec('UPDATE companies SET assigned_exec_id = '.(int) $salesId.' WHERE assigned_exec_id IS NULL');
    echo "OK: assigned unassigned companies to sales id {$salesId}\n";

    $count = (int) $pdo->query('SELECT COUNT(*) FROM companies WHERE assigned_exec_id = '.(int) $salesId)->fetchColumn();
    if ($count === 0) {
        $ins = $pdo->prepare(
            'INSERT INTO companies (name, industry, city, address, credit_limit, outstanding_amount, stage, assigned_exec_id, assigned_tc_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $ins->execute(['Shree Balaji Electricals', 'Industrial Distribution', 'Pune', 'Plot 42, Industrial Area Phase II, Pune', 850000, 120000, 'Follow-up', $salesId, $tcId ?: null]);
        $ins->execute(['Om Sai Infra Projects', 'EPC Contractor', 'Mumbai', 'Marine Drive, Mumbai', 1200000, 340000, 'Quotation Sent', $salesId, $tcId ?: null]);
        $ins->execute(['Gujarat Power Solutions', 'Utility / Govt Vendor', 'Gujarat', 'Ahmedabad', 600000, 0, 'New', $salesId, $tcId ?: null]);
        echo "OK: seeded 3 sample leads for salesperson\n";
    } else {
        echo "OK: salesperson already has {$count} lead(s)\n";
    }
}

// Clear Laravel caches if writable
foreach (['bootstrap/cache/config.php', 'bootstrap/cache/routes-v7.php', 'bootstrap/cache/routes.php'] as $rel) {
    $file = $root.'/'.$rel;
    if (is_file($file)) {
        @unlink($file);
        echo 'OK: cleared '.$rel."\n";
    }
}

$storage = $root.'/storage/logs';
if (! is_dir($storage)) {
    @mkdir($storage, 0775, true);
}
@file_put_contents($storage.'/fix-server.log', date('c')." fix-server ran\n", FILE_APPEND);

echo "\n=== DONE ===\n";
echo "1) Login app as sales@tpi.com / 12345678\n";
echo "2) DELETE this file: public/fix-server.php\n";
echo "3) Test: https://tpi.turnkeyinfotech.live/api/login\n";
