<?php 
global $wpdb, $wppas_stats;

wp_enqueue_script('strack_chart');
wp_enqueue_script('strack_heatmap');
wp_enqueue_style('strack_style');
wp_enqueue_style('strack_font_awesome_style');

$pg = isset($_GET['pg']) ? $_GET['pg'] : '';

//echo date('d.m.Y', 1553256015).' ';
if( isset($_POST['remove_stats_days']) && !empty($_POST['remove_stats_days']))
{
	$remove = $_POST['remove_stats_days'];
	if( is_numeric($remove))
	{
		$timestamp = strtotime('-'.$remove.' days', current_time('timestamp'));
		//echo date('d . m . Y', $timestamp);
		sTrack_DB::delete_stats(array('delete' => '', 'from' => $GLOBALS[ 'wpdb' ]->prefix.'strack_st', 'where' => array(
				array('tm', $timestamp, '<')
			) 
		));
	}
	else
	{
		if($remove === 'all')
		{
			sTrack_DB::delete_stats(array('delete' => '', 'from' => $GLOBALS[ 'wpdb' ]->prefix.'strack_st'));
		}
	}
}

$html = '';
$h = '';
$unique_stats = isset($_GET['uniq']) ? $_GET['uniq'] : 0;
$unique_str = $unique_stats ? __('Unique','strack') : __('All','strack');
$group = isset($_GET['group']) ? $_GET['group'] : '';
$group_id = isset( $_GET['group_id']) ? $_GET['group_id'] : '';
$group_and = !empty($group) && !empty($group_id) ? " AND ".$group." = ".$group_id : '';
$group_where = !empty($group) && !empty($group_id) ? $group." = ".$group_id : "";

$time_range = isset($_GET['range']) && !empty($_GET['range']) ? $_GET['range'] : 'today';


//echo sTrack_Core::get_country( sTrack_Core::get_visitor_ip() );

/*echo intval( sTrack_DB::get_results(array(
	'sql' => "SELECT ev.event_id, COUNT(*) counthits FROM ".$GLOBALS[ 'wpdb' ]->prefix."strack_ev ev INNER JOIN ".$GLOBALS[ 'wpdb' ]->prefix."strack_st st ON ev.id = st.id GROUP BY ev.id"
)));*/


/*echo $cnt = sTrack_DB::count_stats(array(
	'event_type' => 'impression',
	'group' => 'ev.id',
	'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range))),
	'unique' => $unique_stats,
));*/

/*$t = sTrack_DB::get_results(array(
	'sql' => "SELECT COUNT(event_id) hits FROM ".$GLOBALS[ 'wpdb' ]->prefix."strack_ev GROUP BY id"
));
echo $GLOBALS[ 'wpdb' ]->num_rows;
//print_r($t);*/

/*$am_banners = sTrack_DB::count_stats(array(
	'event_type' => 'impression',
	'group' => 'ev.id_1',
	'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range)))
));
echo $am_banners;
$am_pages = sTrack_DB::count_stats(array(
	'event_type' => 'impression',
	'group' => 'st.content_id',
	'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range)))
));
echo $am_pages;
*/

//sTrack_DB::export_stats();


/*$page_views = sTrack_DB::count_page_views(array(
	'between' => array('key' => 'tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range))),
	'group' => $unique_stats ? 'ip' : 'id'
));

$clicks_am = sTrack_DB::count_stats(array(
	'event_type' => 'click',
	'group' => $unique_stats ? 'st.ip' : 'ev.id',
	'where' => array( array($group, $group_id)),
	'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range))),
	'unique' => $unique_stats,
));
$views_am = sTrack_DB::count_stats(array(
	'event_type' => 'impression',
	'group' => $unique_stats ? 'st.ip' : 'ev.event_id',
	'where' => array( array($group, $group_id)),
	'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range))),
	'unique' => $unique_stats
));*/

/*$impr_data = sTrack_DB::load_stats(array(
	'event_type' => 'impression',
	'where' => array( array($group, $group_id)),
	'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range))),
	'group' => $unique_stats ? 'st.ip' : 'ev.event_id',
	'unique' => $unique_stats,
));
$clicks_position = sTrack_DB::load_stats(array(
	'event_type' => 'click',
	'select' => 'ev.position',
	'where' => array( array($group, $group_id)),
	'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range))),
	'group' => $unique_stats ? 'st.ip' : 'ev.id',
	'unique' => $unique_stats,
));*/
//echo count($impr_data);
//echo '<pre>'.print_r($clicks_data,true).'</pre>';
?>

<div class="wrap strack">
    <h2 class="messages-position"></h2>
    
    <div style="width:70%; display:inline-block;">
    	
		<?php 
		if( $pg === 'settings' )
		{
			$h.= '<div class="input_container">';
				$h.= '<h1 class="stats_title">';
					//$h.= '<span class="ctr inf">All</span>';
					$h.= __('Settings','strack');
				$h.= '</h1>';
			$h.= '</div>';

			$h.= '<div class="input_container" style="margin-top: 40px;">';
				$h.= '<h2>'.__('Remove Stats','strack').'</h2>';
				$h.= '<form method="post" action="admin.php?page=strack-statistics&pg=settings">';
					$h.= '<div>';
						$h.= '<select name="remove_stats_days">';
							$h.= '<option value="">'.__('-- Select --','strack').'</option>';
							$h.= '<option value="all">'.__('Remove ALL stats','strack').'</option>';
							for($i = 1; $i <= 365; $i++)
							{
								$h.= '<option value="'.$i.'">'.sprintf(__('Remove all stats older then %s days','strack'), $i).'</option>';
							}
						$h.= '</select>';
					$h.= '</div>';
					$h.= '<div>';
						$h.= '<p>'.__('<strong>Note</strong> removing stats cannot be undone!','strack').'</p>';
						$h.= '<input type="submit" class="button button-primary" value="Remove Stats" />';
					$h.= '</div>';
				$h.= '</form>';
			$h.= '</div>';
		}
		else
		{
			$h.= sTrack_Tpl::stats_header(array(
				'group' => $group,
				'group_id' => $group_id,
				'unique' => $unique_stats,
				'time_range' => $time_range
			)); 
			
			$h.= '<div class="input_container" style="margin-top: 40px;">';
				$h.= '<h1 class="stats_title">';
					$h.= __('Graph','strack');
				$h.= '</h1>';
				$h.= '<div style="position: absolute;top: -2px;right: 102px;color: #999;">'.__('Export').'</div>';
				$h.= '<a href="'.$_SERVER['REQUEST_URI'].'&export=csv" class="strack_export_btn" title="'.__('Export to CSV','strack').'" target="_blank">';
					//$h.= '<i class="fas fa-file-csv"></i>';
					$h.= '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="file-csv" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" style="width: 20px;height: 16px;"><path fill="currentColor" d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm-96 144c0 4.42-3.58 8-8 8h-8c-8.84 0-16 7.16-16 16v32c0 8.84 7.16 16 16 16h8c4.42 0 8 3.58 8 8v16c0 4.42-3.58 8-8 8h-8c-26.51 0-48-21.49-48-48v-32c0-26.51 21.49-48 48-48h8c4.42 0 8 3.58 8 8v16zm44.27 104H160c-4.42 0-8-3.58-8-8v-16c0-4.42 3.58-8 8-8h12.27c5.95 0 10.41-3.5 10.41-6.62 0-1.3-.75-2.66-2.12-3.84l-21.89-18.77c-8.47-7.22-13.33-17.48-13.33-28.14 0-21.3 19.02-38.62 42.41-38.62H200c4.42 0 8 3.58 8 8v16c0 4.42-3.58 8-8 8h-12.27c-5.95 0-10.41 3.5-10.41 6.62 0 1.3.75 2.66 2.12 3.84l21.89 18.77c8.47 7.22 13.33 17.48 13.33 28.14.01 21.29-19 38.62-42.39 38.62zM256 264v20.8c0 20.27 5.7 40.17 16 56.88 10.3-16.7 16-36.61 16-56.88V264c0-4.42 3.58-8 8-8h16c4.42 0 8 3.58 8 8v20.8c0 35.48-12.88 68.89-36.28 94.09-3.02 3.25-7.27 5.11-11.72 5.11s-8.7-1.86-11.72-5.11c-23.4-25.2-36.28-58.61-36.28-94.09V264c0-4.42 3.58-8 8-8h16c4.42 0 8 3.58 8 8zm121-159L279.1 7c-4.5-4.5-10.6-7-17-7H256v128h128v-6.1c0-6.3-2.5-12.4-7-16.9z" class=""></path></svg>';
				$h.= '</a>';
				$h.= '<a href="'.$_SERVER['REQUEST_URI'].'&export=pdf" class="strack_export_btn" title="'.__('Export to PDF','strack').'" target="_blank" style="right: 60px;">';
					$h.= '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="file-pdf" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" style="width: 20px;height: 16px;"><path fill="currentColor" d="M181.9 256.1c-5-16-4.9-46.9-2-46.9 8.4 0 7.6 36.9 2 46.9zm-1.7 47.2c-7.7 20.2-17.3 43.3-28.4 62.7 18.3-7 39-17.2 62.9-21.9-12.7-9.6-24.9-23.4-34.5-40.8zM86.1 428.1c0 .8 13.2-5.4 34.9-40.2-6.7 6.3-29.1 24.5-34.9 40.2zM248 160h136v328c0 13.3-10.7 24-24 24H24c-13.3 0-24-10.7-24-24V24C0 10.7 10.7 0 24 0h200v136c0 13.2 10.8 24 24 24zm-8 171.8c-20-12.2-33.3-29-42.7-53.8 4.5-18.5 11.6-46.6 6.2-64.2-4.7-29.4-42.4-26.5-47.8-6.8-5 18.3-.4 44.1 8.1 77-11.6 27.6-28.7 64.6-40.8 85.8-.1 0-.1.1-.2.1-27.1 13.9-73.6 44.5-54.5 68 5.6 6.9 16 10 21.5 10 17.9 0 35.7-18 61.1-61.8 25.8-8.5 54.1-19.1 79-23.2 21.7 11.8 47.1 19.5 64 19.5 29.2 0 31.2-32 19.7-43.4-13.9-13.6-54.3-9.7-73.6-7.2zM377 105L279 7c-4.5-4.5-10.6-7-17-7h-6v128h128v-6.1c0-6.3-2.5-12.4-7-16.9zm-74.1 255.3c4.1-2.7-2.5-11.9-42.8-9 37.1 15.8 42.8 9 42.8 9z" class=""></path></svg>';
				$h.= '</a>';
			$h.= '</div>';
			
			$h.= '<div class="stats_graph">';
				$h.= '<canvas id="canvas"></canvas>';
			$h.= '</div>';
		}

		echo $h;
		?>
		
    </div>
    <!-- end left container (70%) -->
	
	


    <div style="width:29%; display:inline-block; vertical-align: top;">
		
		<?php
		if( $pg === 'settings' )
		{

		}
		else
		{
			?>
			<div class="strack options">
				<h2 style="color:#b3b3b3;border-bottom:solid 1px #b3b3b3;"><?php _e('Options','strack'); ?></h2>
			<?php
				/* Option Buttons */
				// Unique stats
				echo '<div style="padding-bottom:10px;">';
					echo $unique_stats ? sprintf('<a href="%s" class="st_option uniq">%s</a>', str_replace('&uniq=1', '', $_SERVER['REQUEST_URI']), __('View All Stats','strack')) : sprintf('<a href="%s" class="st_option uniq">%s</a>', $_SERVER['REQUEST_URI'].'&uniq=1', __('View Unique Stats','wpproads'));
				echo '</div>';
				
				// Ranges
				$url = sTrack_Core::get_stats_url(array('remove' => '&range='));
				$ranges = array(
					'today' => __('Today','strack'),
					'this_week' => __('This Week','strack'),
					'this_month' => __('This Month','strack'),
					'this_year' => __('This Year','strack')
				);
				echo '<div>';
					foreach( $ranges as $key => $range)
					{
						$selected = $time_range == $key ? ' selected' : '';
						echo sprintf('<a href="%s" class="st_option range'.$selected.'">%s</a>', $url.'&range='.$key, $range);
					}
				echo '</div>';
				$ranges = array(
					'yesterday' => __('Yesterday','strack'),
					'last_week' => __('Last Week','strack'),
					'last_month' => __('Last Month','strack'),
					'last_year' => __('Last Year','strack')
				);
				echo '<div style="padding-bottom:10px;">';
					foreach( $ranges as $key => $range)
					{
						$selected = $time_range == $key ? ' selected' : '';
						echo sprintf('<a href="%s" class="st_option range'.$selected.'">%s</a>', $url.'&range='.$key, $range);
					}
				echo '</div>';
				$ranges = array(
					'last_2_days' => __('Last 2 Days','strack'),
					'last_5_days' => __('Last 5 Days','strack'),
					'last_7_days' => __('Last 7 Days','strack'),
					'last_10_days' => __('Last 10 Days','strack'),
					'last_14_days' => __('Last 14 Days','strack'),
					'last_30_days' => __('Last 30 Days','strack'),
				);
				echo '<div style="padding-bottom:10px;">';
					foreach( $ranges as $key => $range)
					{
						$selected = $time_range == $key ? ' selected' : '';
						echo sprintf('<a href="%s" class="st_option range'.$selected.'">%s</a>', $url.'&range='.$key, $range);
					}
				echo '</div>';
				
				// custom range ... testing ...
				echo '<div style="padding-bottom:10px;">';
					
					// This year months (till current month)
					for($i = 1; $i <= date_i18n('n'); $i++)
					{
						$am_days = cal_days_in_month(CAL_GREGORIAN, $i, date_i18n('Y'));
						$st = mktime(0, 0, 0, $i, 1, date_i18n('Y'));
						$et = mktime(0, 0, 0, $i, $am_days+1, date_i18n('Y'));
						$timestamp = $st.'::'.$et;
						$selected = $time_range == 'custom_'.$timestamp ? ' selected' : '';
						echo sprintf('<a href="%s" class="st_option range'.$selected.'">%s</a>', $url.'&range=custom_'.$timestamp, date_i18n('F', $st).' '.date_i18n('Y'));
					}
					
				echo '</div>';
				
				echo '<div>';
					$st = mktime(0, 0, 0, 1, 1, date_i18n('Y'));
					$et = mktime(0, 0, 0, 1, 1, date_i18n('Y')+1);
					$timestamp = $st.'::'.$et;
					$selected = $time_range == 'custom_'.$timestamp ? ' selected' : '';
					echo sprintf('<a href="%s" class="st_option range'.$selected.'">%s</a>', $url.'&range=custom_'.$timestamp, date_i18n('Y'));
				echo '</div>';
				?>
			
			</div>
			<!-- end options -->
			
			<div class="strack filters">
				<h2 style="color:#b3b3b3;border-bottom:solid 1px #b3b3b3;"><?php _e('Filters','strack'); ?></h2>
				<div class="strack_group_container">
					<select id="strack_group">
							<option value=""><?php _e('Filter','strack'); ?></option>
							<option value="id_1" <?php echo $group == 'id_1' ? 'selected' : ''; ?>><?php _e('Banner','strack'); ?></option>
							<option value="id_3" <?php echo $group == 'id_3' ? 'selected' : ''; ?>><?php _e('Advertiser','strack'); ?></option>
						<option value="id_2" <?php echo $group == 'id_2' ? 'selected' : ''; ?>><?php _e('Adzone','strack'); ?></option>
					</select>
				</div>
				<div class="strack_group_id_container">
					<input type="hidden" id="strack_uri" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
					<select id="strack_group_id" <?php echo empty($group) ? 'style="display:none;"' : ''; ?>>
						<?php echo !empty($group) ? sTrack_Tpl::filter_option_values(array('group' => $group, 'group_id' => $group_id)) : ''; ?>
					</select>
			</div>  
			
			<div class="strack apply_filter">
					<a id="strack_apply_filter" class="button button-secondary" <?php echo empty($group) || empty($group_id) ? 'style="display:none;"' : ''; ?> href="<?php echo $_SERVER['REQUEST_URI']; ?>"><?php _e('Apply','strack');?></a>
			</div>
			
			<div class="loading small" style="display:none;">Loading...</div>
			
			<script>
			jQuery(document).ready(function($){
					$(this).smarTrack.filters();
				});
			</script>
			</div>
			<!-- end .filters -->
			
			<?php
			/**
			 * BANNER PREVIEW AND HEATMAP
			 */
			if($group === 'id_1' && get_post_type($group_id) === 'wppas_banners' || $group === 'id_1' && get_post_type($group_id) === 'adni_banners')
			{
				$shortcode = get_post_type($group_id) === 'wppas_banners' ? '[wpproads id="'.$group_id.'" center=1]' : '[adning id="'.$group_id.'" stats="0"]';
				$html = '';
				$html.= '<h2 style="margin-top: 50px;color:#b3b3b3;border-bottom:solid 1px #b3b3b3;">'.__('Banner Click Heatmap','strack').'</h2>';
				$html.= '<div class="banner_example">';
					$html.= do_shortcode($shortcode);
					$html.= '<div class="heatmap_cont"><div id="heatmap" class="heatmap"></div></div>';
				$html.= '</div>';
				echo $html;
				
				$click_positions = sTrack_DB::load_stats(array(
					'event_type' => 'click',
					'select' => 'ev.position',
					'where' => array( array($group, $group_id)),
					'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $time_range))),
					'group' => $unique_stats ? 'st.ip' : 'ev.id',
					'unique' => $unique_stats,
				));
				$click_data = '';
				foreach($click_positions as $i => $pos)
				{
					if(array_key_exists('position', $pos) && !empty($pos['position']))
					{
						$p = explode(',', $pos['position']);
						$click_data.= '{x:'.round($p[0]).', y:'.round($p[1]).', value:1},';
					}
				}
				?>
				
				<script type="text/javascript" src="<?php echo STRACK_PUB_URL; ?>/assets/js/heatmap.min.js"></script>
				<script>
				jQuery(document).ready(function($){
					
					$(window).load(function() {
						
						var //height = $('.banner_example').find('.b_container')[0].getBoundingClientRect().height,
							//width = $('.banner_example').find('.b_container')[0].getBoundingClientRect().width,
							height = $('.banner_example').find('._ning_cont').height(),
							width = $('.banner_example').find('._ning_cont').width(),
							transform = $('.banner_example').find('._ning_cont').css('transform');
						
						$('#heatmap').css({'width': width, 'height': height, 'transform': transform, 'transform-origin': '0px 0px 0px'});
						
						var heatmapInstance = h337.create({
							container: document.getElementById('heatmap')
						});
						
						
						var data = { 
						max: 0,
						//min: 0, 
						//data: [{x:143.5, y:143, value:1},{x:205.5, y:113, value:1}]
						data: [<?php echo $click_data; ?>]
						};
						
						heatmapInstance.setData(data);
					});
				});
				</script>
			<?php
				// End .banner_example
			}
		}
		?>
		
        
    </div>
	<!-- end right container (29%) -->
	
	<div class="strack_footer">
		<div class="menu_settings_cont">
			<a href="admin.php?page=strack-statistics">Stats</a> | 
			<a href="admin.php?page=strack-statistics&pg=settings">Settings</a>
		</div>
	</div>

</div>
<!-- end .wrap -->

<?php 
// Graph JS
echo sTrack_Graph::graph_js(array(
	'graph' => 'stats_graph',
	'time_range' => $time_range,
	'group' => $group,
	'where' => array( array($group, $group_id)),
	'unique' => $unique_stats
)); 




//echo Wppas_Stats_Core::tracking(array('type' => 'impression'));
//echo Wppas_Stats_Core::tracking(array('type' => 'click'));
//echo '<br><br>';
//echo Wppas_Stats_Core::test_get_track_id();


// Load all banner clicks for specific banner id
/*$banner_id = 0;
$from = $wpdb->prefix."wppas_ev ev INNER JOIN ".$wpdb->prefix."wppas_st st ON ev.id = st.id";
$where = " WHERE st.banner_id = ".$banner_id;

$res = Wppas_Stats_DB::get_results(array(
	'sql' => "SELECT * FROM ".$from.$where." GROUP BY st.id"
));

foreach( $res as $r)
{
	echo print_r($r, true).'<br><br>';
}*/





/*echo '<div>UNIQUE Clicks (for banner id: 2831)</div>';
echo Wppas_Stats_DB::count_clicks(array(
	'key' => 'banner_id', 
	'val' => 2831, 
	'where' => "ev.dt >= ".$GLOBALS[ 'wppas_stats' ]->today,
	'unique' => 1
	//'between' => array('key' => 'dt', 'val' => '1496796244,1496796278')
));*/



//WHERE $_where",
/*echo '<br><br><div>Count UNIQUE Impressions (by IP)</div>';
echo Wppas_Stats_DB::count_impressions(array(
	'where' => "dt >= ".$GLOBALS[ 'wppas_stats' ]->today,
	'unique' => 1
));


echo '<div>Select UNIQUE Impressions (by IP): '.$GLOBALS[ 'wppas_stats' ]->today.' '.date('d.m.Y H:i', $GLOBALS[ 'wppas_stats' ]->today).'</div>';
$resu = Wppas_Stats_DB::get_results(array(
	'sql' => "SELECT * FROM ".$wpdb->prefix."wppas_st WHERE dt >= ".$GLOBALS[ 'wppas_stats' ]->today." GROUP BY ip ORDER BY dt ASC"
));
foreach( $resu as $rs)
{
	//echo print_r($r, true).'<br><br>';
	echo 'IP: <strong>'.$rs['ip'].'</strong> Date: '.date('d.m.Y H:i', $rs['dt']).'<br>';
}




/*
echo '<div>Select ALL Impressions (today): '.$GLOBALS[ 'wppas_stats' ]->today.' '.date('d.m.Y H:i', $GLOBALS[ 'wppas_stats' ]->today).'</div>';
$res = Wppas_Stats_DB::get_results(array(
	'sql' => "SELECT * FROM ".$wpdb->prefix."wppas_st WHERE dt >= ".$GLOBALS[ 'wppas_stats' ]->today." ORDER BY dt ASC"
));
foreach( $res as $i => $r)
{
	//echo print_r($r, true).'<br><br>';
	echo $i.' IP: <strong>'.$r['ip'].'</strong> Date: '.date('d.m.Y H:i', $r['dt']).'<br>';
}*/
?>