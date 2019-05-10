<?php
/**
 * Plugin Name: MonsterInsights - Google Optimize Addon
 * Plugin URI:  https://www.monsterinsights.com
 * Description: Integrates Google Optimize with MonsterInsights
 * Author:      MonsterInsights Team
 * Author URI:  https://www.monsterinsights.com
 * Version:     1.2.0
 * Text Domain: monsterinsights-google-optimize
 * Domain Path: languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 *
 * @package MonsterInsights_Google_Optimize
 * @author  Chris Christoff
 */
class MonsterInsights_Google_Optimize {
	/**
	 * Holds the class object.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $version = '1.2.0';

	/**
	 * The name of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugin_name = 'MonsterInsights Google Optimize';

	/**
	 * Unique plugin slug identifier.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugin_slug = 'monsterinsights-google-optimize';

	/**
	 * Plugin file.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->file = __FILE__;

		// Load the plugin textdomain.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		
		// Load the updater
		add_action( 'monsterinsights_updater', array( $this, 'updater' ) );

		// Load the plugin.
		add_action( 'monsterinsights_load_plugins', array( $this, 'init' ), 99 );
	}

	/**
	 * Loads the plugin textdomain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Loads the plugin into WordPress.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		if ( ! defined( 'MONSTERINSIGHTS_PRO_VERSION' ) ) {
			// admin notice, MI not installed
			add_action( 'admin_notices', array( self::$instance, 'requires_monsterinsights' ) );
			return;
		} 

		if ( version_compare( MONSTERINSIGHTS_VERSION, '6.0', '<' ) ) {
			// MonsterInsights version not supported
			add_action( 'admin_notices', array( self::$instance, 'requires_monsterinsights_version' ) );
			return;
		}

		if ( ! defined( 'MONSTERINSIGHTS_GOOGLE_OPTIMIZE_VERSION' ) ) {
			define( 'MONSTERINSIGHTS_GOOGLE_OPTIMIZE_VERSION', $this->version );
		}

		// Load frontend components.
		$this->require_frontend();
	}

	/**
	 * Initializes the addon updater.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The user license key.
	 */
	function updater( $key ) {
		$args = array(
			'plugin_name' => $this->plugin_name,
			'plugin_slug' => $this->plugin_slug,
			'plugin_path' => plugin_basename( __FILE__ ),
			'plugin_url'  => trailingslashit( WP_PLUGIN_URL ) . $this->plugin_slug,
			'remote_url'  => 'https://www.monsterinsights.com/',
			'version'     => $this->version,
			'key'         => $key
		);
		
		$updater = new MonsterInsights_Updater( $args );
	}

	/**
	 * Loads all frontend files into scope.
	 *
	 * @since 1.0.0
	 */
	public function require_frontend() {
		require plugin_dir_path( __FILE__ ) . 'includes/frontend/tracking.php';
	}

	/**
	 * Output a nag notice if the user does not have MI installed
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return 	void
	 */
	public function requires_monsterinsights() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'Please install MonsterInsights Pro to use the MonsterInsights Google Optimize addon', 'monsterinsights-google-optimize' ); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Output a nag notice if the user does not have MI version installed
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return 	void
	 */
	public function requires_monsterinsights_version() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'Please install or update MonsterInsights Pro version 7.4 or newer to use the MonsterInsights Google Optimize addon', 'monsterinsights-google-optimize' ); ?></p>
		</div>
		<?php
	}

	 /**
	 * Returns the singleton instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @return object The MonsterInsights_Google_Optimize object.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof MonsterInsights_Google_Optimize ) ) {
			self::$instance = new MonsterInsights_Google_Optimize();
		}
		return self::$instance;
	}
}
// Load the main plugin class.
$monsterinsights_google_optimize = MonsterInsights_Google_Optimize::get_instance();