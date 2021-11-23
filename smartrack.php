<?php
/**
 * Plugin Name: smarTrack
 * Plugin URI: http://tunasite.com
 * Description: Plugin to track user behavior and statistics for your website.
 * Version: 1.1.9
 * Author: Tunafish
 * Author URI: http://tunasite.com
 * Requires at least: 4.6
 * Tested up to: 5.4.2
 *
 * Text Domain: strack
 * Domain Path: /localization/
 *
 * @package smarTrack
 * @category Core
 * @author Tunafish
 */
//mysqli_report(MYSQLI_REPORT_OFF);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'wp_smarTrack' ) ) :

final class wp_smarTrack
{
	/**
	 * @var string
	 */
	public $version = '1.1.9';
		
	/**
	 * @var string
	 */
	public $plugin_str = 'smarTrack';
	
	
	/**
	 * @var The single instance of the class
	 */
	protected static $_instance = null;
	
	
	
	
	/**
	 * Main wp_smarTrack Instance
	 *
	 * Ensures only one instance of wp_smarTrack is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WP_STRACK()
	 * @return wp_smarTrack - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	
	


	public function __construct() 
	{	
		global $strack_init;
		
		// Define constants
		$this->define_constants();
		
		// Classes ------------------------------------------------------------
		require_once( STRACK_DIR .'classes/Init.php');
		
		
		/* ----------------------------------------------------------------
		 * Set Classes
		 * ---------------------------------------------------------------- */
		$strack_init = new sTrack_Init();
	}
	
	
	private function define_constants() 
	{
		define( 'STRACK_VERSION', $this->version );
		
		define( 'STRACK_FILE', __FILE__ );
		define( 'STRACK_BASENAME', plugin_basename( dirname( __FILE__ ) ));
		define( 'STRACK_FOLDER', str_replace(basename( __FILE__),"",plugin_basename(__FILE__)));
		
		define( 'STRACK_URL', plugins_url( STRACK_FOLDER, dirname(__FILE__) ) );
		define( 'STRACK_DIR', plugin_dir_path( __FILE__ ) );
		
		define( 'STRACK_INC_URL', STRACK_URL. 'includes' );
		define( 'STRACK_INC_DIR', STRACK_DIR. 'includes' );
		define( 'STRACK_PUB_URL', STRACK_URL. 'public' );
		define( 'STRACK_PUB_DIR', STRACK_DIR. 'public' );
		define( 'STRACK_TPL_URL', STRACK_URL. 'templates' );
		define( 'STRACK_TPL_DIR', STRACK_DIR. 'templates' );
		define( 'STRACK_PLUGIN_SLUG', basename(dirname(__FILE__)) );
		
		define( 'STRACK_ROLE_SUPERADMIN', 'manage_network_users' );
		define( 'STRACK_ROLE_ADMIN', 'remove_users' );
		define( 'STRACK_ROLE_USER', 'read' );
		
		// Made this load faster then init to translate custom post types @since v4.3.2
		load_plugin_textdomain( 'strack', false, plugin_basename( dirname( __FILE__ ) ) . '/localization' );
	}
}

endif;


/**
 * Returns the main instance of WP_STRACK to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return wp_smarTrack
 */
function WP_STRACK() {
	return wp_smarTrack::instance();
}

WP_STRACK();
?>