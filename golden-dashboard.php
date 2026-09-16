<?php
/**
 * Plugin Name: گلدن داشبورد
 * Plugin URI: https://github.com/mtrik/golden-dashboard
 * Description:  گلدن داشبورد مجموعه‌ای از ویجت‌های المنتور برای ایجاد و مدیریت داشبورد کیف پول کاربران در وردپرس است. در صورت استفاده از افزونه‌های دریافت خودکار نرخ ارز، طلا یا رمزارز، می‌توانید کیف پول‌های متناسب با هر دارایی ایجاد کرده و مدیریت تراکنش‌ها و تنظیمات آن‌ها را از پنل مدیریت انجام دهید.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: MTRIK
 * Author URI: https://github.com/mtrik/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: golden-dashboard
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

if (!defined('GDB_VERSION')) {
    define('GDB_VERSION', '1.0.0');
}

if (!defined('GDB_FILE')) {
    define('GDB_FILE', __FILE__);
}

if (!defined('GDB_PATH')) {
    define('GDB_PATH', plugin_dir_path(__FILE__));
}

if (!defined('GDB_URL')) {
    define('GDB_URL', plugin_dir_url(__FILE__));
}

if (!defined('GDB_ADMIN_PATH')) {
    define('GDB_ADMIN_PATH', GDB_PATH . 'admin/');
}

if (!defined('GDB_ADMIN_URL')) {
    define('GDB_ADMIN_URL', GDB_URL . 'admin/');
}

require_once GDB_PATH . 'includes/class-loader.php';

new GDB_Loader();