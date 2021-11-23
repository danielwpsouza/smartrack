<?php
/**
 * Init related functions and actions.
 *
 * @author      Tunafish
 * @package 	  wp_pro_ad_system/classes
 * @version     1.0.0
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'sTrack_Init' ) ) :


class sTrack_Init {	
	
	public function __construct() 
	{
		//global $strack_browser;
		
		// Run this on activation.
		register_activation_hook(STRACK_FILE, array($this, 'install'));
		register_deactivation_hook(STRACK_FILE, array($this, 'deactivate'));
		
		// Load Functions ------------------------------------------------- 
		require_once( STRACK_INC_DIR .'/ajax_functions.php');
		
		// Load Classes --------------------------------------------------- 
		require_once( STRACK_DIR.'classes/Core.php');
		require_once( STRACK_DIR.'classes/DB.php');
		require_once( STRACK_DIR.'classes/Tpl.php');
		require_once( STRACK_DIR.'classes/Graph.php');
		require_once( STRACK_DIR.'classes/Multisite.php');
		require_once( STRACK_DIR.'classes/Browser.php');
		require_once( STRACK_DIR.'classes/UA.php');
		
		
		/* ----------------------------------------------------------------
		 * Set Classes
		 * ---------------------------------------------------------------- */
		//$strack_browser = new sTrack_Browser();
		
		
		// Actions --------------------------------------------------------
		add_action('wp_loaded', array( 'sTrack_DB', 'export_stats' ));	
		add_action('init', array( $this, 'init_method'));
		add_action( is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts' , array($this, 'strack_enqueue_scripts'), 15);
		add_action('admin_menu', array($this, 'admin_actions'), 20);
		add_action( is_admin() ? 'admin_init' : 'wp', array('sTrack_Core', '_tracking'), 10);
		add_action( 'admin_init', array( __CLASS__, 'check_for_plugin_updates') );
		//add_action( 'wp', array('sTrack_Core', '_tracking'), 10);
		//add_action( 'admin_init', array('sTrack_Core', '_tracking'), 10);
		//add_action( is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts', array('sTrack_Core', '_tracking'), 20);
		//add_action( 'wp_loaded', array('sTrack_Core', '_tracking'));
			
		
		// Hook db cleanup routine to the daily cronjob
		add_action( 'wp_update_plugins', array( $this, 'strack_stats_optimize' ) );
		
		// Filters --------------------------------------------------------
		add_filter('wppas_save_stats', array('sTrack_Core', 'track_event'));
		add_action('adning_save_stats', array('sTrack_Core', 'track_event'));
		add_filter('wppas_banner_menu', array('sTrack_Tpl', 'banner_menu'), 10, 2);
	}
	
	
	
	
	/**
	 * Install WPPAS_STATS
	 */
	public function install() 
	{	
		sTrack_DB::create_tables();
	}
	
	
	/**
	 * Deactivate
	 */
	public function deactivate() 
	{
		wp_clear_scheduled_hook('strack_optimize');
	}
	
	
	
	/*
	 * Init actions
	 *
	 * @access public
	 * @return null
	*/
	public function init_method() 
	{
		global $wpdb;

		if( isset($_GET['reset_tracking']))
		{
			sTrack_Core::reset_tracking_cookie();
			delete_option( 'strack_settings' );
		}

		//Wppas_Stats_DB::update_tables();
	}
	
	
	
	/*
	 * enqueue_scripts
	 *
	 * @access public
	 * @return null
	*/
	public function strack_enqueue_scripts()
	{
		// Enqueue scripts
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		
		wp_register_style('strack_style', STRACK_PUB_URL."/assets/css/strack.css", false, STRACK_VERSION, "all" );
		wp_register_script('strack_chart', STRACK_PUB_URL."/assets/js/Chart.bundle.min.js", array( 'jquery' ), false, true );
		wp_register_script('strack_js', STRACK_PUB_URL.'/assets/js/sTrackStats.js', array( 'jquery' ), false, true);
		wp_register_script('strack_heatmap', STRACK_PUB_URL."/assets/js/heatmap.min.js", array( 'jquery' ), false, true );
		wp_register_style( 'strack_font_awesome_style', STRACK_PUB_URL.'/assets/font-awesome/css/font-awesome.min.css', false, STRACK_VERSION, 'all');
	}
	
	
	
	
	/*
	 * Admin actions
	 *
	 * @access public
	 * @return null
	*/
	public function admin_actions()
	{
		// Create menu
		if( is_plugin_active( 'wppas/wppas.php' ) ) 
		{
			add_submenu_page( 'wp-pro-advertising', __('smarTrack','strack'), __('smarTrack','strack'), STRACK_ROLE_ADMIN, 'strack-statistics', array($this,'strack_stats') );
		}
		//if( is_plugin_active( 'angwp/adning.php' ) ) 
		if( class_exists( 'ADNI_Main' ) )
		{
			add_submenu_page( 'adning', __('smarTrack','strack'), __('smarTrack','strack'), STRACK_ROLE_ADMIN, 'strack-statistics', array($this,'strack_stats') );
		}
	}
	
	
	// MENU FUNCTIONS -------------------------------------------------------
	public function strack_stats()
	{
		include( STRACK_TPL_DIR .'/stats.php');
	}
	
	
	
	
	
	/**
	 * Perform daily optimizations
	 */
	public function strack_stats_optimize()
	{
		// Optimize tables
		$GLOBALS['wpdb']->query( "OPTIMIZE TABLE ".$GLOBALS['wpdb']->prefix."strack_st" );
		$GLOBALS['wpdb']->query( "OPTIMIZE TABLE ".$GLOBALS['wpdb']->prefix."strack_st_archive" );
		$GLOBALS['wpdb']->query( "OPTIMIZE TABLE ".$GLOBALS['wpdb']->prefix."strack_ev" );
		$GLOBALS['wpdb']->query( "OPTIMIZE TABLE ".$GLOBALS['wpdb']->prefix."strack_ev_archive" );
	}
	




	/*
	 * Plugin auto update
	 *
	 * @access public
	 * @return null
	*/
	public static function check_for_plugin_updates()
	{
		// Only works if Adning is activated - http://adning.com
		//if (class_exists('ADNI_Multi')) {
			//set_site_transient('update_plugins', null); // Just for testing to see if the available plugin update gets shown. IF THIS IS ON ACTUALL PLUGIN UPDATES MAY NOT WORK: WP error: Plugin update failed.
			
			//$activation = ADNI_Multi::get_option('adning_activation', array());
			//$license_key = !empty($activation) ? $activation['license-key'] : '';

			require( STRACK_DIR.'classes/STRACK_PLU_Auto_Plugin_Updater.php');
			$api_url = 'http://tunasite.com/updates/?plu-plugin=ajax-handler';
			// current plugin version | remote url | Plugin Slug (plugin_directory/plugin_file.php) | users envato license key (default: '') | envato item ID (default: '')
			new STRACK_PLU_Auto_Plugin_Updater(STRACK_VERSION, $api_url, STRACK_BASENAME.'/'.STRACK_BASENAME.'.php'); // $license_key, ADNI_ENVATO_ID
		//}
	}
	

	
}
endif;
?>