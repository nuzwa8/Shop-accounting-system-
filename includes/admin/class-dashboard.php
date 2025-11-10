<?php
/**
 * SmartRent PK Dashboard Admin Page Class
 * مرکزی ڈیش بورڈ پیج کو ہینڈل کرتی ہے۔
 */

// 🟢 یہاں سے Dashboard Class شروع ہو رہا ہے
class SmartRent_PK_Admin_Dashboard {

    private $page_slug = 'ssm-dashboard';
    private $root_id = 'ssm-dashboard-root';
    private $ajax_action = 'ssm_load_dashboard';
    private $nonce_key = 'ssm_dashboard_nonce';

    /**
     * ایڈمن مینو میں نیا مینو آئٹم رجسٹر کریں
     */
    public function register_admin_menu() {
        add_menu_page(
            esc_html__( 'SmartRent PK', 'smartrent-pk' ),
            esc_html__( 'SmartRent PK', 'smartrent-pk' ),
            'ssm_access_admin',
            $this->page_slug,
            [ $this, 'render_dashboard_page' ],
            'dashicons-admin-home',
            20
        );
    }

    /**
     * ڈیش بورڈ پیج کا (HTML) ٹیمپلیٹ render کرتا ہے۔
     */
    public function render_dashboard_page() {
        // صلاحیت کی تصدیق
        if ( ! current_user_can( 'ssm_access_admin' ) ) {
            wp_die( esc_html__( 'آپ کے پاس اس صفحے تک رسائی کی اجازت نہیں ہے۔', 'smartrent-pk' ) );
        }
        
        ?>
        <div class="wrap ssm-wrap">
            <h2><?php echo esc_html__( 'SmartRent PK – مرکزی ڈیش بورڈ', 'smartrent-pk' ); ?></h2>
            
            <div id="<?php echo esc_attr( $this->root_id ); ?>" class="ssm-root" data-screen="<?php echo esc_attr( $this->page_slug ); ?>">
                <div class="ssm-loading">
                    <p><?php echo esc_html__( 'ڈیٹا لوڈ ہو رہا ہے، براہ کرم انتظار کریں۔...', 'smartrent-pk' ); ?></p>
                </div>

                <template id="ssm-dashboard-template">
                    <div class="ssm-dashboard-grid">
                        <div class="ssm-kpi-card">
                            <h3><?php echo esc_html__( 'ٹوٹل پراپرٹیز (ملکیتی)', 'smartrent-pk' ); ?></h3>
                            <p class="ssm-kpi-value">{{ total_properties }}</p>
                            <span class="ssm-kpi-detail">28 Active</span>
                        </div>
                        <div class="ssm-kpi-card">
                            <h3><?php echo esc_html__( 'Verified Tenants', 'smartrent-pk' ); ?></h3>
                            <p class="ssm-kpi-value">PKR {{ verified_tenants_value }}</p>
                            <span class="ssm-kpi-detail">↑ 12%</span>
                        </div>
                        <div class="ssm-kpi-card">
                            <h3><?php echo esc_html__( 'Total Monthly Rent', 'smartrent-pk' ); ?></h3>
                            <p class="ssm-kpi-value">PKR {{ monthly_rent_value }}</p>
                            <span class="ssm-kpi-detail">↑ 8%</span>
                        </div>
                        <div class="ssm-kpi-card ssm-kpi-warning">
                            <h3><?php echo esc_html__( 'Pending Payments', 'smartrent-pk' ); ?></h3>
                            <p class="ssm-kpi-value">{{ pending_invoices }}</p>
                            <span class="ssm-kpi-detail">12 Invoices</span>
                        </div>
                        
                        <div class="ssm-panel ssm-col-2">
                            <h3><?php echo esc_html__( 'Rental Performance Overview', 'smartrent-pk' ); ?></h3>
                            <canvas id="ssm-rental-chart"></canvas>
                        </div>

                        <div class="ssm-panel">
                            <h3><?php echo esc_html__( 'Compliance & Legal', 'smartrent-pk' ); ?></h3>
                            <ul>
                                <li>
                                    <input type="checkbox" :checked="compliance_status.estamp_active" disabled>
                                    <?php echo esc_html__( 'E-Stamp Active', 'smartrent-pk' ); ?>
                                </li>
                                <li>
                                    <input type="checkbox" :checked="compliance_status.nadra_ekyc" disabled>
                                    <?php echo esc_html__( 'NADRA eKYC', 'smartrent-pk' ); ?>
                                </li>
                                <li :class="{'ssm-alert-pending': compliance_status.trs_pending}">
                                    <input type="checkbox" :checked="!compliance_status.trs_pending" disabled>
                                    <?php echo esc_html__( 'TRS Pending (شرکتوں کی تصدیق)', 'smartrent-pk' ); ?> ⚠️
                                </li>
                            </ul>
                            <a href="#" class="button ssm-button-link"><?php echo esc_html__( 'View All Compliance Tasks', 'smartrent-pk' ); ?></a>
                        </div>
                        
                        <div class="ssm-panel">
                            <h3><?php echo esc_html__( 'Upcoming Rent Alerts', 'smartrent-pk' ); ?></h3>
                            <ul class="ssm-alerts-list">
                                <li v-for="alert in upcoming_alerts" :key="alert.name">
                                    {{ alert.name }} - PKR {{ alert.amount }} <span :class="{'ssm-status-paid': alert.status === 'Paid', 'ssm-status-due': alert.status === 'Due'}">{{ alert.status }}</span>
                                </li>
                            </ul>
                            <a href="#" class="button ssm-button-primary"><?php echo esc_html__( 'Create New Ticket (نئی درخواست)', 'smartrent-pk' ); ?></a>
                        </div>
                        
                        <div class="ssm-panel">
                            <h3><?php echo esc_html__( 'WHT Summary (ٹیبل اور چارٹ)', 'smartrent-pk' ); ?></h3>
                            <p><?php echo esc_html__( 'WHT Summary (تفصیلات)', 'smartrent-pk' ); ?></p>
                            <a href="#" class="button ssm-button-secondary"><?php echo esc_html__( 'Download WHT Report', 'smartrent-pk' ); ?></a>
                        </div>
                    </div>
                    </template>

            </div>
        </div>
        <?php
    }

    /**
     * ڈیش بورڈ کے لیے CSS اور (JavaScript) انکیو کریں۔
     */
    public function enqueue_styles_scripts( $hook ) {
        if ( strpos( $hook, $this->page_slug ) === false ) {
            return;
        }

        // CSS
        wp_enqueue_style( SSM_PLUGIN_SLUG . '-admin-global', SSM_PLUGIN_URL . 'assets/css/smartrent-pk-admin.css', [], SSM_PLUGIN_VERSION );
        wp_enqueue_style( SSM_PLUGIN_SLUG . '-dashboard', SSM_PLUGIN_URL . 'assets/css/dashboard.css', [], SSM_PLUGIN_VERSION );

        // JS
        wp_enqueue_script( SSM_PLUGIN_SLUG . '-dashboard', SSM_PLUGIN_URL . 'assets/js/dashboard.js', [ 'jquery' ], SSM_PLUGIN_VERSION, true );

        // لوکلائزیشن (AJAX) اور نانس ڈیٹا
        wp_localize_script( SSM_PLUGIN_SLUG . '-dashboard', 'ssmDashboardData', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( $this->nonce_key ),
            'action'   => $this->ajax_action,
            'caps'     => [
                'can_manage_properties' => current_user_can( 'ssm_manage_properties' ),
            ],
            'strings' => [
                'loading_error' => esc_html__( 'ڈیش بورڈ لوڈ کرنے میں خرابی ہوئی ہے۔', 'smartrent-pk' ),
                // ... مستقبل میں دیگر سٹرنگز یہاں آئیں گی
            ],
        ] );
    }
}
// 🔴 یہاں پر Dashboard Class ختم ہو رہا ہے
// ✅ Syntax verified block end.
