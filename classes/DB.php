<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'sTrack_DB' ) ) :


class sTrack_DB {	
	
	public static $version = '1.0.0';
	public static $wpdb = '';
	
	
	public static function init($args = array()) 
	{
		$defaults = array(
			'wpdb' => sTrack_Core::$wpdb
		);
		$args = wp_parse_args( $args, $defaults );
		
		self::$wpdb = $args[ 'wpdb' ];
	}


	// Check if database tables exist
	public static function check_if_tables_exist()
	{
		self::init();

		if( self::$wpdb->get_var("SHOW TABLES LIKE '".self::$wpdb->prefix."strack_st"."'") != self::$wpdb->prefix."strack_st") 
		{
			// Tables do not exist
			return array('exist' => 0, 'msg' => __('Attention, SmarTrack database tables do not seem to be created. Please go to the <a href="plugins.php">plugins page</a> and deactivate/reactivate the smarTrack plugin in order to fix this.','strack'));
		}else{
			return array('exist' => 1, 'msg' => '');
		}
	}
	
	
	
	/**
	 * Get results from database
	 */
	public static function get_results( $args = array())
	{
		self::init();
		
		$defaults = array(
			'sql' => ''
		);
		$args = wp_parse_args( $args, $defaults );
		
		return self::$wpdb->get_results( $args['sql'], ARRAY_A );
	}
	
	
	/**
	 * Get var from database
	 */
	public static function get_var( $args = array() ) 
	{
		self::init();
		
		$defaults = array(
			'sql' => ''
		);
		$args = wp_parse_args( $args, $defaults );

		return self::$wpdb->get_var( $args['sql'] );
	}
	
	
	
	
	/**
	 * Count Page Views
	 */
	public static function count_page_views( $args = array())
	{
		self::init();
		
		$defaults = array(
			'count' => 'id',
			'between' => array(),
			'group' => '',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		$from = self::$wpdb->prefix."strack_st";
		$where = '';
		$where_or_and = empty($where) ? " WHERE " : " AND ";
		$where.= !empty($args['between']) ? $where_or_and.self::sql_conditions(array('condition' => 'between', 'key' => $args['between']['key'], 'val' => $args['between']['val'], 'and' => 0)) : '';
		$group = !empty($args['group']) ? " GROUP BY ".$args['group'] : '';
			
		self::$wpdb->get_var(self::$wpdb->prepare("SELECT %s FROM ".$from.$where.$group, $args['count'] ));
		return self::$wpdb->num_rows;
	}
	
	
	
	
	/**
	 * Count Stats
	 */
	public static function count_stats( $args = array())
	{
		self::init();
		
		$defaults = array(
			'event_type' => 'impression',
			'count' => 'ev.event_id',
			'where' => array(),
			'between' => array(),
			'group' => '',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		//echo $args['group'];
		$from = self::$wpdb->prefix."strack_ev ev INNER JOIN ".self::$wpdb->prefix."strack_st st ON ev.id = st.id";
		$where = '';
		if( !empty($args['where']))
		{
			foreach( $args['where'] as $ob )
			{
				if(!empty($ob) && is_array($ob) && count($ob) == 2)
				{
					$where.= !empty($ob[0]) && !empty($ob[1]) ? " AND ".$ob[0]." = '".$ob[1]."'" : '';
				}
			}
		}

		
		
		//$uniq = $args['unique'] ? 'DISTINCT ' : '';
		$where.= !empty($args['between']) ? " AND ".self::sql_conditions(array('condition' => 'between', 'key' => $args['between']['key'], 'val' => $args['between']['val'], 'and' => 0)) : '';
		
		// @since v1.1.7
		if( $args['unique'] )
		{
			$group = !empty($args['group']) ? " GROUP BY st.ip" : '';
		}
		else
		{
			$group = !empty($args['group']) ? " GROUP BY ".$args['group'] : '';
		}
		
		/*$tst =  self::get_results(array(
			'sql' => "SELECT * FROM ".$from." WHERE ev.event_type = '".$args['event_type']."'".$where.$group.""
		));
		echo '<pre>'.print_r($tst,true).'</pre>';
		*/
		/*echo intval( self::get_var(array(
			'sql' => "SELECT COUNT(".$args['count'].") FROM ".$from." WHERE ev.event_type = '".$args['event_type']."'".$where." GROUP BY ev.id_1,ev.id")
		)).'<br>';*/
		
		//echo $where.$group.'<br>';
		self::$wpdb->get_var(self::$wpdb->prepare("SELECT COUNT(".$args['count'].") FROM ".$from." WHERE ev.event_type = %s ".$where.$group, $args['event_type'] ));
// 		var_dump("SELECT COUNT(".$args['count'].") FROM ".$from." WHERE ev.event_type = %s ".$where.$group, $args['event_type'] );exit();
		return self::$wpdb->num_rows;

		/*return intval( self::get_var(array(
			'sql' => "SELECT COUNT(".$uniq.$args['count'].") counthits FROM ".$from." WHERE ev.event_type = '".$args['event_type']."' ".$where.$group) 
		));*/
	}
	
	
	
	public static function load_stats( $args = array())
	{
		self::init();
		
		$defaults = array(
			'event_type' => 'impression',
			'select' => '*',
			'count' => 'ev.event_id',
			'where' => array(),
			'between' => array(),
			'order' => '',
			'group' => '',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		$uniq = $args['unique'] ? 'DISTINCT ' : '';
		$from = self::$wpdb->prefix."strack_ev ev INNER JOIN ".self::$wpdb->prefix."strack_st st ON ev.id = st.id";
		$where = " WHERE event_type = '".$args['event_type']."'";
		if( !empty($args['where']))
		{
			foreach( $args['where'] as $ob )
			{
				if(!empty($ob) && is_array($ob) && count($ob) == 2)
				{
					$where.= !empty($ob[0]) && !empty($ob[1]) ? " AND ".$ob[0]." = '".$ob[1]."'" : '';
				}
			}
		}
		$where.= !empty($args['between']) ? " AND ".self::sql_conditions(array('condition' => 'between', 'key' => $args['between']['key'], 'val' => $args['between']['val'], 'and' => 0)) : '';
		
		$count = 'ev.'.$args['count'];
		$group = !empty($args['group']) ? " GROUP BY ".$args['group'] : '';
		$order = !empty($args['order']) ? " ORDER BY ".$args['order'] : '';
		
		$select = $args['select'] == 'count' ? $count.", COUNT(*) counthits" : $args['select'];
		//echo "SELECT COUNT(".$uniq.$count.") counthits FROM ".$from.$where.$group.$order;
		return self::get_results(array(
			'sql' => "SELECT ".$select." FROM ".$from.$where.$group.$order
		));
	}
	
	
	
	
	
	/**
	 * Count Impressions
	 */
	public static function count_impressions( $args = array())
	{
		self::init();
		
		$defaults = array(
			'key' => 'banner_id',
			'val' => 0,
			'where' => '',
			'between' => array(),
			'order' => '',
			'group' => '',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		$uniq = $args['unique'] ? 'DISTINCT ' : '';
		$where_or_and = !empty($args['val']) ? " AND " : " WHERE ";
		$where_or_and_2 = !empty($args['val']) || !empty($args['where']) ? " AND " : " WHERE ";
		
		$from = self::$wpdb->prefix."strack_st";
		$where = !empty($args['val']) ? " WHERE ".$args['key']." = '".$args['val']."'" : '';
		$where.= !empty($args['where']) ? $where_or_and.$args['where'] : '';
		$where.= !empty($args['between']) ? $where_or_and_2.self::sql_conditions(array('condition' => 'between', 'key' => $args['between']['key'], 'val' => $args['between']['val'], 'and' => 0)) : '';
		$count = !empty($args['val']) && !empty($args['key']) ? 'ip' : 'ip';
		$group = !empty($args['val']) && !empty($args['key']) ? 'banner_id' : '';
		$group = !empty($args['group']) ? $args['group'] : $group;
		$_group = !empty($group) ? " GROUP BY ".$group : '';
		$order = !empty($args['order']) ? " ".sprintf($args['order'],'') : '';
		
		return intval( self::get_var(array(
			'sql' => "SELECT COUNT(".$uniq.$count.") counthits FROM ".$from.$where.$_group.$order) 
		));
	}
	
	
	/**
	 * Impressions data
	 */
	public static function impressions_data( $args = array())
	{
		self::init();
		
		$defaults = array(
			'key' => 'banner_id',
			'val' => 0,
			'where' => '',
			'between' => array(),
			'order' => '',
			'group' => '',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		$uniq = $args['unique'] ? 'DISTINCT ' : '';
		$where_or_and = !empty($args['val']) ? " AND " : " WHERE ";
		$where_or_and_2 = !empty($args['val']) || !empty($args['where']) ? " AND " : " WHERE ";
		
		$from = self::$wpdb->prefix."strack_st";
		$where = !empty($args['val']) ? " WHERE ".$args['key']." = '".$args['val']."'" : '';
		$where.= !empty($args['where']) ? $where_or_and.$args['where'] : '';
		$where.= !empty($args['between']) ? $where_or_and_2.self::sql_conditions(array('condition' => 'between', 'key' => $args['between']['key'], 'val' => $args['between']['val'], 'and' => 0)) : '';
		$group = !empty($args['val']) && !empty($args['key']) ? 'ip' : '';
		$group = !empty($args['group']) ? $args['group'] : $group;
		$_group = !empty($group) ? " GROUP BY ".$group : '';
		$order = !empty($args['order']) ? " ORDER BY ".sprintf($args['order'],'') : '';
		
		
		return self::get_results(array(
			'sql' => "SELECT * FROM ".$from.$where.$_group.$order) 
		);
	}
	
	
	/**
	 * Count Clicks
	 */
	public static function count_clicks( $args = array())
	{
		self::init();
		
		$defaults = array(
			'key' => 'banner_id',
			'val' => 0,
			'where' => '',
			'between' => array(),
			'order' => '',
			'group' => '',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		$uniq = $args['unique'] ? 'DISTINCT ' : '';
		$where_or_and = !empty($args['val']) ? " AND " : " WHERE ";
		$where_or_and_2 = !empty($args['val']) || !empty($args['where']) ? " AND " : " WHERE ";
		
		$from = self::$wpdb->prefix."strack_ev ev INNER JOIN ".self::$wpdb->prefix."strack_st st ON ev.id = st.id";
		$where = !empty($args['val']) ? " WHERE st.".$args['key']." = '".$args['val']."'" : '';
		$where.= !empty($args['where']) ? $where_or_and.$args['where'] : '';
		$where.= !empty($args['between']) ? $where_or_and_2.self::sql_conditions(array('condition' => 'between', 'key' => 'st.'.$args['between']['key'], 'val' => $args['between']['val'], 'and' => 0)) : '';
		$count = !empty($args['val']) && !empty($args['key']) ? 'st.ip' : 'st.banner_id';
		$group = !empty($args['val']) && !empty($args['key']) ? 'st.banner_id' : 'st.ip';
		$group = !empty($args['group']) ? $args['group'] : $group;
		$_group = " GROUP BY ".$group;
		$order = !empty($args['order']) ? " ".sprintf($args['order'],'ev.') : '';
		
		return intval( self::get_var(array(
			'sql' => "SELECT COUNT(".$uniq.$count.") counthits FROM ".$from.$where.$_group.$order
		)));
	}
	
	
	/**
	 * Clicks Data
	 */
	public static function clicks_data( $args = array())
	{
		self::init();
		
		$defaults = array(
			'key' => 'banner_id',
			'val' => 0,
			'where' => '',
			'between' => array(),
			'order' => '',
			'group' => '',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		$uniq = $args['unique'] ? 'DISTINCT ' : '';
		$where_or_and = !empty($args['val']) ? " AND " : " WHERE ";
		$where_or_and_2 = !empty($args['val']) || !empty($args['where']) ? " AND " : " WHERE ";
		
		$from = self::$wpdb->prefix."strack_ev ev INNER JOIN ".self::$wpdb->prefix."strack_st st ON ev.id = st.id";
		$where = !empty($args['val']) ? " WHERE st.".$args['key']." = '".$args['val']."'" : '';
		$where.= !empty($args['where']) ? $where_or_and.$args['where'] : '';
		$where.= !empty($args['between']) ? $where_or_and_2.self::sql_conditions(array('condition' => 'between', 'key' => 'st.'.$args['between']['key'], 'val' => $args['between']['val'], 'and' => 0)) : '';
		$group = !empty($args['val']) && !empty($args['key']) ? 'st.ip' : 'st.banner_id';
		$group = !empty($args['group']) ? $args['group'] : $group;
		$_group = " GROUP BY ".$group;
		$order = !empty($args['order']) ? " ".sprintf($args['order'],'ev.') : '';
		
		return self::get_results(array(
			'sql' => "SELECT * FROM ".$from.$where.$_group.$order
		));
	}
	
	
	
	/**
	 * SQL conditions
	 */
	public static function sql_conditions( $args = array())
	{
		$defaults = array(
			'key' => '',
			'val' => '',
			'condition' => 'equals',
			'prefix' => '',
			'and' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		$and = $args[ 'and' ] ? 'AND ' : '';
		
		switch ( $args[ 'condition' ] ) 
		{
			case 'not_equal_to':
				$where = " ".$and.$args[ 'prefix' ].$args['key']." <> ".$args['val'];
			break;
			case 'greater_than':
				$where = " ".$and.$args[ 'prefix' ].$args['key']." > ".$args['val'];
				break;
			case 'less_than':
				$where = " ".$and.$args[ 'prefix' ].$args['key']." < ".$args['val'];
				break;
			case 'between':
				$range = !is_array($args['val']) ? explode(',', $args['val']) : $args['val'];
				$where = " ".$and."(".$args[ 'prefix' ].$args['key']." BETWEEN '".$range[0]."' AND '".$range[1]."')";
				break;
			default:
				$where = " ".$and.$args[ 'prefix' ].$args['key']." = '".$args['val']."'";
				break;
		}
		
		return $where;
	}
	


	
	
	/**
	 * Insert row into database and return the ID
	 */
	public static function insert_row($data = array(), $table = '')
	{
		if( empty( $data ) || empty( $table ) ) 
		{
			return -1;
		}
		self::init();
		
		//echo print_r($data,true).'<br><br>';
		// Remove unwanted characters (SQL injections)
		$data_keys = array();
		foreach (array_keys($data) as $key)
		{
			$data_keys[] = sanitize_key($key);
		}
       
		self::$wpdb->query(self::$wpdb->prepare("
			INSERT IGNORE INTO ".$table." (".implode(", ", $data_keys).')
			VALUES ('.substr(str_repeat('%s,', count($data)), 0, -1).")", $data));
		//echo intval(self::$wpdb->insert_id).'<br>';
		return intval(self::$wpdb->insert_id);
	}
	
	
	/**
	 * Update existing DB row
	 */
	public static function update_row($data = array(), $table = '')
	{
		if(empty($data) || empty($table))
		{
			return -1;
		}
		self::init();

		// Move the ID at the end of the array
		$id = $data['id'];
		unset($data['id']);

		// Remove unwanted characters (SQL injections, anyone?)
		$data_keys = array();
		foreach (array_keys($data) as $key)
		{
			$data_keys[] = sanitize_key($key);
		}

		// Add the id at the end
		$data['id'] = $id;

		self::$wpdb->query(self::$wpdb->prepare("
			UPDATE IGNORE ".$table."
			SET ".implode(' = %s, ', $data_keys)." = %s
			WHERE id = %d", $data));

		return 0;
	}
	
	
	
	/**
	 * Create database table
	 */
	protected static function create_db_table($args = array())
	{
		$defaults = array(
			'sql' => '',
			'table' => '',
			'wpdb' => $GLOBALS[ 'wpdb' ],
		);
		$args = wp_parse_args( $args, $defaults );
		
		$args[ 'wpdb' ]->query($args['sql']);

		// Make sure the table was created successfully
		foreach ($args[ 'wpdb' ]->get_col("SHOW TABLES LIKE '".$args['table']."'", 0) as $table)
		{
			if ($table == $args['table'])
			{ 
				return true;
			}
		}
		return false;
	}
	// end create_db_table
	
	
	
	
	
	/*
	 * Update the database tables.
	 *
	 * @access public
	 * @return void
	*/
	public static function update_tables() 
	{
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->hide_errors();
		
		//$wpdb->query( "ALTER TABLE ".$wpdb->prefix."wppas_st ADD something VARCHAR(39) DEFAULT NULL AFTER existing_something, ADD other_something VARCHAR(39) DEFAULT NULL AFTER another_existing_something" );
		//$wpdb->query( "ALTER TABLE ".$wpdb->prefix."wppas_st DROP COLUMN something, DROP COLUMN other_something" );
		//$wpdb->query( "ALTER TABLE ".$wpdb->prefix."wppas_st CHANGE something new_something INT UNSIGNED DEFAULT 0" );
		
		//$wpdb->query( "ALTER TABLE ".$wpdb->prefix."wppas_ev DROP COLUMN type" );
		//$wpdb->query( "ALTER TABLE ".$wpdb->prefix."wppas_ev CHANGE event_description event_desc VARCHAR(64) DEFAULT NULL" );
	}
	
	
	
	
	/*
	 * Create the database tables the plugin needs to function.
	 *
	 * @access public
	 * @return void
	*/
	public static function create_tables() 
	{
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->hide_errors();
		
		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) 
		{
			if ( ! empty($wpdb->charset ) ) 
			{
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty($wpdb->collate ) )
			{
				$collate .= " COLLATE $wpdb->collate";
			}
		}
		
		// Stats - User data //IF NOT EXISTS
		$sql_strack_st = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "strack_st (
			id INT UNSIGNED NOT NULL auto_increment,
			track_id INT UNSIGNED NOT NULL DEFAULT 0,
			
			ip VARCHAR(39) DEFAULT NULL,
			country VARCHAR(16) DEFAULT NULL,
			ref VARCHAR(2048) DEFAULT NULL,
			search_terms VARCHAR(2048) DEFAULT NULL,
			
			browser VARCHAR(40) DEFAULT NULL,
			platform VARCHAR(15) DEFAULT NULL,
			device VARCHAR(40) DEFAULT NULL,
			language VARCHAR(5) DEFAULT NULL,
			user_agent VARCHAR(2048) DEFAULT NULL,
			
			resolution VARCHAR(12) DEFAULT NULL,
			screen_w SMALLINT UNSIGNED DEFAULT 0,
			screen_h SMALLINT UNSIGNED DEFAULT 0,
		
			content_id INT(10) UNSIGNED DEFAULT 0,
			content_type VARCHAR(64) DEFAULT NULL,
			
			tm INT(10) UNSIGNED DEFAULT 0,
				
			CONSTRAINT PRIMARY KEY (id),
			INDEX idx_".$wpdb->prefix."strack_st_tm (tm)
		) ".$collate.";";
		
		// Stats - Events
		$sql_strack_ev = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "strack_ev (
			event_id INT(10) NOT NULL AUTO_INCREMENT,
			event_type VARCHAR(64) DEFAULT NULL,
			id_1 INT(10) UNSIGNED DEFAULT 0,
			id_2 INT(10) UNSIGNED DEFAULT 0,
			id_3 INT(10) UNSIGNED DEFAULT 0,
			notes VARCHAR(2048) DEFAULT NULL,
			position VARCHAR(32) DEFAULT NULL,
			id INT UNSIGNED NOT NULL DEFAULT 0,
			tm INT(10) UNSIGNED DEFAULT 0,
			
			CONSTRAINT PRIMARY KEY (event_id),
			INDEX idx_".$wpdb->prefix."strack_ev_tm (tm),
			CONSTRAINT fk_".$wpdb->prefix."strack_st_id FOREIGN KEY (id) REFERENCES ".$wpdb->prefix."strack_st(id) ON UPDATE CASCADE ON DELETE CASCADE
		) ".$collate.";";
		
		
		// Archives
		$sql_strack_st_archive = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "strack_st_archive
			LIKE " . $wpdb->prefix . "strack_st";
		
		$sql_strack_ev_archive = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "strack_ev_archive (
			event_id INT(10) NOT NULL AUTO_INCREMENT,
			event_type VARCHAR(64) DEFAULT NULL,
			id_1 INT(10) UNSIGNED DEFAULT 0,
			id_2 INT(10) UNSIGNED DEFAULT 0,
			id_3 INT(10) UNSIGNED DEFAULT 0,
			notes VARCHAR(2048) DEFAULT NULL,
			position VARCHAR(32) DEFAULT NULL,
			id INT UNSIGNED NOT NULL DEFAULT 0,
			tm INT(10) UNSIGNED DEFAULT 0,
			
			CONSTRAINT PRIMARY KEY (event_id),
			INDEX idx_".$wpdb->prefix."strack_ev_archive (tm)
		) ".$collate.";";
		
		self::create_db_table(array('sql' => $sql_strack_st, $wpdb->prefix.'strack_st', 'wpdb' => $wpdb));
		self::create_db_table(array('sql' => $sql_strack_st_archive, $wpdb->prefix.'strack_st_archive', 'wpdb' => $wpdb));
		self::create_db_table(array('sql' => $sql_strack_ev, $wpdb->prefix.'strack_ev', 'wpdb' => $wpdb));
		self::create_db_table(array('sql' => $sql_strack_ev_archive, $wpdb->prefix.'strack_ev_archive', 'wpdb' => $wpdb));
	}
	
	
	
	
	/**
	 * EXPORT STATS
	 *
	 */
	public static function export_stats($args = array())
	{
		if( isset($_GET['export']) && !empty($_GET['export']))
		{
			$unique_stats = isset($_GET['uniq']) ? $_GET['uniq'] : 0;
			$unique_str = $unique_stats ? __('Unique','strack') : __('All','strack');
			$group = isset($_GET['group']) ? $_GET['group'] : '';
			$group_id = isset( $_GET['group_id']) ? $_GET['group_id'] : '';
			$limit = !empty($group) && !empty($group_id) ? 1 : 0;
			$time_range = isset($_GET['range']) && !empty($_GET['range']) ? $_GET['range'] : 'today';
			$time = sTrack_Core::time_range(array('condition' => $time_range));
			$start_d = date_i18n('j_M_Y', $time[0]);
			$end_d = date_i18n('j_M_Y', $time[1]);
			$tm_str = $start_d == $end_d ? $start_d : $start_d.'_till_'.$end_d;
			
			$posttype = $limit ? get_post_type($group_id) : '';
			if( $limit && $group == 'id_3' ){
				$posttype = 'user';
			}
			
			$filter_str = $posttype == 'wppas_banners' ? __('Banner','strack') : '';
			$filter_str = $posttype == 'wppas_adzones' ? __('Adzone','strack') : $filter_str;
			$filter_str = empty($filter_str) ? $posttype : $filter_str;
			$title = $limit ? str_replace(' ', '-', get_the_title($group_id)) : '';
			
			$filename = 'statistics_for_'.$filter_str.'_'.$title.'_from_'.$tm_str;
			$impression_data = sTrack_Graph::dataset(array(
				'event_type' => 'impression',
				'where' => array( array($group, $group_id)),
				'group' => $unique_stats ? 'st.ip' : 'ev.event_id',
				'time_range' => $time_range,
				'unique' => $unique_stats
			));
			$click_data = sTrack_Graph::dataset(array(
				'event_type' => 'click',
				'where' => array( array($group, $group_id)),
				'group' => $unique_stats ? 'st.ip' : 'ev.event_id',
				'time_range' => $time_range,
				'unique' => $unique_stats
			));
			$labels = sTrack_Graph::graph_range(array('condition' => $time_range));
			/*
			$clicks_am = self::count_stats(array(
				'event_type' => 'click',
				'group' => $unique_stats ? 'st.ip' : 'ev.id',
				'where' => array( array($group, $group_id)),
				'between' => array('key' => 'ev.tm', 'val' => $time),
				'unique' => $unique_stats,
			));
			$views_am = self::count_stats(array(
				'event_type' => 'impression',
				'group' => $unique_stats ? 'st.ip' : 'ev.event_id',
				'where' => array( array($group, $group_id)),
				'between' => array('key' => 'ev.tm', 'val' => $time),
				'unique' => $unique_stats
			));*/
			
			if( $_GET['export'] == 'csv')
			{
				self::export_csv(array(
					'filename' => $filename.'.csv',
					'labels' => $labels,
					'clicks' => $click_data,
					'views' => $impression_data
				));
			}
			elseif( $_GET['export'] == 'pdf' )
			{
				$user_info = $posttype === 'user' ? get_userdata($group_id) : array();
				self::export_pdf(array(
					'args' => array(
						'group' => $group,
						'group_id' => $group_id,
						'unique' => $unique_stats,
						'time_range' => $time_range
					),
					'title' => $posttype !== 'user' ? get_the_title($group_id) : $user_info->display_name,
					'id' => $group_id,
					'posttype' => $posttype,
					's_date' => $time[0],
					'e_date' => $time[1],
					'filename' => $filename.'.pdf',
					'labels' => $labels,
					'clicks' => $click_data,
					'views' => $impression_data
				));
			}
		}
	}
	
	/**
	 * CSV
	 */
	public static function export_csv($args = array())
	{
		$defaults = array(
			'filename' => 'statistics_' . date('Ymd') . '.csv',
			'export' => 'csv',
			'labels' => array(),
			'clicks' => array(),
			'views' => array()
		);
		$args = wp_parse_args( $args, $defaults );
		
		//print_r($args['labels']['labels']);
		//print_r($args['clicks']);
		$data = array();
		$total = array('views' => 0, 'clicks' => 0);
		foreach($args['labels']['labels'] as $i => $label)
		{
			$views = is_numeric($args['views'][$i]) ? $args['views'][$i] : 0;
			$clicks = is_numeric($args['clicks'][$i]) ? $args['clicks'][$i] : 0;
			$total['views'] = $total['views'] + $views;
			$total['clicks'] = $total['clicks'] + $clicks;
			array_push($data, array(
				'date' => $label, 
				'views' => $views, 
				'clicks' => $clicks,
				'ctr' => sTrack_Core::get_ctr(array('clicks' => $clicks, 'impressions' => $views))
			));
		}
		
		// TOTALS
		$am_rows = count($args['labels']['labels']);
		array_push($data, array('date' => '','views' => '','clicks' => '','ctr' => ''));
		array_push($data, array(
			'date' => __('TOTALS','strack'), 
			'views' => $total['views'],
			'clicks' => $total['clicks'],
			//'views' => '=SUM(B2:B'.($am_rows+1).')', 
			//'clicks' => '=SUM(C2:C'.($am_rows+1).')',
			'ctr' => sTrack_Core::get_ctr(array('clicks' => $total['clicks'], 'impressions' => $total['views']))
		));
		
		/*$data = array(
			array("firstname" => "Mary", "lastname" => "Johnson", "age" => 25),
			array("firstname" => "Amanda", "lastname" => "Miller", "age" => 18),
			array("firstname" => "James", "lastname" => "Brown", "age" => 31),
			array("firstname" => "Patricia", "lastname" => "Williams", "age" => 7),
			array("firstname" => "Michael", "lastname" => "Davis", "age" => 43),
			array("firstname" => "Sarah", "lastname" => "Miller", "age" => 24),
			array("firstname" => "Patrick", "lastname" => "Miller", "age" => 27)
		);*/
		
		header("Content-Disposition: attachment; filename=\"".$args['filename']."\"");
		header("Content-Type: text/csv");
		
		$out = fopen("php://output", 'w');
		
		$flag = false;
		foreach($data as $row) 
		{
			if(!$flag) 
			{
				// display field/column names as first row
				fputcsv($out, array_keys($row), ',', '"');
				$flag = true;
			}
			//array_walk($row, array(__CLASS__, 'cleanData'));
			fputcsv($out, array_values($row), ',', '"');
		}
		
		fclose($out);
		exit;
	}




	/*
	 * Save PDF
	 *
	 * This function creates the PDF invoice
	 * there are 2 options
	 * - view (will show the invoice in the browser)
	 * - save (will save the invoice to your computer)
	 *
	 * @access public
	 * @return html
	*/
	public static function export_pdf($args = array())
	{
		$defaults = array(
			'filename' => 'statistics_' . date('Ymd') . '.pdf',
			'export' => 'pdf',
			'labels' => array(),
			'clicks' => array(),
			'views' => array()
		);
		$args = wp_parse_args( $args, $defaults );
		
		require_once(STRACK_INC_DIR.'/tcpdf/tcpdf_include.php');
			
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->AddPage();

		// set margins
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		include( STRACK_TPL_DIR.'/pdf/stats-pdf.php');

		$pdf->writeHTMLCell(0, 0, '', '', $h, 0, 1, 0, true, '', true);
		
		//ob_end_clean();
		$pdf->Output('stats.pdf', 'I');

		exit();
	}
	
	
	/*public static function cleanData(&$str)
	{
		if($str == 't') $str = 'TRUE';
		if($str == 'f') $str = 'FALSE';
		if(preg_match("/^0/", $str) || preg_match("/^\+?\d{8,}$/", $str) || preg_match("/^\d{4}.\d{1,2}.\d{1,2}/", $str)) {
		  $str = "'$str";
		}
		if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
	}*/


	public static function delete_stats($args = array())
	{
		self::init();
		
		$defaults = array(
			'delete' => 'ev.*, st.*',
			'where' => array(),
			'from' => self::$wpdb->prefix."strack_ev ev INNER JOIN ".self::$wpdb->prefix."strack_st st ON ev.id = st.id"
			//'group' => 'id_1',
			//'id' => 0
		);
		$args = wp_parse_args( $args, $defaults );

		//$from = self::$wpdb->prefix."strack_ev ev INNER JOIN ".self::$wpdb->prefix."strack_st st ON ev.id = st.id";
		$where = '';
		if( !empty($args['where']))
		{
			$n = 0;
			foreach( $args['where'] as $ob )
			{
				if(!empty($ob) && is_array($ob) && count($ob) >= 2)
				{
					$eq = count($ob) === 3 ? $ob[2] : '=';
					$and = !$n ? ' ' : ' AND ';
					$where.= !empty($ob[0]) ? $and.$ob[0]." ".$eq." ".$ob[1] : '';
				}
				$n++;
			}

			$where = " WHERE ".$where;
		}
		//$where = '';
		//echo "DELETE ".$args['delete']." FROM " . $args['from'] . " WHERE ".$where.";";
		//$query = "DELETE ev.* FROM ".$from;
		self::$wpdb->query( "DELETE ".$args['delete']." FROM " . $args['from'] . $where.";" );
		//self::$wpdb->query( "DELETE ".$args['delete']." FROM " . $from . " WHERE ".$args['group']." = ".$args['id'].";" );
	}
	
	
}
// end sTrack_DB
endif;
?>