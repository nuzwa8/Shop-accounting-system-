
<?php
/**
 * Plugin Name:       Retail Shop Accounting & Management
 * Description:        Complete accounting, inventory, and management solution for retail/grocery store.
 * Version:           1.0.0
 * Author:            Nuzhat waseem
 * Author URI:        https://coachproai.com
 * Text Domain:       rsam-plugin
 * Domain Path:       /languages
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Direct access ko rokein
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version aur text domain set karein
define( 'RSAM_VERSION', '1.0.0' );
define( 'RSAM_TEXT_DOMAIN', 'rsam-plugin' );

/**
 * Part 1 — Plugin Activation (Database Tables aur Roles)
 */

/**
 * Plugin activate hone par chalne wala function.
 */
function rsam_activate_plugin() {
	// Database tables banayein
	rsam_create_database_tables();
	
	// User roles aur capabilities banayein
	rsam_create_roles();
	
	// Version number (database) mein save karein
	add_option( 'rsam_version', RSAM_VERSION );
}
register_activation_hook( __FILE__, 'rsam_activate_plugin' );

/**
 * Tamam custom table names ko manage karne ke liye helper function.
 *
 * @return array Table naamon ki list.
 */
function rsam_get_table_names() {
	global $wpdb;
	return array(
		'products'          => $wpdb->prefix . 'rsam_products',
		'product_batches'   => $wpdb->prefix . 'rsam_product_batches',
		'purchases'         => $wpdb->prefix . 'rsam_purchases',
		'purchase_items'    => $wpdb->prefix . 'rsam_purchase_items',
		'sales'             => $wpdb->prefix . 'rsam_sales',
		'sale_items'        => $wpdb->prefix . 'rsam_sale_items',
		'expenses'          => $wpdb->prefix . 'rsam_expenses',
		'employees'         => $wpdb->prefix . 'rsam_employees',
		'suppliers'         => $wpdb->prefix . 'rsam_suppliers',
		'customers'         => $wpdb->prefix . 'rsam_customers',
		'customer_payments' => $wpdb->prefix . 'rsam_customer_payments',
	);
}

/**
 * Plugin ke liye zaroori (Database) tables banata hai.
 */
function rsam_create_database_tables() {
	global $wpdb;
	$tables          = rsam_get_table_names();
	$charset_collate = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Products Table
	$sql_products = "CREATE TABLE {$tables['products']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		category VARCHAR(100) DEFAULT '' NOT NULL,
		unit_type VARCHAR(50) NOT NULL COMMENT 'e.g., kg, piece, liter',
		selling_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
		stock_quantity DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
		low_stock_threshold DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
		supplier_id BIGINT(20) UNSIGNED DEFAULT 0,
		has_expiry BOOLEAN NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY name (name),
		KEY category (category)
	) $charset_collate;";
	dbDelta( $sql_products );

	// Product Batches Table
	$sql_product_batches = "CREATE TABLE {$tables['product_batches']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		product_id BIGINT(20) UNSIGNED NOT NULL,
		purchase_id BIGINT(20) UNSIGNED DEFAULT 0,
		batch_code VARCHAR(100) DEFAULT '' NOT NULL,
		quantity_received DECIMAL(10, 2) NOT NULL,
		quantity_in_stock DECIMAL(10, 2) NOT NULL,
		purchase_price DECIMAL(10, 2) NOT NULL COMMENT 'Base purchase price',
		cost_price DECIMAL(10, 2) NOT NULL COMMENT 'Purchase price + distributed costs',
		expiry_date DATE DEFAULT NULL,
		received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY product_id (product_id),
		KEY purchase_id (purchase_id)
	) $charset_collate;";
	dbDelta( $sql_product_batches );

	// Purchases Table
	$sql_purchases = "CREATE TABLE {$tables['purchases']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		supplier_id BIGINT(20) UNSIGNED DEFAULT 0,
		invoice_number VARCHAR(100) DEFAULT '' NOT NULL,
		purchase_date DATE NOT NULL,
		subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
		additional_costs DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Transportation, tax, etc.',
		total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
		notes TEXT,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY supplier_id (supplier_id),
		KEY purchase_date (purchase_date)
	) $charset_collate;";
	dbDelta( $sql_purchases );

	// Purchase Items Table
	$sql_purchase_items = "CREATE TABLE {$tables['purchase_items']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		purchase_id BIGINT(20) UNSIGNED NOT NULL,
		product_id BIGINT(20) UNSIGNED NOT NULL,
		batch_id BIGINT(20) UNSIGNED NOT NULL,
		quantity DECIMAL(10, 2) NOT NULL,
		purchase_price DECIMAL(10, 2) NOT NULL,
		item_subtotal DECIMAL(10, 2) NOT NULL,
		PRIMARY KEY  (id),
		KEY purchase_id (purchase_id),
		KEY product_id (product_id),
		KEY batch_id (batch_id)
	) $charset_collate;";
	dbDelta( $sql_purchase_items );

	// Sales Table
	$sql_sales = "CREATE TABLE {$tables['sales']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		customer_id BIGINT(20) UNSIGNED DEFAULT 0,
		sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		subtotal DECIMAL(10, 2) NOT NULL,
		discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
		total_amount DECIMAL(10, 2) NOT NULL,
		total_cost DECIMAL(10, 2) NOT NULL COMMENT 'Total cost of goods sold',
		total_profit DECIMAL(10, 2) NOT NULL,
		payment_status VARCHAR(20) NOT NULL DEFAULT 'paid' COMMENT 'paid, unpaid, partial',
		notes TEXT,
		PRIMARY KEY  (id),
		KEY customer_id (customer_id),
		KEY sale_date (sale_date)
	) $charset_collate;";
	dbDelta( $sql_sales );

	// Sale Items Table
	$sql_sale_items = "CREATE TABLE {$tables['sale_items']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		sale_id BIGINT(20) UNSIGNED NOT NULL,
		product_id BIGINT(20) UNSIGNED NOT NULL,
		batch_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'Kis batch se farokht hua (FIFO)',
		quantity DECIMAL(10, 2) NOT NULL,
		selling_price DECIMAL(10, 2) NOT NULL,
		cost_price DECIMAL(10, 2) NOT NULL COMMENT 'Cost price from the batch',
		item_profit DECIMAL(10, 2) NOT NULL,
		PRIMARY KEY  (id),
		KEY sale_id (sale_id),
		KEY product_id (product_id)
	) $charset_collate;";
	dbDelta( $sql_sale_items );

	// Expenses Table
	$sql_expenses = "CREATE TABLE {$tables['expenses']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		expense_date DATE NOT NULL,
		category VARCHAR(100) NOT NULL COMMENT 'e.g., rent, utility, salary, maintenance',
		amount DECIMAL(10, 2) NOT NULL,
		description TEXT,
		employee_id BIGINT(20) UNSIGNED DEFAULT 0 COMMENT 'Agar salary hai',
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY expense_date (expense_date),
		KEY category (category)
	) $charset_collate;";
	dbDelta( $sql_expenses );

	// Employees Table
	$sql_employees = "CREATE TABLE {$tables['employees']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		phone VARCHAR(50) DEFAULT '' NOT NULL,
		designation VARCHAR(100) DEFAULT '' NOT NULL,
		monthly_salary DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
		joining_date DATE,
		is_active BOOLEAN NOT NULL DEFAULT 1,
		PRIMARY KEY  (id)
	) $charset_collate;";
	dbDelta( $sql_employees );

	// Suppliers Table
	$sql_suppliers = "CREATE TABLE {$tables['suppliers']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		phone VARCHAR(50) DEFAULT '' NOT NULL,
		address TEXT,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) $charset_collate;";
	dbDelta( $sql_suppliers );

	// Customers Table
	$sql_customers = "CREATE TABLE {$tables['customers']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		phone VARCHAR(50) DEFAULT '' NOT NULL,
		address TEXT,
		credit_balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) $charset_collate;";
	dbDelta( $sql_customers );

	// Customer Payments Table (FIXED: Added missing table)
	$sql_customer_payments = "CREATE TABLE {$tables['customer_payments']} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		customer_id BIGINT(20) UNSIGNED NOT NULL,
		payment_date DATE NOT NULL,
		amount DECIMAL(10, 2) NOT NULL,
		notes TEXT,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY customer_id (customer_id),
		KEY payment_date (payment_date)
	) $charset_collate;";
	dbDelta( $sql_customer_payments );
}

/**
 * Custom roles aur (capabilities) banata hai.
 */
function rsam_create_roles() {
	// 'Shop Staff' role
	add_role(
		'shop_staff',
		__( 'Shop Staff', RSAM_TEXT_DOMAIN ),
		array(
			'read'                   => true,
			'rsam_view_dashboard'    => true,
			'rsam_manage_products'   => true,
			'rsam_manage_purchases'  => true,
			'rsam_manage_sales'      => true,
			'rsam_manage_customers'  => true,
			'rsam_manage_suppliers'  => true,
		)
	);

	// 'Administrator' role ko (permissions) dein
	$admin_role = get_role( 'administrator' );
	if ( $admin_role ) {
		$admin_role->add_cap( 'rsam_view_dashboard', true );
		$admin_role->add_cap( 'rsam_manage_products', true );
		$admin_role->add_cap( 'rsam_manage_purchases', true );
		$admin_role->add_cap( 'rsam_manage_sales', true );
		$admin_role->add_cap( 'rsam_manage_expenses', true );
		$admin_role->add_cap( 'rsam_manage_employees', true );
		$admin_role->add_cap( 'rsam_manage_customers', true );
		$admin_role->add_cap( 'rsam_manage_suppliers', true );
		$admin_role->add_cap( 'rsam_view_reports', true );
		$admin_role->add_cap( 'rsam_manage_settings', true );
	}
}

/**
 * Plugin (translations) load karein.
 */
function rsam_load_textdomain() {
	load_plugin_textdomain( RSAM_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'rsam_load_textdomain' );

/**
 * Deactivation par roles hata dein (safai ke liye)
 */
function rsam_deactivate_plugin() {
	remove_role( 'shop_staff' );
	
	// Admin se bhi (capabilities) hata dein
	$admin_role = get_role( 'administrator' );
	if ( $admin_role ) {
		$admin_role->remove_cap( 'rsam_view_dashboard' );
		$admin_role->remove_cap( 'rsam_manage_products' );
		$admin_role->remove_cap( 'rsam_manage_purchases' );
		$admin_role->remove_cap( 'rsam_manage_sales' );
		$admin_role->remove_cap( 'rsam_manage_expenses' );
		$admin_role->remove_cap( 'rsam_manage_employees' );
		$admin_role->remove_cap( 'rsam_manage_customers' );
		$admin_role->remove_cap( 'rsam_manage_suppliers' );
		$admin_role->remove_cap( 'rsam_view_reports' );
		$admin_role->remove_cap( 'rsam_manage_settings' );
	}
}
register_deactivation_hook( __FILE__, 'rsam_deactivate_plugin' );

/**
 * Part 2 — Admin Menus, Enqueue Scripts, aur (AJAX) Data
 */

/**
 * (WordPress) Admin mein (Menu) pages banata hai.
 */
function rsam_admin_menus() {
	// Top-level menu
	add_menu_page(
		__( 'Shop Management', RSAM_TEXT_DOMAIN ),
		__( 'Shop Management', RSAM_TEXT_DOMAIN ),
		'rsam_view_dashboard',
		'rsam-dashboard',
		'rsam_render_admin_page',
		'dashicons-store',
		25
	);

	// Dashboard (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Dashboard', RSAM_TEXT_DOMAIN ),
		__( 'Dashboard', RSAM_TEXT_DOMAIN ),
		'rsam_view_dashboard',
		'rsam-dashboard',
		'rsam_render_admin_page'
	);

	// Products (Inventory) (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Products (Inventory)', RSAM_TEXT_DOMAIN ),
		__( 'Products', RSAM_TEXT_DOMAIN ),
		'rsam_manage_products',
		'rsam-products',
		'rsam_render_admin_page'
	);

	// Purchases (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Purchases', RSAM_TEXT_DOMAIN ),
		__( 'Purchases', RSAM_TEXT_DOMAIN ),
		'rsam_manage_purchases',
		'rsam-purchases',
		'rsam_render_admin_page'
	);

	// Sales (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Sales', RSAM_TEXT_DOMAIN ),
		__( 'Sales', RSAM_TEXT_DOMAIN ),
		'rsam_manage_sales',
		'rsam-sales',
		'rsam_render_admin_page'
	);

	// Expenses (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Expenses', RSAM_TEXT_DOMAIN ),
		__( 'Expenses', RSAM_TEXT_DOMAIN ),
		'rsam_manage_expenses',
		'rsam-expenses',
		'rsam_render_admin_page'
	);

	// Employees (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Employees', RSAM_TEXT_DOMAIN ),
		__( 'Employees', RSAM_TEXT_DOMAIN ),
		'rsam_manage_employees',
		'rsam-employees',
		'rsam_render_admin_page'
	);
	
	// Suppliers (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Suppliers', RSAM_TEXT_DOMAIN ),
		__( 'Suppliers', RSAM_TEXT_DOMAIN ),
		'rsam_manage_suppliers',
		'rsam-suppliers',
		'rsam_render_admin_page'
	);

	// Customers (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Customers (Khata)', RSAM_TEXT_DOMAIN ),
		__( 'Customers', RSAM_TEXT_DOMAIN ),
		'rsam_manage_customers',
		'rsam-customers',
		'rsam_render_admin_page'
	);

	// Reports (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Reports', RSAM_TEXT_DOMAIN ),
		__( 'Reports', RSAM_TEXT_DOMAIN ),
		'rsam_view_reports',
		'rsam-reports',
		'rsam_render_admin_page'
	);

	// Settings (Sub-menu)
	add_submenu_page(
		'rsam-dashboard',
		__( 'Settings', RSAM_TEXT_DOMAIN ),
		__( 'Settings', RSAM_TEXT_DOMAIN ),
		'rsam_manage_settings',
		'rsam-settings',
		'rsam_render_admin_page'
	);
}
add_action( 'admin_menu', 'rsam_admin_menus' );

/**
 * (JavaScript) aur (CSS) files ko (enqueue) karta hai.
 */
function rsam_admin_enqueue_scripts( $hook_suffix ) {
	// Sirf hamare plugin pages par (assets) load karein
	if ( strpos( $hook_suffix, 'rsam-' ) === false ) {
		return;
	}

	$plugin_url = plugin_dir_url( __FILE__ );
	$version    = RSAM_VERSION;

	// (CSS) file
	wp_enqueue_style(
		'rsam-admin-style',
		$plugin_url . 'assets/rsam-admin.css',
		array(),
		$version
	);

	// (JavaScript) file
	wp_enqueue_script(
		'rsam-admin-script',
		$plugin_url . 'assets/rsam-admin.js',
		array( 'jquery', 'jquery-ui-autocomplete' ),
		$version,
		true
	);

	// (JavaScript) ko data bhejein (wp_localize_script)
	rsam_localize_script_data();
}
add_action( 'admin_enqueue_scripts', 'rsam_admin_enqueue_scripts' );

/**
 * (JavaScript) ke liye zaroori (PHP) data (localize) karta hai.
 */
function rsam_localize_script_data() {
	$current_user = wp_get_current_user();
	
	// User ki (capabilities) ka (map)
	$user_caps = array(
		'canManageProducts'  => current_user_can( 'rsam_manage_products' ),
		'canManagePurchases' => current_user_can( 'rsam_manage_purchases' ),
		'canManageSales'     => current_user_can( 'rsam_manage_sales' ),
		'canManageExpenses'  => current_user_can( 'rsam_manage_expenses' ),
		'canManageEmployees' => current_user_can( 'rsam_manage_employees' ),
		'canManageSuppliers' => current_user_can( 'rsam_manage_suppliers' ),
		'canManageCustomers' => current_user_can( 'rsam_manage_customers' ),
		'canViewReports'     => current_user_can( 'rsam_view_reports' ),
		'canManageSettings'  => current_user_can( 'rsam_manage_settings' ),
	);

	wp_localize_script(
		'rsam-admin-script',
		'rsamData',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'rsam-ajax-nonce' ),
			'caps'     => $user_caps,
			'strings'  => array(
				'loading'              => __( 'Loading...', RSAM_TEXT_DOMAIN ),
				'errorOccurred'        => __( 'An error occurred. Please try again.', RSAM_TEXT_DOMAIN ),
				'confirmDelete'        => __( 'Are you sure you want to delete this?', RSAM_TEXT_DOMAIN ),
				'invalidInput'         => __( 'Please check your inputs.', RSAM_TEXT_DOMAIN ),
				'processing'           => __( 'Processing...', RSAM_TEXT_DOMAIN ),
				'itemSaved'            => __( 'Item saved successfully.', RSAM_TEXT_DOMAIN ),
				'itemDeleted'          => __( 'Item deleted successfully.', RSAM_TEXT_DOMAIN ),
				'noItemsFound'         => __( 'No items found.', RSAM_TEXT_DOMAIN ),
				'addNew'               => __( 'Add New', RSAM_TEXT_DOMAIN ),
				'edit'                 => __( 'Edit', RSAM_TEXT_DOMAIN ),
				'delete'               => __( 'Delete', RSAM_TEXT_DOMAIN ),
				'save'                 => __( 'Save', RSAM_TEXT_DOMAIN ),
				'cancel'               => __( 'Cancel', RSAM_TEXT_DOMAIN ),
				'close'                => __( 'Close', RSAM_TEXT_DOMAIN ),
			),
		)
	);
}

/**
 * Admin pages ko (render) karne ke liye bunyadi (callback) function.
 */
function rsam_render_admin_page() {
	$screen_slug = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'rsam-dashboard';
	$screen_name = str_replace( 'rsam-', '', $screen_slug );
	?>
	<div class="wrap">
		<div id="rsam-<?php echo esc_attr( $screen_name ); ?>-root" class="rsam-root" data-screen="<?php echo esc_attr( $screen_name ); ?>">
			<div class="rsam-loading-placeholder">
				<h2><?php esc_html_e( 'Shop Management', RSAM_TEXT_DOMAIN ); ?></h2>
				<p><?php esc_html_e( 'Loading...', RSAM_TEXT_DOMAIN ); ?></p>
			</div>
		</div>
	</div>
	<?php
	rsam_include_all_templates();
}

/**
 * (JavaScript) ke istemal ke liye tamam (HTML <template>) blocks ko (footer) mein (print) karta hai.
 */
function rsam_include_all_templates() {
	rsam_template_dashboard();
	rsam_template_products();
	rsam_template_purchases();
	rsam_template_sales();
	rsam_template_expenses();
	rsam_template_employees();
	rsam_template_suppliers();
	rsam_template_customers();
	rsam_template_reports();
	rsam_template_settings();
	rsam_template_common_ui();
}

/**
 * Part 3 — Templates (Dashboard, Common UI) aur (AJAX) Handlers
 */

/**
 * Dashboard Screen ke liye (HTML <template>)
 */
function rsam_template_dashboard() {
	?>
	<template id="rsam-tmpl-dashboard">
		<div class="rsam-screen-header">
			<h1><?php esc_html_e( 'Dashboard', RSAM_TEXT_DOMAIN ); ?></h1>
		</div>
		<div class="rsam-dashboard-widgets">
			
			<div class="rsam-widget rsam-widget-loading" data-widget="stats">
				<div class="rsam-widget-header">
					<h3><?php esc_html_e( 'Shop Overview', RSAM_TEXT_DOMAIN ); ?></h3>
				</div>
				<div class="rsam-widget-body">
					<p><?php esc_html_e( 'Loading stats...', RSAM_TEXT_DOMAIN ); ?></p>
				</div>
			</div>

			<div class="rsam-widget" data-widget="quick-links">
				<div class="rsam-widget-header">
					<h3><?php esc_html_e( 'Quick Actions', RSAM_TEXT_DOMAIN ); ?></h3>
				</div>
				<div class="rsam-widget-body rsam-quick-links">
					<?php if ( current_user_can( 'rsam_manage_sales' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rsam-sales' ) ); ?>" class="button button-primary button-hero">
							<span class="dashicons dashicons-cart"></span>
							<?php esc_html_e( 'New Sale', RSAM_TEXT_DOMAIN ); ?>
						</a>
					<?php endif; ?>
					<?php if ( current_user_can( 'rsam_manage_purchases' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rsam-purchases' ) ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-archive"></span>
							<?php esc_html_e( 'New Purchase', RSAM_TEXT_DOMAIN ); ?>
						</a>
					<?php endif; ?>
					<?php if ( current_user_can( 'rsam_manage_products' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rsam-products' ) ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-package"></span>
							<?php esc_html_e( 'Add Product', RSAM_TEXT_DOMAIN ); ?>
						</a>
					<?php endif; ?>
					<?php if ( current_user_can( 'rsam_manage_expenses' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rsam-expenses' ) ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-money-alt"></span>
							<?php esc_html_e( 'Add Expense', RSAM_TEXT_DOMAIN ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( current_user_can( 'rsam_manage_products' ) ) : ?>
			<div class="rsam-widget rsam-widget-loading" data-widget="top-products">
				<div class="rsam-widget-header">
					<h3><?php esc_html_e( 'Top Selling Products (This Month)', RSAM_TEXT_DOMAIN ); ?></h3>
				</div>
				<div class="rsam-widget-body">
					<p><?php esc_html_e( 'Loading...', RSAM_TEXT_DOMAIN ); ?></p>
				</div>
			</div>

			<div class="rsam-widget rsam-widget-loading" data-widget="low-stock">
				<div class="rsam-widget-header">
					<h3><?php esc_html_e( 'Low Stock Alerts', RSAM_TEXT_DOMAIN ); ?></h3>
				</div>
				<div class="rsam-widget-body">
					<p><?php esc_html_e( 'Loading...', RSAM_TEXT_DOMAIN ); ?></p>
				</div>
			</div>
			<?php endif; ?>

		</div>
	</template>
	<?php
}

/**
 * (Common UI) ke liye (Templates) - Maslan (Modal/Drawer) forms.
 */
function rsam_template_common_ui() {
	?>
	<template id="rsam-tmpl-modal-form">
		<div class="rsam-modal-backdrop"></div>
		<div class="rsam-modal-wrapper">
			<div class="rsam-modal-content">
				<div class="rsam-modal-header">
					<h3 class="rsam-modal-title"></h3>
					<button type="button" class="rsam-modal-close dashicons dashicons-no-alt"></button>
				</div>
				<div class="rsam-modal-body">
				</div>
				<div class="rsam-modal-footer">
					<button type="button" class="button rsam-modal-cancel"><?php esc_html_e( 'Cancel', RSAM_TEXT_DOMAIN ); ?></button>
					<button type="button" class="button button-primary rsam-modal-save">
						<span class="rsam-btn-text"><?php esc_html_e( 'Save', RSAM_TEXT_DOMAIN ); ?></span>
						<span class="rsam-loader-spinner"></span>
					</button>
				</div>
			</div>
		</div>
	</template>

	<template id="rsam-tmpl-modal-confirm">
		<div class="rsam-modal-backdrop"></div>
		<div class="rsam-modal-wrapper rsam-modal-confirm">
			<div class="rsam-modal-content">
				<div class="rsam-modal-header">
					<h3 class="rsam-modal-title"><?php esc_html_e( 'Are you sure?', RSAM_TEXT_DOMAIN ); ?></h3>
					<button type="button" class="rsam-modal-close dashicons dash
icons-no-alt"></button>
				</div>
				<div class="rsam-modal-body">
					<p class="rsam-confirm-text"><?php esc_html_e( 'Are you sure you want to delete this item? This action cannot be undone.', RSAM_TEXT_DOMAIN ); ?></p>
				</div>
				<div class="rsam-modal-footer">
					<button type="button" class="button rsam-modal-cancel"><?php esc_html_e( 'Cancel', RSAM_TEXT_DOMAIN ); ?></button>
					<button type="button" class="button button-danger rsam-modal-confirm-delete">
						<span class="rsam-btn-text"><?php esc_html_e( 'Delete', RSAM_TEXT_DOMAIN ); ?></span>
						<span class="rsam-loader-spinner"></span>
					</button>
				</div>
			</div>
		</div>
	</template>
	<?php
}

/**
 * (AJAX) Handler: Dashboard (Stats) hasil karne ke liye.
 */
function rsam_ajax_get_dashboard_stats() {
	check_ajax_referer( 'rsam-ajax-nonce', 'nonce' );
	if ( ! current_user_can( 'rsam_view_dashboard' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to view this data.', RSAM_TEXT_DOMAIN ) ), 403 );
	}

	global $wpdb;
	$tables = rsam_get_table_names();

	$today_start = wp_date( 'Y-m-d 00:00:00' );
	$month_start = wp_date( 'Y-m-01 00:00:00' );

	$today_sales = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(total_amount) FROM {$tables['sales']} WHERE sale_date >= %s",
		$today_start
	) );

	$monthly_sales = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(total_amount) FROM {$tables['sales']} WHERE sale_date >= %s",
		$month_start
	) );

	$monthly_profit = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(total_profit) FROM {$tables['sales']} WHERE sale_date >= %s",
		$month_start
	) );
	
	$monthly_expenses = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(amount) FROM {$tables['expenses']} WHERE expense_date >= %s",
		wp_date( 'Y-m-01' )
	) );

	$stock_value = $wpdb->get_var(
		"SELECT SUM(cost_price * quantity_in_stock) 
		 FROM {$tables['product_batches']} 
		 WHERE quantity_in_stock > 0"
	);

	$low_stock_count = $wpdb->get_var(
		"SELECT COUNT(id) 
		 FROM {$tables['products']} 
		 WHERE stock_quantity <= low_stock_threshold AND low_stock_threshold > 0"
	);

	$top_products = $wpdb->get_results( $wpdb->prepare(
		"SELECT p.name, SUM(si.quantity) as total_quantity
		 FROM {$tables['sale_items']} si
		 JOIN {$tables['sales']} s ON s.id = si.sale_id
		 JOIN {$tables['products']} p ON p.id = si.product_id
		 WHERE s.sale_date >= %s
		 GROUP BY si.product_id
		 ORDER BY total_quantity DESC
		 LIMIT 5",
		 $month_start
	) );

	$low_stock_products = $wpdb->get_results(
		"SELECT name, stock_quantity, low_stock_threshold 
		 FROM {$tables['products']} 
		 WHERE stock_quantity <= low_stock_threshold AND low_stock_threshold > 0
		 ORDER BY (stock_quantity - low_stock_threshold) ASC
		 LIMIT 5"
	);

	$stats = array(
		'today_sales'      => rsam_format_price( $today_sales ),
		'monthly_sales'    => rsam_format_price( $monthly_sales ),
		'monthly_profit'   => rsam_format_price( $monthly_profit ),
		'monthly_expenses' => rsam_format_price( $monthly_expenses ),
		'stock_value'      => rsam_format_price( $stock_value ),
		'low_stock_count'  => (int) $low_stock_count,
		'top_products'     => $top_products,
		'low_stock_products' => $low_stock_products,
	);

	wp_send_json_success( $stats );
}
add_action( 'wp_ajax_rsam_get_dashboard_stats', 'rsam_ajax_get_dashboard_stats' );

/**
 * Raqam (Price) ko format karne ke liye helper function.
 */
function rsam_format_price( $price ) {
	$currency_symbol = __( 'Rs.', RSAM_TEXT_DOMAIN );
	$price = (float) $price;
	return $currency_symbol . ' ' . number_format( $price, 2 );
}

/**
 * Part 4 — Products (Inventory) (Templates + AJAX)
 */

/**
 * Products (Inventory) Screen ke liye (HTML <template>)
 */
function rsam_template_products() {
	?>
	<template id="rsam-tmpl-products">
		<div class="rsam-screen-header">
			<h1><?php esc_html_e( 'Products (Inventory)', RSAM_TEXT_DOMAIN ); ?></h1>
			<button type="button" class="button button-primary" id="rsam-add-new-product">
				<span class="dashicons dashicons-plus-alt"></span>
				<?php esc_html_e( 'Add New Product', RSAM_TEXT_DOMAIN ); ?>
			</button>
		</div>

		<div class="rsam-list-controls">
			<input type="search" id="rsam-product-search" class="rsam-search-field" placeholder="<?php esc_attr_e( 'Search by product name...', RSAM_TEXT_DOMAIN ); ?>">
		</div>

		<div class="rsam-list-table-wrapper">
			<table class="rsam-list-table" id="rsam-products-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product Name', RSAM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Category', RSAM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Unit', RSAM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Stock Qty', RSAM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Stock Value (Cost)', RSAM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Selling Price', RSAM_TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Actions', RSAM_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody id="rsam-products-table-body">
					<tr>
						<td colspan="7" class="rsam-list-loading">
							<span class="rsam-loader-spinner"></span> <?php esc_html_e( 'Loading products...', RSAM_TEXT_DOMAIN ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="rsam-pagination" id="rsam-products-pagination">
		</div>

		<div id="rsam-product-form-container" style="display: none;">
			<form id="rsam-product-form" class="rsam-form">
				<input type="hidden" name="product_id" value="0">
				
				<div class="rsam-form-field">
					<label for="rsam-product-name"><?php esc_html_e( 'Product Name', RSAM_TEXT_DOMAIN ); ?> <span class="rsam-required">*</span></label>
					<input type="text" id="rsam-product-name" name="name" required>
				</div>

				<div class="rsam-form-grid">
					<div class="rsam-form-field">
						<label for="rsam-product-category"><?php esc_html_e( 'Category', RSAM_TEXT_DOMAIN ); ?></label>
						<input type="text" id="rsam-product-category" name="category" placeholder="<?php esc_attr_e( 'e.g., Pulses, Soaps', RSAM_TEXT_DOMAIN ); ?>">
					</div>
					<div class="rsam-form-field">
						<label for="rsam-product-unit"><?php esc_html_e( 'Unit Type', RSAM_TEXT_DOMAIN ); ?> <span class="rsam-required">*</span></label>
						<input type="text" id="rsam-product-unit" name="unit_type" required placeholder="<?php esc_attr_e( 'e.g., kg, piece, liter', RSAM_TEXT_DOMAIN ); ?>">
					</div>
				</div>

				<div class="rsam-form-grid">
					<div class="rsam-form-field">
						<label for="rsam-product-selling-price"><?php esc_html_e( 'Selling Price', RSAM_TEXT_DOMAIN ); ?> <span class="rsam-required">*</span></label>
						<input type="number" id="rsam-product-selling-price" name="selling_price" step="0.01" min="0" required>
					</div>
					<div class="rsam-form-field">
						<label for="rsam-product-low-stock"><?php esc_html_e( 'Low Stock Threshold', RSAM_TEXT_DOMAIN ); ?></label>
						<input type="number" id="rsam-product-low-stock" name="low_stock_threshold" step="1" min="0" placeholder="<?php esc_attr_e( 'e.g., 5', RSAM_TEXT_DOMAIN ); ?>">
					</div>
				</div>

				<div class="rsam-form-field">
					<label>
						<input type="checkbox" name="has_expiry" id="rsam-product-has-expiry" value="1">
						<?php esc_html_e( 'This product has an expiry date (e.g., bread, milk)', RSAM_TEXT_DOMAIN ); ?>
					</label>
				</div>

				<p class="rsam-form-note"><?php esc_html_e( 'Note: Stock Quantity and Purchase Price are managed from the "Purchases" screen.', RSAM_TEXT_DOMAIN ); ?></p>
			</form>
		</div>
	</template>
	<?php
}

/**
 * (AJAX) Handler: Products ki list (fetch) karne ke liye.
 */
function rsam_ajax_get_products() {
	check_ajax_referer( 'rsam-ajax-nonce', 'nonce' );
	if ( ! current_user_can( 'rsam_manage_products' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission.', RSAM_TEXT_DOMAIN ) ), 403 );
	}

	global $wpdb;
	$tables = rsam_get_table_names();

	$page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
	$limit = 20;
	$offset = ( $page - 1 ) * $limit;

	$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

	$where_clause = '';
	$params = array();
	if ( ! empty( $search ) ) {
		$where_clause = ' WHERE p.name LIKE %s';
		$params[] = '%' . $wpdb->esc_like( $search ) . '%';
	}

	$count_query = "SELECT COUNT(p.id) FROM {$tables['products']} p $where_clause";
	$total_items = $wpdb->get_var( 
		empty( $params ) ? $count_query : $wpdb->prepare( $count_query, $params )
	);

	$query_parts = array(
		"SELECT 
			p.id, 
			p.name, 
			p.category, 
			p.unit_type, 
			p.selling_price, 
			p.stock_quantity,
			p.low_stock_threshold,
			p.has_expiry,
			COALESCE(SUM(b.cost_price * b.quantity_in_stock), 0) as stock_value
		FROM 
			{$tables['products']} p
		LEFT JOIN 
			{$tables['product_batches']} b ON p.id = b.product_id AND b.quantity_in_stock > 0",
		$where_clause,
		"GROUP BY p.id
		ORDER BY p.name ASC
		LIMIT %d
		OFFSET %d"
	);
	
	$query = implode( ' ', $query_parts );
	$query_params = array_merge( $params, array( $limit, $offset ) );
	$products = $wpdb->get_results( $wpdb->prepare( $query, $query_params ) );
	
	foreach ( $products as $product ) {
		$product->stock_quantity = (float) $product->stock_quantity;
		$product->selling_price_formatted = rsam_format_price( $product->selling_price );
		$product->stock_value_formatted = rsam_format_price( $product->stock_value );
	}

	wp_send_json_success( array(
		'products'   => $products,
		'pagination' => array(
			'total_items' => (int) $total_items,
			'total_pages' => ceil( $total_items / $limit ),
			'current_page' => $page,
		),
	) );
}
add_action( 'wp_ajax_rsam_get_products', 'rsam_ajax_get_products' );

/**
 * (AJAX) Handler: Naya (Product) (save) ya (update) karne ke liye.
 */
function rsam_ajax_save_product() {
	check_ajax_referer( 'rsam-ajax-nonce', 'nonce' );
	if ( ! current_user_can( 'rsam_manage_products' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission.', RSAM_TEXT_DOMAIN ) ), 403 );
	}

	parse_str( $_POST['form_data'], $data );

	$product_id = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0;
	$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
	$category = isset( $data['category'] ) ? sanitize_text_field( $data['category'] ) : '';
	$unit_type = isset( $data['unit_type'] ) ? sanitize_key( $data['unit_type'] ) : '';
	$selling_price = isset( $data['selling_price'] ) ? floatval( $data['selling_price'] ) : 0;
	$low_stock_threshold = isset( $data['low_stock_threshold'] ) ? floatval( $data['low_stock_threshold'] ) : 0;
	$has_expiry = isset( $data['has_expiry'] ) ? 1 : 0;

	if ( empty( $name ) || empty( $unit_type ) || $selling_price < 0 ) {
		wp_send_json_error( array( 'message' => __( 'Please fill all required fields correctly.', RSAM_TEXT_DOMAIN ) ), 400 );
	}

	global $wpdb;
	$tables = rsam_get_table_names();

	$db_data = array(
		'name'                => $name,
		'category'            => $category,
		'unit_type'           => $unit_type,
		'selling_price'       => $selling_price,
		'low_stock_threshold' => $low_stock_threshold,
		'has_expiry'          => $has_expiry,
	);
	
	$db_formats = array( '%s', '%s', '%s', '%f', '%f', '%d' );

	if ( $product_id > 0 ) {
		$result = $wpdb->update(
			$tables['products'],
			$db_data,
			array( 'id' => $product_id ),
			$db_formats,
			array( '%d' )
		);
		$message = __( 'Product updated successfully.', RSAM_TEXT_DOMAIN );
	} else {
		$db_data['stock_quantity'] = 0;
		$db_formats[] = '%f';

		$result = $wpdb->insert(
			$tables['products'],
			$db_data,
			$db_formats
		);
		$product_id = $wpdb->insert_id;
		$message = __( 'Product added successfully.', RSAM_TEXT_DOMAIN );
	}

	if ( $result === false ) {
		wp_send_json_error( array( 'message' => __( 'Database error. Could not save product.', RSAM_TEXT_DOMAIN ) ), 500 );
	}

	wp_send_json_success( array( 'message' => $message, 'product_id' => $product_id ) );
}
add_action( 'wp_ajax_rsam_save_product', 'rsam_ajax_save_product' );

/**
 * (AJAX) Handler: (Product) (delete) karne ke liye.
 */
function rsam_ajax_delete_product() {
	check_ajax_referer( 'rsam-ajax-nonce', 'nonce' );
	if ( ! current_user_can( 'rsam_manage_products' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission.', RSAM_TEXT_DOMAIN ) ), 403 );
	}

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

	if ( $product_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid Product ID.', RSAM_TEXT_DOMAIN ) ), 400 );
	}

	global $wpdb;
	$tables = rsam_get_table_names();
	
	$stock = $wpdb->get_var( $wpdb->prepare(
		"SELECT stock_quantity FROM {$tables['products']} WHERE id = %d", $product_id
	) );
	
	if ( (float) $stock > 0 ) {
		wp_send_json_error( array( 'message' => __( 'Cannot delete. This product still has stock. Please adjust stock to 0 first.', RSAM_TEXT_DOMAIN ) ), 400 );
	}

	$wpdb->delete( $tables['product_batches'], array( 'product_id' => $product_id ), array( '%d' ) );

	$result = $wpdb->delete( $tables['products'], array( 'id' => $product_id ), array( '%d' ) );

	if ( $result ) {
		wp_send_json_success( array( 'message' => __( 'Product deleted successfully.', RSAM_TEXT_DOMAIN ) ) );
	} else {
		wp_send_json_error( array( 'message' => __( 'Could not delete product.', RSAM_TEXT_DOMAIN ) ), 500 );
	}
}
add_action( 'wp_ajax_rsam_delete_product', 'rsam_ajax_delete_product' );

/**
 * (AJAX) Handler: (Sales/Purchases) forms ke liye (products) (search) karna.
 */
function rsam_ajax_search_products() {
	check_ajax_referer( 'rsam-ajax-nonce', 'nonce' );
	if ( ! current_user_can( 'rsam_manage_sales' ) && ! current_user_can( 'rsam_manage_purchases' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission.', RSAM_TEXT_DOMAIN ) ), 403 );
	}

	global $wpdb;
	$tables = rsam_get_table_names();
	
	$search = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

	if ( empty( $search ) ) {
		wp_send_json_success( array() );
	}

	$query = $wpdb->prepare(
		"SELECT id, name, unit_type, selling_price, has_expiry, stock_quantity
		 FROM {$tables['products']} 
		 WHERE name LIKE %s 
		 LIMIT 15",
		'%' . $wpdb->esc_like( $search ) . '%'
	);
	
	$products = $wpdb->get_results( $query );

	$results = array();
	foreach ( $products as $product ) {
		$results[] = array(
			'id'    => $product->id,
			'label' => sprintf(
				'%s (%s) - %s',
				$product->name,
				$product->unit_type,
				rsam_format_price( $product->selling_price )
			),
			'value' => $product->name,
			'data'  => $product,
		);
	}
	
	wp_send_json_success( $results );
}
add_action( 'wp_ajax_rsam_search_products', 'rsam_ajax_search_products' );


	
