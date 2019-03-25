<?php
/**
 * Plugin Name: Caldera Form Woocommerce Integration add-on
 * Plugin URI: https://calderaforms.com/
 * Description: Integrates Caldera Forms with Woocommerce so that form submissions can result different actions performed in woocommerce.
 * Version: 1.0.0
 * Author: Real Big Plugins
 * Author URI: https://realbigplugins.com/
 * Text Domain: woo_cal_addon
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) exit;

register_activation_hook( __FILE__, [ 'WooCommerce_Caldera_Integration', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'WooCommerce_Caldera_Integration', 'deactivation' ] );

/**
 * Class WooCommerce_Caldera_Integration
 */
class WooCommerce_Caldera_Integration {

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @since 1.0
	 * @return $this
	 */
	public static function instance() {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof WooCommerce_Caldera_Integration ) ) {
			self::$instance = new self;

			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Activation function hook
	 *
	 * @since 1.0
	 * @return void
	 */
	public function activation() {

		if ( ! current_user_can( 'activate_plugins' ) )
			return;
		
		if ( ! self::meets_requirements() ) {
			return ;
		}
	}

	/**
	 * Setup Constants
	 */
	private function setup_constants() {

		/**
		 * Directory
		 */
		define( 'WOO_CAL_DIR', plugin_dir_path( __FILE__ ) );
		define( 'WOO_CAL_DIR_FILE', WOO_CAL_DIR . basename( __FILE__ ) );
		define( 'WOO_CAL_INCLUDES_DIR', trailingslashit( WOO_CAL_DIR . 'includes' ) );
		define( 'WOO_CAL_BASE_DIR', plugin_basename( __FILE__ ) );
        define( 'CF_WOOCOMMERCE_VER', '1.0.0' );
		
		/**
		 * URLS
		 */
		define( 'WOO_CAL_PLUGIN_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
		define( 'WOO_CAL_ASSETS_URL', trailingslashit( WOO_CAL_PLUGIN_URL . 'assets' ) );
	}

	/**
	 * Include Required Files
	 */
	private function includes() {

		if ( file_exists( WOO_CAL_INCLUDES_DIR . 'woo_caldera_meta_box.php' ) ) {
			require_once( WOO_CAL_INCLUDES_DIR . 'woo_caldera_meta_box.php' );
		}

		if ( file_exists( WOO_CAL_INCLUDES_DIR . 'cf_woocommerce_process.php' ) ) {
			require_once( WOO_CAL_INCLUDES_DIR . 'cf_woocommerce_process.php' );
		}

		if ( file_exists( WOO_CAL_INCLUDES_DIR . 'cf_woocommerce_checkout.php' ) ) {
			require_once( WOO_CAL_INCLUDES_DIR . 'cf_woocommerce_checkout.php' );
		}

		if ( file_exists( WOO_CAL_INCLUDES_DIR . 'cf_woocommerce_product.php' ) ) {
			require_once( WOO_CAL_INCLUDES_DIR . 'cf_woocommerce_product.php' );
		}

		if ( file_exists( WOO_CAL_INCLUDES_DIR . 'functions.php' ) ) {
			require_once( WOO_CAL_INCLUDES_DIR . 'functions.php' );
		}

		include_once WOO_CAL_DIR . '/vendor/autoload.php';

	}
	
	/**
	 * Adds new order status in woocommerce.
	 * 
	 * @param $order_statuses
	 * 
	 * @return $order_statuses
	 */
	function woo_cal_new_order_statuses( $order_statuses ) {

		$order_statuses['wc-pending'] = __( 'Pending', 'woo_cal_addon' );
	
		return $order_statuses;
	}

	

	/**
	 * Initializes some action hooks.
	 */
	private function hooks() {

	   	/**
		 * filter to initialize the license system
		 */
		add_action('init', [ $this, 'woo_cal_init_session' ], 1);
		add_action( 'admin_init', [ $this, 'woo_cal_init_license' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_scripts' ], 11 );

		add_action( 'plugins_loaded', [ $this, 'upgrade' ] );
		add_action( 'admin_notices', [ $this, 'disable_plugin' ] );
		
		add_filter( 'wc_order_statuses', [ $this, 'woo_cal_new_order_statuses' ] );
	}

    /**
     * Enqueue scripts on frontend
     *
     * @since 1.0
     */
    public function frontend_enqueue_scripts() {
        wp_enqueue_style( 'bosedd-front-style', WOO_CAL_ASSETS_URL . 'front.css' );
    }
	
	/**
     * Initiate the Sessions
     */
	function woo_cal_init_session() {
        if(!session_id()) {
            session_start();
        }
    }

	/**
     * License function
     */
	function woo_cal_init_license() {
		$plugin = array(
			'slug'      =>  'woocommerce-caldera-integration',
			'name'      =>  'Caldera Form Woocommerce Integration add-on',
			'author'    =>  'Real Big Plugins',
			'url'		=>  'https://calderaforms.com/',
			'key_store'	=>  'cf_woocommerce_license_key',
			'file'		=>  dirname( __FILE__ ),
		);
	
		new \calderawp\licensing_helper\licensing( $plugin );

	}

	/**
	 * Check if Caldera and WooCommerce is available
	 *
	 * @return bool True if Caldera and WooCommerce is available, false otherwise
	 */
	public function meets_requirements() {

		if ( ! defined( 'CFCORE_VER' ) || ! class_exists( 'WooCommerce' )  ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( plugin_basename( __FILE__ ), true );
			return false;
        } 
        
        return true;
	} 
	
	/**
	 * Display admin notifications that dependency not found.
	 */
	function disable_plugin() {
		if ( ! $this->meets_requirements() ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( plugin_basename( __FILE__ ), true );
			$class = 'notice is-dismissible error';
			$message = __( 'Caldera Form Woocommerce Integration add-on requires <a href="https://wordpress.org/plugins/woocommerce/" target="_BLANK">Woocommerce</a> plugin and <a href="https://wordpress.org/plugins/caldera-forms/" target="_BLANK">Caldera Forms</a> plugin to be activated.', 'woo_cal_addon' );
			printf( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
		}
	}
}

/**
 * @return WooCommerce_Caldera_Main|bool
 */
function WooCommerce_Caldera_Main() {

	$instance = WooCommerce_Caldera_Integration::instance();
	if ( ! $instance->meets_requirements() ) {
		return false;
	}
	$GLOBALS['woo_caldera'] = $instance;

}
add_action( 'plugins_loaded', 'WooCommerce_Caldera_Main' );