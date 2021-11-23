<?php
/**
 * Init related functions and actions.
 *
 * @author      Tunafish
 * @package 	smartrack/classes
 * @version     1.0.0
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'sTrack_Core' ) ) :


class sTrack_Core {	
	
	public static $version = '1.0.0';
	public static $wpdb = '';
	public static $IP = false;
	public static $settings = array();
	protected static $stats = array();
	protected static $settings_signature = '';
	
	
	/**
	 * Initializes variables and actions
	 */
	public static function init() 
	{	
		self::$wpdb = apply_filters( 'strack_wpdb', $GLOBALS[ 'wpdb' ] );
		
		// Load all the settings
		self::$settings = sTrack_MS::get_option('strack_settings', array());
		//print_r(self::$settings).'<br><br>';
		self::$settings = wp_parse_args( self::$settings, self::default_settings());
		// Settings signature: no need to update the database is settings are the same
		self::$settings_signature = md5( serialize( self::$settings ) );
		self::strack_save_settings();
		
		// Update the options before shutting down
		add_action( 'shutdown', array( __CLASS__, 'strack_save_settings' ), 100 );
		add_action( is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts' , array(__CLASS__, 'strack_enqueue_tracking'), 15);
		//self::strack_enqueue_tracking();
		// Ajax
		add_action('wp_ajax_save_pview_data', array(__CLASS__, 'update_pview_data_callback'));
		add_action('wp_ajax_nopriv_save_pview_data', array(__CLASS__, 'update_pview_data_callback'));
		
		//add_filter('wppas_api_head', array(__CLASS__, 'head_scripts'));
	}
	
	
	/**
	 * AJAX Update page view data.
	 *
	 *
	 */
	public static function update_pview_data_callback() 
	{
		$pv_id = $_POST['id'];
		$track_id = $_POST['track_id'];
		$ref = urldecode($_POST['ref']);
		$site_host = parse_url( get_site_url(), PHP_URL_HOST );
		$referer = parse_url($ref);
		$screen_w = intval($_POST['screen_w']);
		$screen_h = intval($_POST['screen_h']);
		$reso = strip_tags(trim($_POST['reso']));
		//$referer['host'] == $site_host 
		
		
		// Update DB
		self::$stats['id'] = $_POST['id'];
		self::$stats['track_id'] = $_POST['track_id'];
		self::$stats['ref'] = $ref;
		self::$stats['search_terms'] = self::get_search_terms($referer);
		self::$stats['screen_w'] = $screen_w;
		self::$stats['screen_h'] = $screen_h;
		self::$stats['resolution'] = $reso;

		sTrack_DB::update_row( self::$stats, self::$wpdb->prefix.'strack_st' );
		//self::$wpdb->query("UPDATE ".self::$wpdb->prefix."strack_st SET referer = '".$ref."' WHERE id = ".$pv_id."");
		
		exit();
	}
	
	
	
	
	
	/**
	 * Core tracking function
	 */
	public static function _tracking( $args = array() ) 
	{
		// https://stackoverflow.com/a/13640164/3481803
		/*@header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		@header("Cache-Control: post-check=0, pre-check=0", false);
		@header("Pragma: no-cache");
        // Make sure headers are sent (and not later get modified by WP or other plugins)
        flush();
        // Keep going even when user ends the connection
		ignore_user_abort(true); */
		
		$defaults = array(
			'type' => 'pview',
			'track' => 1,
			'no_bots' => 1,
			'track_admin_area' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		$browser = new sTrack_Browser();
		$ua = $browser->getUserAgent();
		// Allow third-party tools to prevent/allow tracking.
		$args['track'] = $args['no_bots'] && ($browser->isRobot() || self::is_bot($ua)) ? 0 : $args['track'];
		$args['track'] = apply_filters( 'strack_track_page_view', $args['track'] );
		// fix to prevent pageviews being counted on Adning link redirects.
		// Because apply_filters doesn't seem to work here
		$args['track'] = isset($_GET['_dnlink']) ? 0 : $args['track'];

		if( $args['track'] && (!is_admin() || $args['track_admin_area']) )
		{
			self::$stats[ 'tm' ] = date_i18n( 'U' );
			$strack_stats_cookie = self::get_track_id( false );
			if( $args['type'] == 'pview')
			{	
				// Get user platform
				$pf = $browser->getPlatform();
				$platform = sTrack_UA::get_os_version($ua, $pf);
				$platform = $platform[0] != 'unknown' ? $platform[0] : $pf;
				
				// Users IP address
				self::$stats[ 'ip' ] = self::get_visitor_ip();
				self::$stats[ 'language' ] = self::get_language();
				self::$stats[ 'country' ] = self::get_country( self::$stats[ 'ip' ] );
				self::$stats[ 'browser' ] = $browser->getBrowser();
				self::$stats[ 'platform' ] = $platform;
				self::$stats[ 'user_agent' ] = $ua;
				self::$stats[ 'device' ] = self::get_visitor_device();
				
				$content = self::get_content_info();
				// Dont save page views for error pages.
				if( $content['content_type'] === '404')
					return;
				self::$stats[ 'content_id' ] = $content['content_id'];
				self::$stats[ 'content_type' ] = $content['content_type'];
				
				// Allow third-party tools to use the stats array
				self::$stats = apply_filters( 'strack_stats_init', self::$stats );
				
				// Save this information in the database
				self::$stats[ 'id' ] = sTrack_DB::insert_row( self::$stats, $GLOBALS[ 'wpdb' ]->prefix . 'strack_st' );
				
				$sTrackStatsArgs = wp_parse_args(self::$stats, array('ajaxurl' => admin_url('admin-ajax.php')));
				//wp_localize_script('strack_js','sTrackStatsArgs', $sTrackStatsArgs);
				//self::strack_enqueue_tracking();
			}
		}
	}
	// end tracking
	
	
	
	public static function track_event($args = array())
	{
		$defaults = array(
			'event_type' => '',
			'id_1' => 0,
			'id_2' => 0,
			'id_3' => 0,
			'notes' => '',
			'banner_id' => 0,
			'adzone_id' => 0,
			'advertiser_id' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		// Create values for WP Pro Advertising System.
		if(array_key_exists('type', $args) && ($args['type'] == 'clicks' || $args['type'] == 'impressions'))
		{
			$args['event_type'] = $args['type'] == 'clicks' ? 'click' : 'impression';
			$args['id_1'] = $args['banner_id'];
			$args['id_2'] = $args['adzone_id'];
			$args['id_3'] = $args['advertiser_id'];
			$args['notes'] = 'wpproads::id_1::banner_id,id_2::adzone_id,id_3::advertiser_id';
		}
		//echo $args['id_1'].$args['id_2'].$args['id_3'];
		// Create values for ADNING.
		if(array_key_exists('type', $args) && ($args['type'] == 'click' || $args['type'] == 'impression'))
		{
			$args['event_type'] = $args['type'] == 'click' ? 'click' : 'impression';
			$args['id_1'] = $args['banner_id'];
			$args['id_2'] = $args['adzone_id'];
			$args['id_3'] = $args['advertiser_id'];
			$args['notes'] = 'adning::id_1::banner_id,id_2::adzone_id,id_3::advertiser_id';
		}
		
		$strack_stats_cookie = self::get_track_id( false );
		
		// Check if page view has been registered for this request.
		/*if( $args['event_type'] == 'click')
		{
			echo $_GET['strack'];
			break;
		}*/
		
		if(empty(self::$stats['id']))
		{
			if(isset($_GET['strack']))
			{
				self::$stats[ 'id' ] = $_GET['strack'];
			}
			else
			{
				// Try to Guess page view id based on the latest impression.
				self::$stats[ 'id' ] = sTrack_DB::get_var(
					array("sql" => "SELECT id FROM ".$GLOBALS[ 'wpdb' ]->prefix . "strack_st WHERE track_id = ".self::$stats[ 'track_id' ]." ORDER BY id DESC LIMIT 1")
				);	
			}

			if(empty(self::$stats['id']))
			{
				self::$stats['id'] = self::$stats[ 'track_id' ];
			}
		}
		
		
		if( !empty(self::$stats[ 'id' ]) && !empty($args['event_type']))
		{
			//self::strack_enqueue_tracking();
			
			$event_info = array(
				'event_type' => $args['event_type'],
				'notes' => $args['notes'],
				'id_1' => $args['id_1'],
				'id_2' => $args['id_2'],
				'id_3' => $args['id_3'],
				'id' => self::$stats[ 'id' ],
				'tm' => date_i18n( 'U' )
			);
			
			//$content = self::get_content_info();
			// Fixes a weird error where 2 rows get saved at the same time...
			$check_for_duplicate = sTrack_DB::get_results(array(
				'sql' => "SELECT event_id FROM ".$GLOBALS[ 'wpdb' ]->prefix."strack_ev WHERE id = ".$event_info['id']." AND event_type = '".$event_info['event_type']."' AND tm = ".$event_info['tm']." AND id_1 = ".$event_info['id_1']." AND id_2 = ".$event_info['id_2']." AND id_3 = ".$event_info['id_3']
			));
			if( empty($check_for_duplicate))
			{
				//echo '<pre>'.print_r($event_info,true).'</pre>';
				$event_id = sTrack_DB::insert_row( $event_info, $GLOBALS[ 'wpdb' ]->prefix . 'strack_ev' );
			}
			else
			{
				$event_id = $check_for_duplicate[0]['event_id'];
			}
			wp_localize_script('strack_js','sTrackEvent', array('id' => $event_id));
		}
	}
	
	
	
	
	
	/**
	 * Get language from browser header
	 */
	protected static function get_language()
	{
		if(isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			// Capture up to the first delimiter (, found in Safari)
			preg_match("/([^,;]*)/", $_SERVER["HTTP_ACCEPT_LANGUAGE"], $array_languages);
			// Fix some codes, the correct syntax is with minus (-) not underscore (_)
			return str_replace( "_", "-", strtolower( $array_languages[0] ) );
		}
		return 'xx';
	}
	// end get_language
	
	
	
	
	
	/**
	 * Get Country by IP Address
	 *
	 * Maxmind
	 * http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz
	 */
	public static function get_country( $ip )
	{
		$ip_num = (float) sprintf( '%u', bindec( self::dtr_pton( $ip )));
		$country_cd = 'xx';
			
		if( file_exists( STRACK_INC_DIR.'/maxmind/GeoIP.dat' ) && ( $handle = fopen( STRACK_INC_DIR.'/maxmind/GeoIP.dat', 'rb' ))) 
		{
			$country_codes = array('','ap','eu','ad','ae','af','ag','ai','al','am','cw','ao','aq','ar','as','at','au','aw','az','ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bv','bw','by','bz','ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz','de','dj','dk','dm','do','dz','ec','ee','eg','eh','er','es','et','fi','fj','fk','fm','fo','fr','sx','ga','gb','gd','ge','gf','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu','id','ie','il','in','io','iq','ir','is','it','jm','jo','jp','ke','kg','kh','ki','km','kn','kp','kr','kw','ky','kz','la','lb','lc','li','lk','lr','ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq','mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np','nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pm','pn','pr','ps','pt','pw','py','qa','re','ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sj','sk','sl','sm','sn','so','sr','st','sv','sy','sz','tc','td','tf','tg','th','tj','tk','tm','tn','to','tl','tr','tt','tv','tw','tz','ua','ug','um','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yt','rs','za','zm','me','zw','a1','a2','o1','ax','gg','im','je','bl','mf','bq','ss','o1');
				
			$offset = 0;
			for($depth = 31; $depth >= 0; --$depth) 
			{
				if (fseek($handle, 6 * $offset, SEEK_SET) != 0)
				{
					break;
				}
				$buf = fread($handle, 6);
				$cd = array(0,0);
				for($i = 0; $i < 2; ++$i) 
				{
					for($j = 0; $j < 3; ++$j) 
					{
						$cd[$i] += ord(substr($buf, 3 * $i + $j, 1)) << ($j * 8);
					}
				}
		
				if( $ip_num & ( 1 << $depth )) 
				{
					if($cd[1] >= 16776960 && !empty($country_codes[$cd[1] - 16776960])) 
					{
						$country_cd = $country_codes[$cd[1] - 16776960];
						break;
					}
					$offset = $cd[1];
				} 
				else 
				{
					if($cd[0] >= 16776960 && !empty($country_codes[$cd[0] - 16776960])) 
					{
						$country_cd = $country_codes[$cd[0] - 16776960];
						break;
					}
					$offset = $cd[0];
				}
			}
			fclose($handle);
		}
		
		return $country_cd;
	}
	
	
	
	
	/**
	 * Detect Bots
	 */
	public static function is_bot( $user_agent = '') 
	{
		$is_bot = 0;
		$bots = array(
			'AbachoBOT',
			'Acoon',
			'AESOP_com_SpiderMan',
			'ah-ha.com',
			'appie',
			'Arachnoidea',
			'ArchitextSpider',
			'Atomz',
			'coccocbot',
			'DeepIndex',
			'DotBot',
			'Googlebot',
			'Gigabot',
			'Openbot',
			'Slurp',
			'YandexBot',
			'WebCrawler',
			'Bingbot',
			'AhrefsBot',
			'SemrushBot',
			'TrendictionBot'
		);

		$bots = apply_filters("smartrack_bots", $bots);
		
		if(!empty($user_agent))
		{
			foreach($bots as $bot)
			{
				if(strpos(strtolower($user_agent), strtolower($bot)) !== false) 
				{
					return 1;
				}
			}
		}

		// Extra check
		if ( 
			preg_match('/abacho|accona|AddThis|AdsBot|ahoy|AhrefsBot|AISearchBot|alexa|altavista|anthill|appie|applebot|arale|araneo|AraybOt|ariadne|arks|aspseek|ATN_Worldwide|Atomz|baiduspider|baidu|bbot|bingbot|bing|Bjaaland|BlackWidow|BotLink|bot|boxseabot|bspider|calif|CCBot|ChinaClaw|christcrawler|CMC\/0\.01|combine|confuzzledbot|contaxe|CoolBot|cosmos|crawler|crawlpaper|crawl|curl|cusco|cyberspyder|cydralspider|dataprovider|digger|DIIbot|DotBot|downloadexpress|DragonBot|DuckDuckBot|dwcp|EasouSpider|ebiness|ecollector|elfinbot|esculapio|ESI|esther|eStyle|Ezooms|facebookexternalhit|facebook|facebot|fastcrawler|FatBot|FDSE|FELIX IDE|fetch|fido|find|Firefly|fouineur|Freecrawl|froogle|gammaSpider|gazz|gcreep|geona|Getterrobo-Plus|get|girafabot|golem|googlebot|\-google|grabber|GrabNet|griffon|Gromit|gulliver|gulper|hambot|havIndex|hotwired|htdig|HTTrack|ia_archiver|iajabot|IDBot|Informant|InfoSeek|InfoSpiders|INGRID\/0\.1|inktomi|inspectorwww|Internet Cruiser Robot|irobot|Iron33|JBot|jcrawler|Jeeves|jobo|KDD\-Explorer|KIT\-Fireball|ko_yappo_robot|label\-grabber|larbin|legs|libwww-perl|linkedin|Linkidator|linkwalker|Lockon|logo_gif_crawler|Lycos|m2e|majesticsEO|marvin|mattie|mediafox|mediapartners|MerzScope|MindCrawler|MJ12bot|mod_pagespeed|moget|Motor|msnbot|muncher|muninn|MuscatFerret|MwdSearch|NationalDirectory|naverbot|NEC\-MeshExplorer|NetcraftSurveyAgent|NetScoop|NetSeer|newscan\-online|nil|none|Nutch|ObjectsSearch|Occam|openstat.ru\/Bot|packrat|pageboy|ParaSite|patric|pegasus|perlcrawler|phpdig|piltdownman|Pimptrain|pingdom|pinterest|pjspider|PlumtreeWebAccessor|PortalBSpider|psbot|rambler|Raven|RHCS|RixBot|roadrunner|Robbie|robi|RoboCrawl|robofox|Scooter|Scrubby|Search\-AU|searchprocess|search|SemrushBot|Senrigan|seznambot|Shagseeker|sharp\-info\-agent|sift|SimBot|Site Valet|SiteSucker|skymob|SLCrawler\/2\.0|slurp|snooper|solbot|speedy|spider_monkey|SpiderBot\/1\.0|spiderline|spider|suke|tach_bw|TechBOT|TechnoratiSnoop|templeton|teoma|titin|topiclink|twitterbot|twitter|UdmSearch|Ukonline|UnwindFetchor|URL_Spider_SQL|urlck|urlresolver|Valkyrie libwww\-perl|verticrawl|Victoria|void\-bot|Voyager|VWbot_K|wapspider|WebBandit\/1\.0|webcatcher|WebCopier|WebFindBot|WebLeacher|WebMechanic|WebMoose|webquest|webreaper|webspider|webs|WebWalker|WebZip|wget|whowhere|winona|wlm|WOLP|woriobot|WWWC|XGET|xing|yahoo|YandexBot|YandexMobileBot|yandex|yeti|Zeus/i', 
			$user_agent) // $_SERVER['HTTP_USER_AGENT']
		){
			return true; // 'Above given bots detected'
		}
		
		return $is_bot;
	}



	
	
	
	/*
	 * Get Visitor Device
	 *
	 * @since v1.0.1
	 * @access public
	 * @return string
	*/
	public static function get_visitor_device()
	{
		$browser = new sTrack_Browser();
		
		$device = 'desktop';
		
		if( $browser->isMobile() )
		{
			$device = 'mobile';
		}
		elseif( $browser->isTablet() )
		{
			$device = 'tablet';
		}
		
		return $device;
	}
	
	
	
	
	/*
	 * Get Visitor IP
	 *
	 * @access public
	 * @return IP
	*/
	public static function get_visitor_ip() 
	{
		// Check to see if we've already retrieved the IP address and if so return the last result.
		if( self::$IP !== false ) { return self::$IP; }
		
		// Check if cronjob is running
		$sapi_type = php_sapi_name();
		if(substr($sapi_type, 0, 3) == 'cli') { return self::$IP; }
	
		// By default we use the remote address the server has.
		$temp_ip = $_SERVER['REMOTE_ADDR'];
	
		// Check to see if any of the HTTP headers are set to identify the remote user.
		// These often give better results as they can identify the remote user even through firewalls etc, 
		// but are sometimes used in SQL injection attacks.
		if (getenv('HTTP_CLIENT_IP')) {
			$temp_ip = getenv('HTTP_CLIENT_IP');
		} elseif (getenv('HTTP_X_FORWARDED_FOR')) {
			$temp_ip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_X_FORWARDED')) {
			$temp_ip = getenv('HTTP_X_FORWARDED');
		} elseif (getenv('HTTP_FORWARDED_FOR')) {
			$temp_ip = getenv('HTTP_FORWARDED_FOR');
		} elseif (getenv('HTTP_FORWARDED')) {
			$temp_ip = getenv('HTTP_FORWARDED');
		} 

		// Trim off any port values that exist.
		if( strstr( $temp_ip, ':' ) !== FALSE ) {
			$temp_a = explode(':', $temp_ip);
			$temp_ip = $temp_a[0];
		}
		
		// Check to make sure the http header is actually an IP address and not some kind of SQL injection attack.
		$long = ip2long($temp_ip);
	
		// ip2long returns either -1 or FALSE if it is not a valid IP address depending on the PHP version, so check for both.
		if($long == -1 || $long === FALSE) {
			// If the headers are invalid, use the server variable which should be good always.
			$temp_ip = $_SERVER['REMOTE_ADDR'];
		}

		// If the ip address is blank, use 127.0.0.1 (aka localhost).
		if( $temp_ip == '' ) { $temp_ip = '127.0.0.1'; }
		
		self::$IP = $temp_ip;
		
		return self::$IP;
	}
	
	
	
	public static function dtr_pton( $ip )
	{
		if( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) 
		{
			$unpacked = unpack( 'A4', inet_pton( $ip ) );
		}
		elseif( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && defined( 'AF_INET6' )) 
		{
			$unpacked = unpack( 'A16', inet_pton( $ip ) );
		}

		$binary_ip = '';
		if( !empty( $unpacked )) 
		{
			$unpacked = str_split( $unpacked[ 1 ] );
			foreach( $unpacked as $char ) 
			{
				$binary_ip .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
			}
		}
		return $binary_ip;
	}
	
	
	
	
	
	/**
	 * Reads the cookie to get the track_id
	 */
	/*public static function test_get_track_id($force = false){
		return self::get_track_id($force);
	}*/
	protected static function get_track_id($force = false)
	{
		$new_session = true;
		$identifier = 0;
		
		if( isset( $_COOKIE[ 'strack_tracking_code' ] )) 
		{
			// Make sure only authorized information is recorded
			$identifier = self::sep_checksum( $_COOKIE[ 'strack_tracking_code' ] );
			//echo 'set:'.$identifier.' - '.$_COOKIE[ 'strack_tracking_code' ];
			
			if ( $identifier === false ) 
			{
				return false;
			}

			$new_session = ( strpos( $identifier, 'id' ) !== false );
			$identifier = intval( $identifier );
		}
		
		// No active session
		if( $new_session || $force)
		{
			self::$settings[ 'session_duration' ] = !empty(self::$settings[ 'session_duration' ]) ? self::$settings[ 'session_duration' ] : 3600;
			// set default track ID Nr.
			self::$stats[ 'track_id' ] = sTrack_MS::get_option( 'strack_track_id', -1 );
			if ( self::$stats[ 'track_id' ] == -1 ) 
			{
				self::$stats[ 'track_id' ] = intval( self::$wpdb->get_var( "SELECT MAX( track_id ) FROM ".$GLOBALS[ 'wpdb' ]->prefix."strack_st"));
			}
			self::$stats[ 'track_id' ]++;
			update_option('strack_track_id', self::$stats[ 'track_id' ]);

			$set_cookie = apply_filters( 'strack_set_track_cookie', true );
			if( $set_cookie ) 
			{
				$ck = self::get_checksum( self::$stats[ 'track_id' ] );
				@setcookie(
					'strack_tracking_code',
					$ck,
					time() + self::$settings[ 'session_duration' ],
					COOKIEPATH
				);
				$_COOKIE[ 'strack_tracking_code' ] = $ck;
			}
		}
		elseif( $identifier > 0 ) 
		{
			//echo 'oi'.$identifier;
			self::$stats[ 'track_id' ] = $identifier;
		}
		
		//echo 'oi'.$identifier;
		/*if( $new_session && $identifier > 0 ) 
		{
			self::$wpdb->query( self::$wpdb->prepare( "
				UPDATE ".$GLOBALS[ 'wpdb' ]->prefix."strack_st 
				SET track_id = %d
				WHERE id = %d AND track_id = 0", self::$stats[ 'track_id' ], $identifier
			) );
		}*/
	}
	
	protected static function get_checksum( $id = 0 ) 
	{
		return $id . '.' . md5( $id . self::$settings[ 'secret' ] );
	}

	protected static function sep_checksum( $id_checksum = '' ) 
	{
		list( $id, $checksum ) = explode( '.', $id_checksum );
		//echo $checksum.' - '.md5( $id . self::$settings[ 'secret' ] ).'<br>';
		//print_r(get_option('wppas_stats_settings', array()));
		return $checksum === md5( $id . self::$settings[ 'secret' ] ) ? $id : false;
	}
	
	public static function reset_tracking_cookie()
	{
		unset($_COOKIE['strack_tracking_code']);
		@setcookie('strack_tracking_code', '', current_time('timestamp')-3600, COOKIEPATH, COOKIE_DOMAIN);	
	}
	
	
	
	/**
	 * Get Content Info
	 */
	protected static function get_content_info()
	{
		$content_info = array( 'content_id' => 0, 'content_type' => 'unknown' );

		// 404 pages
		if( is_404() ) 
		{
			$content_info['content_type'] = '404';
		}
		elseif( is_single() ) 
		{
			if( ( $post_type = get_post_type() ) != 'post' ) 
			{
				$post_type = 'cpt:' . $post_type;
			}

			$content_info['content_type'] = $post_type;
			$content_info_array = array();
			foreach ( get_object_taxonomies( $GLOBALS['post'] ) as $taxonomy ) 
			{
				$terms = get_the_terms( $GLOBALS['post']->ID, $taxonomy );
				if( is_array( $terms )) 
				{
					foreach( $terms as $term ) 
					{
						$content_info_array[] = $term->term_id;
					}
					$content_info['category'] = implode( ',', $content_info_array );
				}
			}
			$content_info['content_id'] = $GLOBALS['post']->ID;
		}
		elseif( is_page() ) 
		{
			$content_info['content_type'] = 'page';
			$content_info['content_id'] = $GLOBALS['post']->ID;
		}
		elseif(is_singular())
		{
			$content_info['content_type'] = 'singular';
		}
		elseif(is_post_type_archive())
		{
			$content_info['content_type'] = 'post_type_archive';
		}
		elseif(is_attachment())
		{
			$content_info['content_type'] = 'attachment';
		}
		elseif(is_tag())
		{
			$content_info['content_type'] = 'tag';
			$list_tags = get_the_tags();
			if (is_array($list_tags))
			{
				$tag_info = array_pop($list_tags);
				if (!empty($tag_info))
				{
					$content_info['category'] = $tag_info->term_id;
				}
			}
		}
		elseif(is_tax())
		{
			$content_info['content_type'] = 'taxonomy';
		}
		elseif(is_category())
		{
			$content_info['content_type'] = 'category';
			$list_categories = get_the_category();
			if (is_array($list_categories))
			{
				$cat_inf = array_pop($list_categories);
				if (!empty($cat_inf))
				{ 
					$content_info['category'] = $cat_inf->term_id;
				}
			}
		}
		elseif(is_date())
		{
			$content_info['content_type']= 'date';
		}
		elseif(is_author())
		{
			$content_info['content_type'] = 'author';
		}
		elseif( is_archive() ) 
		{
			$content_info['content_type'] = 'archive';
		}
		elseif( is_search() ) {
			$content_info['content_type'] = 'search';
		}
		elseif( is_feed() ) 
		{
			$content_info['content_type'] = 'feed';
		}
		elseif( is_home() || is_front_page() ) 
		{
			$content_info['content_type'] = 'home';
		}
		elseif( !empty( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] == 'wp-login.php' ) 
		{
			$content_info[ 'content_type' ] = 'login';
		}
		elseif( !empty( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] == 'wp-register.php' ) 
		{
			$content_info['content_type'] = 'registration';
		}
		elseif( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) 
		{
			$content_info['content_type'] = 'admin';
		}
		
		if(is_paged())
		{
			$content_info['content_type'] .= ',paged';
		}
		if( is_singular() ) 
		{
			$author = get_the_author_meta( 'user_login', $GLOBALS['post']->post_author );
			if( !empty( $author ) ) 
			{
				$content_info['author'] = $author;
			}
		}

		return $content_info;
	}
	// end get_content_info
	
	
	
	
	
	
	/**
	 * GET SEARCH TERMS
	 * 
	 *
	 */
	protected static function get_search_terms($url = array()) 
	{
		if( empty( $url ) || !isset( $url[ 'host' ] )) 
		{
			return '';
		}
		
		// List engines with different character encodings here.
		$query_formats = array(
			'baidu.com' => 'wd',
			'bing' => 'q',
			'dogpile.com' => 'q',
			'duckduckgo' => 'q',
			'eniro' => 'search_word',
			'exalead.com' => 'q',
			'excite' => 'q',
			'gigablast' => 'q',
			'google' => 'q',
			'hotbot' => 'q',
			'maktoob' => 'p',
			'mamma' => 'q',
			'naver' => 'query',
			'qwant' => 'q',
			'rambler' => 'query',
			'seznam' => 'oq',
			'soso.com' => 'query',
			'virgilio' => 'qs',
			'voila' => 'rdata',
			'yahoo' => 'p',
			'yam' => 'k',
			'yandex' => 'text',
			'yell' => 'keywords',
			'yippy' => 'query',
			'youdao' => 'q'
		);

		$charsets = array( 'baidu' => 'EUC-CN' );
		$regex_match = implode( '|', array_keys( $query_formats ) );
		$search_terms = '';

		if( !empty( $url[ 'query' ] )) 
		{
			parse_str( $url[ 'query' ], $query );
		}

		if( !empty( $url[ 'host' ] )) 
		{
			preg_match( "/($regex_match)./i", $url['host'], $matches );
		}

		if( !empty( $matches[1] )) 
		{
			$search_terms = '_';
			if( !empty( $query[$query_formats[$matches[1]]] )) 
			{
				$search_terms = str_replace( '\\', '', trim( urldecode( $query[$query_formats[$matches[1]]] )));
				if( function_exists( 'mb_check_encoding' ) && !mb_check_encoding( $query[$query_formats[$matches[1]]], 'UTF-8' ) && !empty( $charsets[$matches[1]] )) 
				{
					$search_terms = mb_convert_encoding( urldecode( $query[$query_formats[$matches[1]]] ), 'UTF-8', $charsets[ $matches[ 1 ] ]);
				}
			}
		}
		else {
			// No luck "yet" keep trying.
			foreach( array( 'q','s','k','qt' ) as $format ) 
			{
				if( !empty( $query[$format] )) 
				{
					$search_terms = str_replace( '\\', '', trim( urldecode( $query[$format] )));
					break;
				}
			}
		}

		return $search_terms;
	}
	// end get_search_terms
	
	
	
	
	/**
	 * Get stats CTR
	 *
	 * @access public
	 * @return array
	*/
	public static function get_ctr($args = array())
	{
		$ctr = !empty($args['clicks']) && !empty($args['impressions']) ? $args['clicks'] / $args['impressions'] * 100 : 0;
		return round($ctr,2).'%';	
	}
	
	
	
	
	/**
	 * Get a time range 
	 */
	public static function time_range($args = array()) 
	{
		$defaults = array(
			'condition' => 'today',
			'days' => 30,
			'return' => 'array', // array | string
			'h' => array(0,23),
			'd' => date_i18n('d'),
			'm' => date_i18n('m'),
			'y' => date_i18n('Y'),
			'val' => ''
		);
		$args = wp_parse_args( $args, $defaults );
		
		$range = array();
		// Check for last_x_days
		$check = self::last_x_days(array('condition' => $args['condition']));
		$args['condition'] = $check['condition'];
		$x_days = $check['days'];
		
		// Check for custom
		// custom_TIMESTAMP::TIMESTAMP
		$check = self::custom_range(array('condition' => $args['condition']));
		$args['condition'] = $check['condition'];
		$between = $check['between'];
		
		
		switch ( $args[ 'condition' ] ) 
		{
			case 'past_day':
				// used for last_x_days
				$d = $args['val'] !== '' ? date_i18n('j')-$args['val'] : $args['d'];
				//echo $args['val'].' '.$d.' '.date_i18n('j').'<br>';
				$range[0] = mktime($args['h'][0], 0, 0, $args['m'], $d, $args['y']);
				$range[1] = mktime($args['h'][1], 59, 59, $args['m'], $d, $args['y']);
				break;
			case 'day':
				$d = $args['val'] !== '' ? $args['val'] : $args['d'];
				$range[0] = mktime($args['h'][0], 0, 0, $args['m'], $d, $args['y']);
				$range[1] = mktime($args['h'][1], 59, 59, $args['m'], $d, $args['y']);
				break;
			case 'month':
				$m = !empty($args['val']) ? $args['val'] : $args['m'];
				$am_days = 31; //cal_days_in_month(CAL_GREGORIAN, $m, $args['y']);
				$range[0] = mktime($args['h'][0], 0, 0, $m, 1, $args['y']);  
				$range[1] = mktime($args['h'][1], 59, 59, $m, $am_days, $args['y']);  
				break;  
			case 'year':
				$range[0] = mktime($args['h'][0], 0, 0, 1, 1, $args['val']); 
				$range[1] = mktime($args['h'][0], 0, 0, 1, 1, $args['val']+1);
				break; 
			case 'this_year':
				$range[0] = mktime($args['h'][0], 0, 0, 1, 1, date_i18n('Y')); 
				$range[1] = current_time('timestamp');    
				break;
			case 'this_month':
				$range[0] = mktime($args['h'][0], 0, 0, $args['m'], 1, $args['y']);  
				$range[1] = current_time('timestamp');
				break;   
			case 'this_week':
				$range[0] = mktime($args['h'][0], 0, 0, date_i18n('n'), date_i18n('j'), date_i18n('Y')) - ((date_i18n('N')-1)*3600*24);     
				$range[1] = current_time('timestamp');
				break;   
			case 'last_year':
				$range[0] = mktime($args['h'][0], 0, 0, 1, 1, date_i18n('Y')-1);     
				$range[1] = mktime($args['h'][1], 59, 59, 12, 31, date_i18n('Y')-1);    
				break;
			case 'last_x_days':
				$range[0] = mktime($args['h'][0],0,0, date_i18n('m'),date_i18n('j')-$x_days,date_i18n('Y'));
				$range[1] = mktime($args['h'][1],59,59, date_i18n('m'),date_i18n('j')-1,date_i18n('Y'));
				break;
			case 'last_month':
				$y = $args['m'] === '01' ? date_i18n('Y')-1 : $args['y'];
				$m = $args['m'] === '01' ? 12 : $args['m']-1;
				$am_days = 31; //cal_days_in_month(CAL_GREGORIAN, $m, $y);
				$range[0] = mktime($args['h'][0], 0, 0, $m, 1, $y);  
				$range[1] = mktime($args['h'][1], 59, 59, $m, $am_days, $y);  
				break;
			case 'last_week':
				$range[0] = mktime($args['h'][0], 0, 0, date_i18n('n'), date_i18n('j')-6, date_i18n('Y')) - ((date_i18n('N'))*3600*24);     
				$range[1] = mktime($args['h'][1], 59, 59, date_i18n('n'), date_i18n('j'), date_i18n('Y')) - ((date_i18n('N'))*3600*24);
				break;
			case 'yesterday':
				$val = $args['val'] !== '' ? $args['val'] : $args['h'];
				$h = !is_array($val) ? array($val,$val) : $val;
				$range[0] = mktime($h[0], 0, 0, date_i18n('m'), date_i18n('d')-1, date_i18n('Y'));
				$range[1] = mktime($h[1], 59, 59, date_i18n('m'), date_i18n('d')-1, date_i18n('Y'));
				break;
			case 'custom':
				$range[0] = $between[0];
				$range[1] = $between[1];
				break;
			default:
				$val = $args['val'] !== '' ? $args['val'] : $args['h'];
				$h = !is_array($val) ? array($val,$val) : $val;
				$range[0] = mktime($h[0], 0, 0, date_i18n('m'), date_i18n('d'), date_i18n('Y'));
				$range[1] = mktime($h[1], 59, 59, date_i18n('m'), date_i18n('d'), date_i18n('Y'));
				break;
		}
		
		if($args['return'] == 'array')
		{
			return $range;
		}
		else
		{
			return $range[0].','.$range[1];
		}
		
	}
	
	
	
	
	
	public static function last_x_days($args = array())
	{
		$defaults = array(
			'condition' => 'last_30_days',
			'days' => 30
		);
		$args = wp_parse_args( $args, $defaults );
		$x_days = '';
		
		// Check for last_x_days
		if(strpos($args['condition'], 'last_') !== false && strpos($args['condition'], '_days') !== false)
		{
			$x_days = explode('last_', $args['condition']);
			$x_days = explode('_days', $x_days[1]);
			$x_days = $x_days[0];
			$x_days = is_numeric($x_days) ? $x_days : $args['days'];
			$args['condition'] = 'last_x_days';
		}
		
		return array('condition' => $args['condition'], 'days' => $x_days);
	}
	
	
	
	
	public static function custom_range($args = array())
	{
		// custom_TIMESTAMP::TIMESTAMP
		$defaults = array(
			'condition' => 'custom_'.mktime(0, 0, 0, date_i18n('m'), date_i18n('d'), date_i18n('Y')).'::'.mktime(23, 59, 59, date_i18n('m'), date_i18n('d'), date_i18n('Y'))
		);
		$args = wp_parse_args( $args, $defaults );
		$between = array();
		
		// Check for custom_
		if(strpos($args['condition'], 'custom_') !== false)
		{
			$x_range = explode('custom_', $args['condition']);
			$x_range = $x_range[1];
			$between = explode('::', $x_range);
			//print_r($between);
			$args['condition'] = 'custom';
		}
		
		return array('condition' => $args['condition'], 'between' => $between);
	}
	
	
	/**
	 * Enqueue Tracking Script
	 */
	public static function strack_enqueue_tracking()
	{
		// Check if tracking is valid and should continue.
		/*if( empty(self::$stats[ 'id' ]) || empty(self::$stats[ 'track_id' ]))
		{
			return false;
		}
		else
		{
			
		}*/
		
		
		$args = array(
			'ajaxurl' => admin_url('admin-ajax.php')
		);
		
		$args['is_admin'] = is_admin();
		$args['id'] = !empty(self::$stats[ 'id' ]) ? self::$stats[ 'id' ] : '';
		$args['track_id'] = !empty(self::$stats[ 'track_id' ]) ? self::$stats[ 'track_id' ] : '';
		$content = self::get_content_info();
		$args[ 'content_id' ] = $content['content_id'];
		$args = apply_filters( 'strack_js_args', $args );
		
		wp_enqueue_script('strack_js');
		wp_localize_script('strack_js','sTrackStatsArgs',$args);
	}
	
	
	
	
	/**
	 * Default Settings
	 */
	public static function default_settings()
	{
		return array(
			'version' => self::$version,
			'secret' => wp_hash( uniqid( time(), true )),
			'session_duration' => time() + 2678400, // one month  // one hour 3600
		);
	}
	
	
	
	
	/**
	 * Saves options in the database
	 */
	public static function strack_save_settings() 
	{
		// Allow 3rd party functions to manipulate the settings before saving
		self::$settings = apply_filters( 'strack_save_settings', self::$settings );
		
		$option = sTrack_MS::get_option( 'strack_settings', array());
		if(!empty($option) && self::$settings_signature === md5(serialize( self::$settings )) ) 
		{
			return true;
		}
		
		sTrack_MS::update_option( 'strack_settings', self::$settings);

		return true;
	}
	
	
	
	public static function get_stats_url($args)
	{
		$defaults = array(
			'url' => $_SERVER['REQUEST_URI'],
			'remove' => ''
		);
		$args = wp_parse_args( $args, $defaults );
		
		$url = $args['url'];
		
		if( !empty($args['remove']))
		{
			$has_val = strpos($url, $args['remove']) !== false ? 1 : 0;
			if( $has_val )
			{
				$exp = explode($args['remove'], $url);
				$url2 = strstr($exp[1], '&'); // get everything before the next argument starts (or is empty).
				return $exp[0].$url2;
			}
		}
		
		return $url;
	}
	
	
	
	
	/**
	 * Head scripts for wppas API
	 *
	 */
	public static function head_scripts()
	{
		$html = '';
		
		//self::tracking();
		
		$args = array(
			'ajaxurl' => admin_url('admin-ajax.php')
		);
		
		$args['id'] = !empty(self::$stats[ 'id' ]) ? self::$stats[ 'id' ] : '';
		$args['track_id'] = !empty(self::$stats[ 'track_id' ]) ? self::$stats[ 'track_id' ] : '';
		$content = self::get_content_info();
		$args[ 'content_id' ] = $content['content_id'];
		$args = apply_filters( 'strack_js_args', $args );
		
		$html.= '<script type="text/javascript">
/* <![CDATA[ */
var sTrackStatsArgs = {"ajaxurl":"'.$args['ajaxurl'].'","id":"'.$args['id'].'","track_id":"'.$args['track_id'].'","content_id":"'.$args['content_id'].'"};
/* ]]> */
</script>';
		
		$html.= '<script type="text/javascript" src="'.STRACK_PUB_URL.'/assets/js/sTrackStats.js"></script>';
		
		return $html;
	}
	
	
}
//end class sTrack_Core








// Let's get going
if ( function_exists( 'add_action' ) ) 
{
	add_action( 'plugins_loaded', array( 'sTrack_Core', 'init' ), 20 );
}

endif;
?>