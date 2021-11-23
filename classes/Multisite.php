<?php
/**
 * Init related functions and actions.
 *
 * @author      Tunafish
 * @package 	  smartrack/classes
 * @version     1.0.0
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'sTrack_MS' ) ) :

class sTrack_MS {	


	/*
	 * Check if the plugin is network activated
	 *
	 * @access public
	 * @return bool
	*/
	public static function plugin_is_network_activated()
	{
		$active = 0;
		
		if( is_multisite() )
		{
			if( !function_exists( 'is_plugin_active_for_network' ) )
			{
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				// Makes sure the plugin is defined before trying to use it
			}
			 
			if( is_plugin_active_for_network( 'smartrack/smartrack.php' ) ) 
			{
				$active = 1;
			}
		}
		
		return $active;
	}
	
	
	
	
	
	
	
	/*
	 * Check if specific admin data has to loaded.
	 *
	 * @access public
	 * @return bool
	*/
	public static function load_admin_data()
	{
		$visible = 0;
		
		if( is_multisite() && self::plugin_is_network_activated() && is_main_site() || is_multisite() && !self::plugin_is_network_activated() || !is_multisite() )
		{
			$visible = 1;
		}
		
		return $visible;
	}
	
	
	
	
	
	/*
	 * MULTISITE get data from main site using set_blog_id() or switch_to_blog()
	 *
	 * @access public
	 * @return null
	*/
	public static function wpmu_load_from_main_start()
	{	
		if( self::plugin_is_network_activated() && !is_main_site() )
		{
			//global $wpdb;
			
			switch_to_blog( BLOG_ID_CURRENT_SITE );
			// $wpdb->set_blog_id( BLOG_ID_CURRENT_SITE );
		}
	}
	
	
	/*
	 * MULTISITE get data from main site using set_blog_id() or switch_to_blog()
	 *
	 * @access public
	 * @return null
	*/
	public static function wpmu_load_from_main_stop()
	{	
		if( self::plugin_is_network_activated() && is_main_site() )
		{
			//global $wpdb;
			restore_current_blog();	
		}
	}
	
	
	
	
	
	
	
	/*
	 * Load site option - get_option() - for multisite installations.
	 *
	 * @access public
	 * @return array/string
	*/
	public static function get_option( $name, $value = '' )
	{
		global $wpdb;
		
		if( self::plugin_is_network_activated() )
		{
			$option = get_site_option($name, $value);
		}
		else
		{
			$option = get_option($name, $value);
		}
		
		return $option;
	}
	
	
	
	
	
	
	
	/*
	 * Update option - update_option() - for multisite installations.
	 *
	 * @access public
	 * @return null
	*/
	public static function update_option( $name, $value = '' )
	{
		global $wpdb;
		
		update_option($name, $value);
		
		if( self::plugin_is_network_activated() && is_main_site() )
		{
			update_site_option($name, $value);
		}
	}
	
	
	
	
	
	
	/*
	 * Load site option - get_option() - for multisite installations.
	 *
	 * @access public
	 * @return string
	*/
	public static function do_shortcode( $shortcode )
	{
		global $wpdb;
		
		self::wpmu_load_from_main_start();
		$value = do_shortcode($shortcode);
		self::wpmu_load_from_main_stop();
		
		return $value;
	}
	
	
	
	
	
	
	/*
	 * Load site url.
	 *
	 * @access public
	 * @return string
	*/
	public static function get_site_url()
	{	
		$url = is_multisite() && self::plugin_is_network_activated() ? get_site_url( BLOG_ID_CURRENT_SITE ) : get_site_url( get_current_blog_id() );
		
		return $url;
	}
	
	
	
	
	
	/*
	 * Database Prefix
	 *
	 * @access public
	 * @return string
	*/
	public static function db_prefix()
	{
		global $wpdb;
		
		if ( self::plugin_is_network_activated() ) 
		{ 
			$db_prefix = $wpdb->get_blog_prefix( BLOG_ID_CURRENT_SITE ); 
		}
		else
		{
			$db_prefix = $wpdb->prefix;
		}
		
		return $db_prefix;
	}
	
}
endif;
?>