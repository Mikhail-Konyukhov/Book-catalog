-- MYSQL_DATABASE creates only one schema; the test suite needs its own.
CREATE DATABASE IF NOT EXISTS `infotech` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `infotech_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
