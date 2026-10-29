-- Run once on your Mac (local MySQL):
--   mysql -u root -p < database/setup_local_mysql.sql
--
-- Uses the same DB name/user/password as backend/.env

CREATE DATABASE IF NOT EXISTS turnkeyi_tpi
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'turnkeyi_tpi'@'localhost' IDENTIFIED BY 'A;6&VM?!(~Cd#Gb!';
CREATE USER IF NOT EXISTS 'turnkeyi_tpi'@'127.0.0.1' IDENTIFIED BY 'A;6&VM?!(~Cd#Gb!';

GRANT ALL PRIVILEGES ON turnkeyi_tpi.* TO 'turnkeyi_tpi'@'localhost';
GRANT ALL PRIVILEGES ON turnkeyi_tpi.* TO 'turnkeyi_tpi'@'127.0.0.1';

FLUSH PRIVILEGES;
