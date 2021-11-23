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

if ( ! class_exists( 'sTrack_Graph' ) ) :


class sTrack_Graph {	
	
	
	/**
	 * GET GRAPH STATS DATA
	 */
	public static function dataset($args = array())
	{
		$defaults = array(
			'event_type' => 'impression',
			'where' => array(),
			'group' => 'ev.id',
			'time_range' => 'today',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		
		$ds = array();
		$graph_range = self::graph_range(array('condition' => $args['time_range']));
// 		var_dump($graph_range); exit;
		//print_r($graph_range['labels']);
		//for($h = $h; $h <= count($graph_range['labels']); $h++)
		foreach($graph_range['labels'] as $i => $label)
		{
			// Start by 1 if necessary
			$h = $i === 0 && $label !== 0 ? 1 : $graph_range['labels'][0] !== 0 ? $i+1 : $i;
			//echo $h.' '.$label;
			// Time range
			$tr = array(
				'condition' => $graph_range['time_range'],
				'val' => is_numeric($label) ? $label : $h,
			);
			if( !empty($graph_range['y'])){ $tr['y'] = $graph_range['y']; }
			if( !empty($graph_range['m'])){ $tr['m'] = $graph_range['m']; }
			$tm = !empty($graph_range['tm']) ? $graph_range['tm'][$i] : sTrack_Core::time_range($tr);
			//print_r($tm);
			
			//echo date('H:s',$tm[0]).','.date('H:s',$tm[1]);
			//echo date('d m Y H:i:s',$tm[0]).','.date('d m Y H:i:s',$tm[1]).'<br>';
			// Load stats from DB
			
			array_push($ds, sTrack_DB::count_stats(array(
				'event_type' => $args['event_type'],
				'group' => $args['group'],
				'where' => $args['where'],
				'between' => array('key' => 'ev.tm', 'val' => $tm[0].','.$tm[1]),
				'unique' => $args['unique']
			)));
		}
// 		var_dump($ds); exit;
		return $ds;
	}
	
	
	
	
	/**
	 * GRAPH RANGE
	 */
	public static function graph_range($args = array())
	{
		$defaults = array(
			'condition' => 'today'
		);
		$args = wp_parse_args( $args, $defaults );
		$range = array(); 
		$check = sTrack_Core::last_x_days(array('condition' => $args['condition']));
		$args['condition'] = $check['condition']; 
		
		// Check for custom
		$custom_check = sTrack_Core::custom_range(array('condition' => $args['condition']));
		$args['condition'] = $custom_check['condition'];
		$between = $custom_check['between'];
		
		switch( $args[ 'condition' ] ) 
		{
			case 'last_x_days':
				$range['tm'] = array();
				for($i = 1; $i <= $check['days']; $i++)
				{ 
					$tm = sTrack_Core::time_range(array('condition' => 'past_day', 'val' => $i));
					array_push($range['tm'], array($tm[0], $tm[1])); 
				}
				sort($range['tm']);
				
				$range['labels'] = array();
				//for($i = 1; $i <= $check['days']; $i++)
				foreach($range['tm'] as $i => $tm)
				{
					$lbl = $i == count($range['tm'])-1 ? __('yesterday', 'strack') : date_i18n('D d', $tm[0]);
					array_push($range['labels'], $lbl); 
				}
				
				//sort($range['labels']);
				$range['time_range'] = 'past_day';
				break;
			case 'last_year':
				$range['labels'] = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sept','Oct','Nov','Dec');
				$range['time_range'] = 'month';
				$range['y'] = date_i18n('Y')-1;
				break;
			case 'this_year':
				$range['labels'] = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sept','Oct','Nov','Dec');
				$range['time_range'] = 'month';
				break;
			case 'this_month':
				$am_days = cal_days_in_month(CAL_GREGORIAN, date_i18n('m'), date_i18n('y'));
				$range['labels'] = array();
				for($i = 1; $i <= $am_days; $i++){ array_push($range['labels'], $i); }
				$range['time_range'] = 'day';
				break;
			case 'last_month':
				$y = date_i18n('m') === '01' ? date_i18n('Y')-1 : date_i18n('Y');
				$m = date_i18n('m') === '01' ? 12 : date_i18n('m')-1;
				$am_days = 31; //cal_days_in_month(CAL_GREGORIAN, $m, $y);
				$range['labels'] = array();
				for($i = 1; $i <= $am_days; $i++){ array_push($range['labels'], $i); }
				$range['time_range'] = 'day';
				$range['m'] = $m;
				$range['y'] = $y;
				break;
			case 'this_week':
				$range['tm'] = array(
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") + 1), mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") + 1)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") + 2), mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") + 2)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") + 3),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") + 3)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") + 4),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") + 4)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") + 5),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") + 5)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") + 6),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") + 6)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") + 7),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") + 7))
				);
				$range['labels'] = array(
					'Mon '.date_i18n('d', $range['tm'][0][0]),
					'Tue '.date_i18n('d', $range['tm'][1][0]),
					'Wed '.date_i18n('d', $range['tm'][2][0]),
					'Thu '.date_i18n('d', $range['tm'][3][0]),
					'Fri '.date_i18n('d', $range['tm'][4][0]),
					'Sat '.date_i18n('d', $range['tm'][5][0]),
					'Sun '.date_i18n('d', $range['tm'][6][0])
				);
				$range['time_range'] = 'day';
				
				break;
			case 'last_week':
				$range['tm'] = array(
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") - 6),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") - 6)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") - 5),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") - 5)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") - 4),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") - 4)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") - 3),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") - 3)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") - 2),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") - 2)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") - 1),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") - 1)),
					array(mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N")),mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N")))
				);
				$range['labels'] = array(
					'Mon '.date_i18n('d', $range['tm'][0][0]),
					'Tue '.date_i18n('d', $range['tm'][1][0]),
					'Wed '.date_i18n('d', $range['tm'][2][0]),
					'Thu '.date_i18n('d', $range['tm'][3][0]),
					'Fri '.date_i18n('d', $range['tm'][4][0]),
					'Sat '.date_i18n('d', $range['tm'][5][0]),
					'Sun '.date_i18n('d', $range['tm'][6][0])
				);
				$range['time_range'] = 'day';
				
				break;
			case 'yesterday':
				$range['labels'] = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24);
				$range['time_range'] = 'yesterday';
				break;
			case 'custom':
			
				/*$datetime1 = new DateTime(date_i18n('Y', $between[0]).'-'.date_i18n('m', $between[0]).'-'.date_i18n('d', $between[0]).' 00:00:00');//start time
				$datetime2 = new DateTime(date_i18n('Y', $between[1]).'-'.date_i18n('m', $between[1]).'-'.(date_i18n('d', $between[1])+1).' 00:00:00');//end time
				$interval = $datetime1->diff($datetime2);
				echo $interval->format('%Y years %m months %d days %H hours %i minutes %s seconds');//00 years 0 months 0 days 08 hours 0 minutes 0 seconds*/

				$diff = $between[1] - $between[0];

				$years = floor($diff / (365*60*60*24));
				$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
				$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
				//$days = floor($diff / (60 * 60 * 24));
			    //printf("%d years, %d months, %d days\n", $years, $months, $days);
				$range['labels'] = array();
				
				if($years != 0)
				{
					$range['time_range'] = 'year';
					$range['labels'] = array(date_i18n('Y', $between[0])-1,date_i18n('Y', $between[0]),date_i18n('Y', $between[0])+1);
					$range['y'] = date_i18n('Y', $between[0]);
				}
				elseif($months != 0)
				{
					$am_days = cal_days_in_month(CAL_GREGORIAN, date_i18n('m', $between[0]), date_i18n('y', $between[0]));
					for($i = 1; $i <= $am_days; $i++){ array_push($range['labels'], $i); }
					$range['time_range'] = 'day';
					$range['y'] = date_i18n('Y');
					$range['m'] = date_i18n('m', $between[0]);
				}
				elseif($days != 0)
				{
					$am_days = cal_days_in_month(CAL_GREGORIAN, date_i18n('m', $between[0]), date_i18n('y', $between[0]));
					for($i = 1; $i <= $am_days; $i++){ array_push($range['labels'], $i); }
					$range['time_range'] = 'day';
					$range['y'] = date_i18n('Y');
					$range['m'] = date_i18n('m', $between[0]);
				}
				break;
			default:
				// today
				$range['labels'] = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24);
				$range['time_range'] = '';
				break;	
		}
// 		var_dump($range); exit;
		return wp_parse_args($range, array('order' => '', 'y' => '', 'm' => '', 'd' => ''));
	}
	
	
	
	
	
	/**
	 * GRAPH JS
	 */
	public static function graph_js($args = array())
	{
		$defaults = array(
			'graph' => 'stats_graph',
			'group' => '',
			//'labels' => json_encode(array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24)),
			'where' => array(),
			'time_range' => 'today',
			'unique' => 0
		);
		$args = wp_parse_args( $args, $defaults );
		$labels = self::graph_range(array('condition' => $args['time_range']));
		$labels = json_encode($labels['labels']);

		$args['group'] = empty($args['group']) ? 'id_1' : $args['group'];
		//$group_by = $args['group'] === 'id_1' ? 'ev.event_id' : 'ev.'.$args['group'].',ev.id';
		
		if( $args['group'] === 'id_1' )
		{
			$group_by = 'ev.event_id';
		}
		elseif( $args['group'] === 'id_3' )
		{
			$group_by = 'ev.event_id';
		}
		else
		{
			$group_by = 'ev.'.$args['group'].',ev.id';
		}

		$datasets = array(
			array(
				'fill' => false,
				'label' => __('Views','strack'),
				'backgroundColor' => 'rgba(93, 180, 252, 0.5)',
				'borderColor' => 'rgba(93, 180, 252, 0.5)',
				'data' => self::dataset(array(
					'event_type' => 'impression',
					'where' => $args['where'],
					//'group' => $args['unique'] ? 'st.ip' : 'ev.event_id',
					'group' => $args['unique'] ? 'ev.'.$args['group'] : $group_by,
					'time_range' => $args['time_range'],
					'unique' => $args['unique']
				))
			),
			array(
				'fill' => true,
				'label' => __('Clicks','strack'),
				'backgroundColor' => 'rgba(242, 62, 95, 0.5)',
				'borderColor' => 'rgba(242, 62, 95, 0.5)',
				'data' => self::dataset(array(
					'event_type' => 'click',
					'where' => $args['where'],
					//'group' => $args['unique'] ? 'st.ip' : 'ev.id',
					'group' => $args['unique'] ? 'ev.'.$args['group'] : $group_by,
					'time_range' => $args['time_range'],
					'unique' => $args['unique']
				))
			)
		);
		
		$js = '';
		$js.= '<script type="text/javascript">';
		$js.= 'var config = {';
			$js.= 'type: "line",';
			$js.= 'data: {';
				$js.= 'labels:'.$labels.',';
				$js.= 'datasets:'.json_encode($datasets);
			$js.= '},';
			$js.= 'options: {';
				$js.= 'responsive: true,';
				$js.= 'title:{';
					$js.= 'display:false,';
					$js.= 'text:"'.__('Statistics','strack').'"';
				$js.= '},';
				$js.= 'tooltips: {';
					//$js.= 'mode: "index",';
					$js.= 'intersect: false,';
				$js.= '},';
				$js.= 'hover: {';
					//$js.= 'mode: "nearest",';
					$js.= 'intersect: false';
				$js.= '},';
				$js.= 'scales: {';
					$js.= 'xAxes: [{';
						$js.= 'display: true,';
						$js.= 'scaleLabel: {';
							$js.= 'display: false,';
							$js.= 'labelString: "'.__('Month','strack').'"';
						$js.= '}';
					$js.= '}],';
					$js.= 'yAxes: [{';
						$js.= 'ticks: {';
                        $js.= 'min: 0,';
                        $js.= 'beginAtZero: true';
						$js.= '},';
						$js.= 'display: true,';
						$js.= 'scaleLabel: {';
							$js.= 'display: false,';
							$js.= 'labelString: "Value"';
						$js.= '}';
					$js.= '}]';
				$js.= '}';
			$js.= '}';
		$js.= '};';
			
		//$js.= 'window.onload = function(){';
		$js.= 'jQuery(document).ready(function($){';
			$js.= '$(window).load(function(){';
			//$js.= '$(document).ready(function(){';
				$js.= 'console.log("Create Chart");';
				$js.= 'var ctx = document.getElementById("canvas").getContext("2d");';
				$js.= 'window.myLine = new Chart(ctx, config);';
			$js.= '});';   
		$js.= '});';
		$js.= '</script>';
		
		return $js;	
	}
	
}
endif;
?>