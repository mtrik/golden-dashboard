<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
// This file works directly with Golden Dashboard's own custom database tables
// (gd_user_wallet, gd_wallet_transactions, etc.), which have no WordPress core
// API equivalent, so direct $wpdb queries are required throughout. Every value
// that varies by request is passed through $wpdb->prepare() with %d/%s/%f
// placeholders (manually audited); object caching is intentionally not applied
// because wallet balances and transaction records must always reflect the
// latest write.

if (!defined('ABSPATH')) {
    exit;
}

class GDB_Loader
{
    private static $booted = false;
    private static $db_version = '2.8.0';

    public function __construct()
    {
        register_activation_hook(GDB_FILE, [$this, 'install']);
        register_deactivation_hook(GDB_FILE, [$this, 'deactivate']);
        register_uninstall_hook(GDB_FILE, ['GDB_Loader', 'uninstall']);

        $this->includes();

        add_action('plugins_loaded', [$this, 'boot'], 20);
        add_action('plugins_loaded', [$this, 'check_db_version'], 10);
    }

    private function includes()
    {
        $files = [
            'helpers.php',
            'class-wallet.php',
            'class-security.php',
            'class-cashback.php',
            'class-gold-wallet.php',
            'class-shortcodes.php',
            'class-assets.php',
            'class-ajax.php',
            'class-wallet-topup.php',
            'class-withdraw-request.php',
            'class-wallet-withdraw.php',
        ];

        foreach ($files as $file) {
            $path = GDB_PATH . 'includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public function boot()
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        if (class_exists('GDB_Assets')) {
            new GDB_Assets();
        }

        if (class_exists('GDB_Shortcodes')) {
            new GDB_Shortcodes();
        }

        if (class_exists('GDB_Ajax')) {
            new GDB_Ajax();
        }

        
        
        
        if (is_admin()) {
            $this->load_admin_class();
        }

        if (class_exists('WooCommerce') && class_exists('GDB_Wallet_Topup')) {
            static $wallet_topup = null;
            if (!$wallet_topup) {
                $wallet_topup = new GDB_Wallet_Topup();
            }
        }

        if (class_exists('WooCommerce') && class_exists('GDB_Gold_Wallet')) {
            static $gold_wallet = null;
            if (!$gold_wallet) {
                $gold_wallet = new GDB_Gold_Wallet();
            }
        }

        if (class_exists('WooCommerce') && class_exists('GDB_Cashback')) {
            static $cashback = null;
            if (!$cashback) {
                $cashback = new GDB_Cashback();
            }
        }

        add_action('elementor/init', function () {
            if (!did_action('elementor/loaded')) {
                return;
            }

            $base_file = GDB_PATH . 'includes/class-base-widget.php';
            if (file_exists($base_file)) {
                require_once $base_file;
            }

            $elementor_file = GDB_PATH . 'includes/class-elementor.php';
            if (file_exists($elementor_file)) {
                require_once $elementor_file;
            }

            if (class_exists('GDB_Elementor')) {
                new GDB_Elementor();
            }
        });
    }



    private function load_admin_class()
    {
        $admin_class = GDB_ADMIN_PATH . 'class-admin.php';
        if (file_exists($admin_class)) {
            require_once $admin_class;
        }

        if (class_exists('GDB_Admin') && !did_action('gdb_admin_loaded')) {
            new GDB_Admin();
            do_action('gdb_admin_loaded');
        }
    }





    public function install()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        
        $table_wallet = $wpdb->prefix . 'gd_user_wallet';
        $sql_wallet = "CREATE TABLE $table_wallet (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            balance decimal(20,2) NOT NULL DEFAULT '0.00',
            total_credit decimal(20,2) NOT NULL DEFAULT '0.00',
            total_debit decimal(20,2) NOT NULL DEFAULT '0.00',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_wallet);

        $this->add_index_if_not_exists($table_wallet, 'user_id', 'user_id');

        
        $table_trans = $wpdb->prefix . 'gd_wallet_transactions';
        $sql_trans = "CREATE TABLE $table_trans (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(20) NOT NULL,
            amount decimal(20,2) NOT NULL,
            fee_amount decimal(20,2) DEFAULT '0.00',
            net_amount decimal(20,2) DEFAULT '0.00',
            balance_before decimal(20,2) NOT NULL,
            balance_after decimal(20,2) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            reference_id bigint(20) DEFAULT '0',
            description text,
            status varchar(20) DEFAULT 'completed',
            tracking_code varchar(12) DEFAULT '',
            admin_note text,
            bank_transaction_id varchar(100) DEFAULT '',
            bank_date varchar(50) DEFAULT '',
            meta_data text,
            created_by bigint(20) DEFAULT '0',
            ip_address varchar(45) DEFAULT '',
            user_agent varchar(255) DEFAULT '',
            session_id varchar(128) DEFAULT '',
            is_suspicious tinyint(1) DEFAULT '0',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_trans);

        $this->add_index_if_not_exists($table_trans, 'user_id', 'user_id');
        $this->add_index_if_not_exists($table_trans, 'status', 'status');
        $this->add_index_if_not_exists($table_trans, 'tracking_code', 'tracking_code');
        $this->add_index_if_not_exists($table_trans, 'transaction_type', 'transaction_type');

        $this->upgrade_tables();

        
        $table_log = $wpdb->prefix . 'gd_wallet_security_log';
        $sql_log = "CREATE TABLE $table_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            severity varchar(20) DEFAULT 'low',
            message text,
            ip_address varchar(45) DEFAULT '',
            user_agent varchar(255) DEFAULT '',
            request_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_log);

        $this->add_index_if_not_exists($table_log, 'user_id', 'user_id');
        $this->add_index_if_not_exists($table_log, 'event_type', 'event_type');

        
        $table_audit = $wpdb->prefix . 'gd_audit_log';
        $sql_audit = "CREATE TABLE $table_audit (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_audit);

        $this->add_index_if_not_exists($table_audit, 'transaction_id', 'transaction_id');

        
        $table_rate_limit = $wpdb->prefix . 'gd_wallet_rate_limit';
        $sql_rate_limit = "CREATE TABLE $table_rate_limit (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            identifier varchar(100) NOT NULL,
            action_type varchar(50) NOT NULL,
            attempt_count int(11) DEFAULT '1',
            last_attempt datetime DEFAULT CURRENT_TIMESTAMP,
            blocked_until datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_identifier_action (identifier, action_type)
        ) $charset_collate;";
        dbDelta($sql_rate_limit);

        $this->add_index_if_not_exists($table_rate_limit, 'idx_blocked', 'blocked_until');

        
        $table_blocked_ips = $wpdb->prefix . 'gd_wallet_blocked_ips';
        $sql_blocked_ips = "CREATE TABLE $table_blocked_ips (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            reason text,
            blocked_by bigint(20) DEFAULT '0',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_ip (ip_address)
        ) $charset_collate;";
        dbDelta($sql_blocked_ips);

        

        
        $table_gold_types = $wpdb->prefix . 'gd_gold_wallet_types';
        $sql_gold_types = "CREATE TABLE $table_gold_types (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(150) NOT NULL,
            unit_label varchar(30) NOT NULL DEFAULT 'گرم',
            wc_product_id bigint(20) NOT NULL,
            suggested_amounts text,
            min_amount decimal(20,6) NOT NULL DEFAULT '0.000000',
            max_amount decimal(20,6) NOT NULL DEFAULT '0.000000',
            step_amount decimal(20,6) NOT NULL DEFAULT '0.100000',
            payment_mode varchar(20) NOT NULL DEFAULT 'wallet_only',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status)
        ) $charset_collate;";
        dbDelta($sql_gold_types);

        
        $table_user_gold = $wpdb->prefix . 'gd_user_metal_wallet';
        $sql_user_gold = "CREATE TABLE $table_user_gold (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            gold_type_id bigint(20) NOT NULL,
            balance decimal(20,6) NOT NULL DEFAULT '0.000000',
            total_credit decimal(20,6) NOT NULL DEFAULT '0.000000',
            total_debit decimal(20,6) NOT NULL DEFAULT '0.000000',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_user_type (user_id, gold_type_id)
        ) $charset_collate;";
        dbDelta($sql_user_gold);

        
        
        $table_metal_trans = $wpdb->prefix . 'gd_metal_transactions';
        $sql_metal_trans = "CREATE TABLE $table_metal_trans (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            gold_type_id bigint(20) NOT NULL,
            type varchar(20) NOT NULL,
            quantity decimal(20,6) NOT NULL,
            price_per_unit decimal(20,2) NOT NULL DEFAULT '0.00',
            rial_amount decimal(20,2) NOT NULL DEFAULT '0.00',
            fee_amount decimal(20,2) NOT NULL DEFAULT '0.00',
            net_rial_amount decimal(20,2) NOT NULL DEFAULT '0.00',
            balance_before decimal(20,6) NOT NULL DEFAULT '0.000000',
            balance_after decimal(20,6) NOT NULL DEFAULT '0.000000',
            payment_mode varchar(20) NOT NULL DEFAULT 'wallet',
            reference_id bigint(20) DEFAULT '0',
            transaction_type varchar(50) NOT NULL DEFAULT 'gold_purchase',
            status varchar(20) NOT NULL DEFAULT 'completed',
            tracking_code varchar(12) DEFAULT '',
            description text,
            meta_data text,
            ip_address varchar(45) DEFAULT '',
            user_agent varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user (user_id),
            KEY idx_type (gold_type_id),
            KEY idx_tracking (tracking_code)
        ) $charset_collate;";
        dbDelta($sql_metal_trans);

        update_option('gdb_db_version', self::$db_version);
        GDB_Withdraw_Request::install();
    }



    private function add_index_if_not_exists($table, $index_name, $column)
    {
        global $wpdb;
        
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}");
        $existing_indexes = [];
        foreach ($indexes as $idx) {
            $existing_indexes[] = $idx->Key_name;
        }

        if (!in_array($index_name, $existing_indexes)) {
            $wpdb->query("ALTER TABLE {$table} ADD INDEX {$index_name} ({$column})");
        }
    }



    private function upgrade_tables()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
        
        if (!in_array('fee_amount', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN fee_amount decimal(20,2) DEFAULT '0.00' AFTER amount");
        }
        if (!in_array('net_amount', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN net_amount decimal(20,2) DEFAULT '0.00' AFTER fee_amount");
        }
        if (!in_array('verified_at', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN verified_at datetime DEFAULT NULL AFTER is_suspicious");
        }

        
        
        
        
        
        
        
        
        
        
        
        
        
        
        $table_metal = $wpdb->prefix . 'gd_metal_transactions';
        $metal_columns = $wpdb->get_col("SHOW COLUMNS FROM {$table_metal}");

        if (!in_array('fee_amount', $metal_columns)) {
            $wpdb->query("ALTER TABLE {$table_metal} ADD COLUMN fee_amount decimal(20,2) DEFAULT '0.00' AFTER rial_amount");
        }
        if (!in_array('net_rial_amount', $metal_columns)) {
            $wpdb->query("ALTER TABLE {$table_metal} ADD COLUMN net_rial_amount decimal(20,2) DEFAULT '0.00' AFTER fee_amount");
        }
        if (!in_array('meta_data', $metal_columns)) {
            $wpdb->query("ALTER TABLE {$table_metal} ADD COLUMN meta_data text AFTER description");
        }
    }

    public function deactivate()
    {
        
    }

    public static function uninstall()
    {
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        $delete_tables = get_option('gdb_uninstall_delete_tables', false);
        if ($delete_tables) {
            global $wpdb;
            $tables = [
                $wpdb->prefix . 'gd_user_wallet',
                $wpdb->prefix . 'gd_wallet_transactions',
                $wpdb->prefix . 'gd_wallet_security_log',
                $wpdb->prefix . 'gd_audit_log',
                $wpdb->prefix . 'gd_wallet_rate_limit',
                $wpdb->prefix . 'gd_wallet_blocked_ips',
                $wpdb->prefix . 'gd_gold_wallet_types',
                $wpdb->prefix . 'gd_user_metal_wallet',
                $wpdb->prefix . 'gd_metal_transactions',
            ];
            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS $table");
            }
            delete_option('gdb_db_version');
            delete_option('gdb_wallet_topup_product_id');
            delete_option('gdb_withdraw_min_amount');
            delete_option('gdb_withdraw_max_amount');
            delete_option('gdb_withdraw_fee_percent');
            delete_option('gdb_enable_admin_email');
            delete_option('gdb_enable_user_email');
            delete_option('gdb_uninstall_delete_tables');
            delete_option('gdb_security_rate_limit_enabled');
            delete_option('gdb_security_rate_limit_window');
            delete_option('gdb_security_rate_limit_max_attempts');
            delete_option('gdb_security_rate_limit_block_duration');
            delete_option('gdb_security_suspicious_amount_threshold');
            delete_option('gdb_security_require_verification_above');
            delete_option('gdb_security_alert_admin_on_suspicious');
            delete_option('gdb_security_max_daily_transactions');
            delete_option('gdb_security_max_transaction_amount');
            delete_option('gdb_security_log_all_transactions');
            delete_option('gdb_cashback_topup_enabled');
            delete_option('gdb_cashback_topup_percent');
            delete_option('gdb_cashback_gold_enabled');
            delete_option('gdb_cashback_gold_percent');
            delete_option('gdb_cashback_order_enabled');
            delete_option('gdb_cashback_order_percent');
        }
    }

    public function check_db_version()
    {
        if (get_option('gdb_db_version') !== self::$db_version) {
            $this->install();
        }
    }
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
