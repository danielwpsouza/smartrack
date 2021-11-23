<?php
/*
 * AJAX REQUEST FUNCTIONS
 *
 * http://codex.wordpress.org/AJAX_in_Plugins
 * For not logged-in users use: add_action('wp_ajax_nopriv_my_action', 'my_action_callback');
*/


/* -------------------------------------------------------------
 * save_event_data
 * ------------------------------------------------------------- */
add_action('wp_ajax_save_event_data', 'save_event_data_callback');
add_action('wp_ajax_nopriv_save_event_data', 'save_event_data_callback');
function save_event_data_callback() 
{
	$pv_id = esc_sql($_POST['id']);
	$event_type = esc_sql($_POST['event_type']);
	$ids = json_decode(stripslashes($_POST['ids']), true);
	$track_id = $_POST['track_id'];
	$pos = json_decode(stripslashes($_POST['pos']), true);
	$position = is_array($pos) ? $pos[0].','.$pos[1] : '';
	$and_id_1 = !empty($ids) && is_array($ids) && array_key_exists('id_1',$ids) && !empty($ids['id_1']) ? " AND id_1 = ".$ids['id_1'] : '';
	
	if( !empty($position))
	{
		//print_r($ids);
		//echo $position.' ';
		//echo $and_id_1;
		//echo $GLOBALS[ 'wpdb' ]->prefix."strack_ev SET position = '".$position."' WHERE event_type = '".$event_type."' AND id = ".$pv_id.$and_id_1." AND position IS NULL";
		//echo is_array($ids) ? 'ja' : 'nee';
		//echo 'oi'.$event_type.$pv_id.$and_id_1;
		$GLOBALS[ 'wpdb' ]->query("UPDATE ".$GLOBALS[ 'wpdb' ]->prefix."strack_ev SET position = '".esc_sql($position)."' WHERE event_type = '".$event_type."' AND id = ".$pv_id.$and_id_1." AND position IS NULL");
	}
	
	/*echo 'oi'.$_POST['pos'];
	echo $event_type.' '.$pv_id.' ';
	echo is_array($pos) ? 'yes '.$position : 'no';*/
	//sTrack_DB::update_row(self::$stats, $GLOBALS[ 'wpdb' ]->prefix.'strack_ev');
	
	/*echo 'Event Data<br>';
	echo 'pv_id:'.$pv_id.'<br>';
	echo 'track_id:'.$track_id.'<br>';
	echo print_r($pos,true).'<br>';
	echo $_POST['ids'].'<br>';
	echo print_r($ids,true).'<br>';*/
	
	exit();
}


/* -------------------------------------------------------------
 * load_filter_options
 * ------------------------------------------------------------- */
add_action('wp_ajax_load_filter_options', 'load_filter_options_callback');
add_action('wp_ajax_nopriv_load_filter_options', 'load_filter_options_callback');
function load_filter_options_callback() 
{
	global $pro_ads_advertisers, $pro_ads_banners, $wppas_banner_creator;
	
	$html = '';
	//$options = array();
	$group = $_POST['group'];
	
	echo sTrack_Tpl::filter_option_values(array('group' => $group));
	exit();
}


/* -------------------------------------------------------------
 * select_filter_option
 * ------------------------------------------------------------- */
add_action('wp_ajax_select_filter_option', 'select_filter_option_callback');
add_action('wp_ajax_nopriv_select_filter_option', 'select_filter_option_callback');
function select_filter_option_callback() 
{
	$group = $_POST['group'];
	$group_id = $_POST['group_id'];
	$url = urldecode($_POST['uri']);
	
	echo $url.'&group='.$group.'&group_id='.$group_id;
	
	exit();
}


/* -------------------------------------------------------------
 * save_event_data
 * ------------------------------------------------------------- */
/*add_action('wp_ajax_strack_update_click', 'strack_update_click_callback');
add_action('wp_ajax_nopriv_strack_update_click', 'strack_update_click_callback');
function strack_update_click_callback() 
{
	$cid = sTrack_DB::get_var(
		array("sql" => "SELECT id FROM ".$GLOBALS[ 'wpdb' ]->prefix . "strack_st WHERE content_id = ".$_POST['content_id']." AND track_id = ".$_POST['track_id']." ORDER BY id DESC LIMIT 1")
	);
		
	$GLOBALS[ 'wpdb' ]->query("UPDATE ".$GLOBALS[ 'wpdb' ]->prefix."strack_ev SET id = '".$_POST['strack_st']."' WHERE event_type = 'click' AND banner_id = ".$_POST['banner_id']."");
}*/
