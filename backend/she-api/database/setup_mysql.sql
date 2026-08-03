-- MySQL Setup Script for SHE Inspection System
-- Run this script to configure database and users

-- Create database
CREATE DATABASE IF NOT EXISTS she_inspection
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Use the database
USE she_inspection;

-- Create pma user for phpMyAdmin advanced features
CREATE USER IF NOT EXISTS 'pma'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON phpmyadmin.* TO 'pma'@'localhost';

-- Create phpMyAdmin configuration storage database
CREATE DATABASE IF NOT EXISTS phpmyadmin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE phpmyadmin;

-- Create phpMyAdmin tables for advanced features
CREATE TABLE IF NOT EXISTS pma_relation (
  master_db VARCHAR(64) DEFAULT '' NOT NULL,
  master_table VARCHAR(64) DEFAULT '' NOT NULL,
  master_field VARCHAR(64) DEFAULT '' NOT NULL,
  foreign_db VARCHAR(64) DEFAULT '' NOT NULL,
  foreign_table varchar(64) DEFAULT '' NOT NULL,
  foreign_field varchar(64) DEFAULT '' NOT NULL,
  PRIMARY KEY (master_db, master_table, master_field),
  KEY foreign_field (foreign_db, foreign_table, foreign_field)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pma_table_info (
  db_name varchar(64) NOT NULL default '',
  table_name varchar(64) NOT NULL default '',
  display_field varchar(64) default NULL,
  PRIMARY KEY (db_name, table_name)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pma_designer_coords (
  db_name varchar(64) NOT NULL default '',
  table_name varchar(64) NOT NULL default '',
  x int(11) DEFAULT NULL,
  y int(11) DEFAULT NULL,
  v varchar(10) DEFAULT NULL,
  h varchar(10) DEFAULT NULL,
  colorIndex tinyint(4) DEFAULT NULL,
  PRIMARY KEY (db_name, table_name)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Switch back to main database
USE she_inspection;

-- Grant privileges to root user (allow no password for localhost)
GRANT ALL PRIVILEGES ON she_inspection.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON she_inspection.* TO 'root'@'127.0.0.1';
GRANT ALL PRIVILEGES ON she_inspection.* TO 'root'@'%';

-- Grant privileges to pma user on phpmyadmin database
GRANT ALL PRIVILEGES ON phpmyadmin.* TO 'pma'@'localhost';

-- Flush privileges to ensure they take effect
FLUSH PRIVILEGES;

-- Show created databases
SHOW DATABASES;