<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * DineFlow / Salo — full schema.
 */
class CreateDineflowSchema extends Migration
{
    public function up()
    {
        $opts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $this->db->query("CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NULL,
            role ENUM('admin','cashier','kitchen','pending') NOT NULL DEFAULT 'pending',
            google_id VARCHAR(64) NULL,
            mfa_secret VARCHAR(64) NULL,
            mfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) $opts");

        $this->db->query("CREATE TABLE password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NULL,
            INDEX (token_hash)
        ) $opts");

        $this->db->query("CREATE TABLE categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            slug VARCHAR(80) NOT NULL UNIQUE,
            sort_order INT NOT NULL DEFAULT 0
        ) $opts");

        $this->db->query("CREATE TABLE menu_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NULL,
            name VARCHAR(120) NOT NULL,
            description TEXT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            prep_minutes INT NOT NULL DEFAULT 10,
            photo VARCHAR(500) NULL,
            options TEXT NULL,
            is_available TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX (category_id)
        ) $opts");

        $this->db->query("CREATE TABLE dining_tables (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(10) NOT NULL UNIQUE,
            seats INT NOT NULL DEFAULT 4,
            zone VARCHAR(40) NOT NULL DEFAULT 'Main Hall',
            status ENUM('available','occupied','reserved') NOT NULL DEFAULT 'available',
            sort_order INT NOT NULL DEFAULT 0,
            updated_at DATETIME NULL
        ) $opts");

        $this->db->query("CREATE TABLE table_visits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            table_id INT UNSIGNED NOT NULL,
            reservation_id INT UNSIGNED NULL,
            status ENUM('open','bill_requested','closed') NOT NULL DEFAULT 'open',
            deposit_credit DECIMAL(10,2) NOT NULL DEFAULT 0,
            opened_at DATETIME NOT NULL,
            closed_at DATETIME NULL,
            INDEX (table_id, status)
        ) $opts");

        $this->db->query("CREATE TABLE orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code CHAR(6) NOT NULL UNIQUE,
            type ENUM('dine_in','take_away') NOT NULL DEFAULT 'dine_in',
            table_id INT UNSIGNED NULL,
            visit_id INT UNSIGNED NULL,
            reservation_id INT UNSIGNED NULL,
            guests INT NOT NULL DEFAULT 1,
            customer_name VARCHAR(120) NULL,
            customer_mobile VARCHAR(30) NULL,
            customer_email VARCHAR(190) NULL,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
            discount_type ENUM('none','promo','senior','pwd') NOT NULL DEFAULT 'none',
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            promo_code VARCHAR(40) NULL,
            discount_id_no VARCHAR(60) NULL,
            discount_id_name VARCHAR(120) NULL,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('received','preparing','ready','served','cancelled') NOT NULL DEFAULT 'received',
            payment_method ENUM('cash','gcash','online_banking','pay_later') NOT NULL DEFAULT 'cash',
            payment_status ENUM('awaiting','for_verification','paid','rejected','tab') NOT NULL DEFAULT 'awaiting',
            bank_account_id INT UNSIGNED NULL,
            reference_no VARCHAR(80) NULL,
            proof_image VARCHAR(255) NULL,
            payment_submitted_at DATETIME NULL,
            rejection_reason VARCHAR(255) NULL,
            verified_by INT UNSIGNED NULL,
            verified_at DATETIME NULL,
            paid_at DATETIME NULL,
            released_at DATETIME NULL,
            estimated_ready_at DATETIME NULL,
            preparing_at DATETIME NULL,
            ready_at DATETIME NULL,
            served_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX (status), INDEX (payment_status), INDEX (visit_id), INDEX (paid_at), INDEX (created_at)
        ) $opts");

        $this->db->query("CREATE TABLE order_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            menu_item_id INT UNSIGNED NULL,
            name VARCHAR(120) NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            options_json TEXT NULL,
            options_text VARCHAR(500) NULL,
            note VARCHAR(255) NULL,
            line_total DECIMAL(10,2) NOT NULL,
            prep_minutes INT NOT NULL DEFAULT 10,
            INDEX (order_id), INDEX (menu_item_id)
        ) $opts");

        $this->db->query("CREATE TABLE reservations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code CHAR(6) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL,
            mobile VARCHAR(30) NOT NULL,
            email VARCHAR(190) NULL,
            res_date DATE NOT NULL,
            res_time TIME NOT NULL,
            guests INT NOT NULL DEFAULT 2,
            table_id INT UNSIGNED NULL,
            notes TEXT NULL,
            preorder_total DECIMAL(10,2) NOT NULL DEFAULT 0,
            deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            amount_due DECIMAL(10,2) NOT NULL DEFAULT 0,
            payment_method ENUM('none','cash','gcash','online_banking') NOT NULL DEFAULT 'none',
            payment_status ENUM('none','awaiting','for_verification','paid','rejected') NOT NULL DEFAULT 'none',
            bank_account_id INT UNSIGNED NULL,
            reference_no VARCHAR(80) NULL,
            proof_image VARCHAR(255) NULL,
            payment_submitted_at DATETIME NULL,
            rejection_reason VARCHAR(255) NULL,
            verified_by INT UNSIGNED NULL,
            verified_at DATETIME NULL,
            status ENUM('pending','confirmed','seated','cancelled','no_show') NOT NULL DEFAULT 'pending',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX (res_date), INDEX (status)
        ) $opts");

        $this->db->query("CREATE TABLE reservation_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reservation_id INT UNSIGNED NOT NULL,
            menu_item_id INT UNSIGNED NULL,
            name VARCHAR(120) NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            line_total DECIMAL(10,2) NOT NULL,
            prep_minutes INT NOT NULL DEFAULT 10,
            INDEX (reservation_id)
        ) $opts");

        $this->db->query("CREATE TABLE service_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            table_id INT UNSIGNED NOT NULL,
            visit_id INT UNSIGNED NULL,
            type ENUM('call_staff','bill','water') NOT NULL,
            status ENUM('open','done') NOT NULL DEFAULT 'open',
            handled_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            done_at DATETIME NULL,
            INDEX (status)
        ) $opts");

        $this->db->query("CREATE TABLE reviews (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NULL UNIQUE,
            guest_name VARCHAR(120) NULL,
            rating TINYINT NOT NULL,
            comment TEXT NULL,
            is_approved TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL
        ) $opts");

        $this->db->query("CREATE TABLE promo_codes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(40) NOT NULL UNIQUE,
            type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
            value DECIMAL(10,2) NOT NULL,
            min_order DECIMAL(10,2) NOT NULL DEFAULT 0,
            expires_at DATE NULL,
            usage_limit INT NULL,
            used_count INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL
        ) $opts");

        $this->db->query("CREATE TABLE settings (
            `key` VARCHAR(60) PRIMARY KEY,
            `value` TEXT NULL
        ) $opts");

        $this->db->query("CREATE TABLE bank_accounts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bank_name VARCHAR(80) NOT NULL,
            account_name VARCHAR(120) NOT NULL,
            account_number VARCHAR(60) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0
        ) $opts");

        $this->db->query("CREATE TABLE activity_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            user_name VARCHAR(120) NULL,
            action VARCHAR(60) NOT NULL,
            entity VARCHAR(40) NULL,
            entity_id INT UNSIGNED NULL,
            details VARCHAR(500) NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NULL,
            INDEX (created_at)
        ) $opts");

        $this->db->query("CREATE TABLE notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            channel ENUM('email','sms') NOT NULL,
            recipient VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NULL,
            message TEXT NOT NULL,
            status ENUM('sent','failed','logged') NOT NULL DEFAULT 'logged',
            error VARCHAR(255) NULL,
            created_at DATETIME NULL
        ) $opts");
    }

    public function down()
    {
        foreach (['notifications', 'activity_logs', 'bank_accounts', 'settings', 'promo_codes', 'reviews',
            'service_requests', 'reservation_items', 'reservations', 'order_items', 'orders', 'table_visits',
            'dining_tables', 'menu_items', 'categories', 'password_resets', 'users'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
