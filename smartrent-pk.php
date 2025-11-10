<?php
/**
 * Plugin Name: SmartRent PK – Compliance & Rental Management
 * Description: پاکستان میں کرایہ داری کے عمل کو ڈیجیٹل بنانا – ای-اسٹیمپ، ٹیننٹ رجسٹریشن، ادائیگی، WHT، اور قانونی کمپلائنس۔
 * Version: 1.0.0
 * Author: SmartRent Team
 * License: GPL2
 * Text Domain: smartrent-pk
 * Domain Path: /languages
 */

// 🟢 یہاں سے Core Plugin Setup شروع ہو رہا ہے
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'SSM_PLUGIN_SLUG', 'smartrent-pk' );
define( 'SSM_PLUGIN_VERSION', '1.0.0' );
define( 'SSM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * کور کلاسز کو شامل کریں
 */
require_once SSM_PLUGIN_DIR . 'includes/class-plugin-activator.php';
require_once SSM_PLUGIN_DIR . 'includes/class-plugin-loader.php';
require_once SSM_PLUGIN_DIR . 'includes/class-plugin-ajax.php';
require_once SSM_PLUGIN_DIR . 'includes/admin/class-dashboard.php'; // پہلا ماڈیول شامل

/**
 * پلگ اِن کو ایکٹیویٹ کریں
 * یہ فنکشن (DB) ٹیبلز اور کسٹم رولز کو شامل کرتا ہے۔
 */
function activate_smartrent_pk() {
    SmartRent_PK_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_smartrent_pk' );

/**
 * پلگ اِن کی تمام سروسز کو چالو کریں
 */
function run_smartrent_pk() {
    $loader = new SmartRent_PK_Loader();

    // ایڈمن ہکس: CSS/JS اور مینو رجسٹریشن
    $dashboard = new SmartRent_PK_Admin_Dashboard();
    $loader->add_action( 'admin_menu', $dashboard, 'register_admin_menu' );
    $loader->add_action( 'admin_enqueue_scripts', $dashboard, 'enqueue_styles_scripts' );

    // AJAX ہینڈلرز کو رجسٹر کریں (یہاں صرف ایک عام کلاس شامل ہے)
    $ajax_handler = new SmartRent_PK_Ajax();
    $loader->add_action( 'wp_ajax_ssm_load_dashboard', $ajax_handler, 'handle_load_dashboard' ); // ڈیش بورڈ کا AJAX ایکشن

    $loader->run();
}
run_smartrent_pk();
// 🔴 یہاں پر Core Plugin Setup ختم ہو رہا ہے
// ✅ Syntax verified block end.
