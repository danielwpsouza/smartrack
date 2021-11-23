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

if ( ! class_exists( 'sTrack_Tpl' ) ) :


class sTrack_Tpl {	

	
	
	/**
	 * Stats header
	 */
	public static function stats_header($args = array())
	{
		wp_enqueue_style('strack_style');
		
		$defaults = array(
			'group' => '',
			'group_id' => '',
			'unique' => '',
			'time_range' => 'today',
			'show_info_line' => 1
		);
		$args = wp_parse_args( $args, $defaults );
		$html = '';

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
		/*if( $args['unique'] )
		{
			//$group_by = $args['group'] === 'id_1' ? 'ev.id_1' : 'ev.id';
			//$group_by = $args['group'] === 'id_1' ? 'st.ip' : 'ev.id';
			$group_by = 'ev.id';
		}
		else
		{
			$group_by = $args['group'] === 'id_1' ? 'ev.event_id' : 'ev.id_2,ev.id';
		}*/
		//echo $args['group'].' '.$args['group_id'].'<br>';
		
		$clicks_am = sTrack_DB::count_stats(array(
			'event_type' => 'click',
			'group' => $args['unique'] ? 'ev.'.$args['group'] : $group_by, //'ev.id',
			//'group' => $args['unique'] ? 'st.ip' : $group_by, //'ev.id',
			//'group' => $group_by,
			'where' => array( array($args['group'], $args['group_id'])),
			'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $args['time_range']))),
			'unique' => $args['unique']
		));
		$views_am = sTrack_DB::count_stats(array(
			'event_type' => 'impression',
			'group' => $args['unique'] ? 'ev.'.$args['group'] : $group_by, //'ev.id',
			//'group' => $args['unique'] ? 'st.ip' : $group_by, //'ev.event_id',
			//'group' => $group_by,
			'where' => array( array($args['group'], $args['group_id'])),
			'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $args['time_range']))),
			'unique' => $args['unique']
		));
		
		$html.= '<div class="input_container">';
				
				$range_title = self::stats_title(array(
					'group' => $args['group'],
					'group_id' => $args['group_id'],
					'unique' => $args['unique'],
					'time_range' => $args['time_range']
				));
				$html.= $range_title;
        $html.= '</div>';
        
        $html.= '<div class="input_container one_third">';
        	$html.= '<div class="countbox">';
            	$html.= '<div class="views txt">'.__('Views','strack').'</div>';
           	$html.= '<div class="views cont">';
            		$html.= '<span class="number">'.$views_am.'</span>';
            	$html.= '</div>';
           $html.= '</div>';
        $html.= '</div>';
        $html.= '<div class="input_container one_third">';
        	$html.= '<div class="countbox">';
            	$html.= '<div class="clicks txt">'.__('Clicks','strack').'</div>';
           	$html.= '<div class="clicks cont">';
            		$html.= '<span class="number">'.$clicks_am.'</span>';
            	$html.= '</div>';
           $html.= '</div>';
        $html.= '</div>';
        $html.= '<div class="input_container one_third">';
        	$html.= '<div class="countbox">';
            	$html.= '<div class="ctr txt">'.__('CTR','strack').'</div>';
           	$html.= '<div class="ctr cont">';
            		$html.= '<span class="number">';
						$html.= sTrack_Core::get_ctr(array('clicks' => $clicks_am,'impressions' => $views_am));
                  $html.= '</span>';
            	$html.= '</div>';
           $html.= '</div>';
        $html.= '</div>';
		
		if($args['show_info_line'])
		{
			$html.= self::stats_info_line(wp_parse_args( array('clicks_am' => $clicks_am, 'views_am' => $views_am), $args ));
		}
		
		return $html;
	}
	
	
	
	
	public static function stats_info_line($args = array())
	{
		wp_enqueue_style('strack_style');
		
		$defaults = array(
			'group' => '',
			'group_id' => '',
			'unique' => '',
			'time_range' => 'today',
			'views_am' => '',
			'clicks_am' => '',
			'page_views' => ''
		);
		$args = wp_parse_args( $args, $defaults );

		$args['group'] = empty($args['group']) ? 'id_1' : $args['group'];

		// Banner or Adzone
		$type = $args['group'] === 'id_1' ? 'banner' : 'adzone';
		$type = $args['group'] === 'id_3' ? '' : $type;

		$group_by = $args['group'] === 'id_1' ? 'ev.event_id' : 'ev.'.$args['group'].',ev.id';
		
		if( $args['views_am'] === '')
		{
			$args['views_am'] = sTrack_DB::count_stats(array(
				'event_type' => 'impression',
				//'group' => $args['unique'] ? 'st.ip' : 'ev.event_id',
				'group' => $args['unique'] ? 'ev.'.$args['group'] : $group_by,
				'where' => array( array($args['group'], $args['group_id'])),
				'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $args['time_range']))),
				'unique' => $args['unique']
			));
		}	
		if( $args['clicks_am'] === '')
		{
			$args['clicks_am'] = sTrack_DB::count_stats(array(
				'event_type' => 'click',
				//'group' => $args['unique'] ? 'st.ip' : 'ev.id',
				'group' => $args['unique'] ? 'ev.'.$args['group'] : $group_by,
				'where' => array( array($args['group'], $args['group_id'])),
				'between' => array('key' => 'ev.tm', 'val' => sTrack_Core::time_range(array('condition' => $args['time_range']))),
				'unique' => $args['unique'],
			));
		}	
		if( $args['page_views'] === '')
		{
			$args['page_views'] = sTrack_DB::count_page_views(array(
				'between' => array('key' => 'tm', 'val' => sTrack_Core::time_range(array('condition' => $args['time_range']))),
				'group' => $args['unique'] ? 'ip' : 'id'
			));	
		}
		
		$html = '';
		$html.= '<div class="input_container" style="margin-top:30px; color:#999; font-size:15px; line-height:1;">';
        	
			$hi = array(
				__('Some stats for you:','strack'),
				__('This is what I found:','strack'),
				__('Here are the numbers:','strack'),
				__('Looking good:','strack')
			);
			$unq = $args['unique'] ? __('unique','strack') : ''; 
			$html.= '<span style="color:#333;background-color:#ffffc3;padding:0 5px 0 0;">'.$hi[array_rand($hi)].'</span> ';
			$html.= sprintf('<span style="text-decoration:underline;"><strong>%s</strong> %s page views</span> %s, creating a total of <span style="text-decoration:underline;"><strong>%s</strong> %s %s impressions</span> and <span style="text-decoration:underline;"><strong>%s</strong> %s clicks</span>.', $args['page_views'], $unq, lcfirst(self::time_based_string(array('time_range' => $args['time_range']))), $args['views_am'], $unq, $type, $args['clicks_am'], $unq); 
			
        $html.= '</div>';
		
		return $html;
	}
	
	
	


	
	/**
	 * Main Statistics Title
	*/
	public static function stats_title($args = array())
	{
		$defaults = array(
			'group' => '',
			'group_id' => '',
			'unique' => '',
			'time_range' => 'today'
		);
		$args = wp_parse_args( $args, $defaults );
		
		$html = '';
		$unique_str = $args['unique'] ? __('Unique','strack') : __('All','strack');
		$limit = !empty($args['group']) && !empty($args['group_id']) ? 1 : 0;
		$posttype = $limit ? get_post_type($args['group_id']) : '';
		if( $limit && $args['group'] === 'id_3'){
			$posttype = 'user';
		}
		$user_info = $posttype === 'user' ? get_userdata($args['group_id']) : array();
		$title = $limit ? get_the_title($args['group_id']) : '';
		$title = $limit && $posttype === 'user' ? $user_info->display_name : $title;
		
		
		$html.= '<h1 class="stats_title">';
			
			if( !$limit )
			{
				$html.= sprintf(__('%s Statistics %s','strack'), '<span class="ctr inf">'.$unique_str.'</span>', self::time_based_string(array('time_range' => $args['time_range'])));
			}
			else
			{
				$html.= sprintf(__('%s Statistics for %s %s','strack'), '<span class="ctr inf">'.$unique_str.'</span>', '<span class="itm_title">'.$title.'</span>', self::time_based_string(array('time_range' => $args['time_range'])));
			}
			
		$html.= '</h1>';

		//$html.= self::between_string(array('time_range' => $args['time_range']));
		
		return $html;
	}
	
	
	
	public static function between_string($args = array())
	{
		$defaults = array(
			'time_range' => ''
		);
		$args = wp_parse_args( $args, $defaults );

		// Check for custom
		$check = sTrack_Core::custom_range(array('condition' => $args['time_range']));
		$args['time_range'] = $check['condition'];
		$between = $check['between'];

		return sprintf(__('between %s - %s','strack'), date_i18n('d m Y', $between[0]), date_i18n('d m Y', $between[1]));
	}




	public static function time_based_string($args = array())
	{
		$defaults = array(
			'time_range' => 'today',
			'd' => date_i18n('d'),
			'm' => date_i18n('m'),
			'y' => date_i18n('Y'),
			'date' => mktime(0, 0, 0, current_time('n'), current_time('j'), current_time('Y'))
			//'date' => $GLOBALS[ 'wppas_stats' ]->today
		);
		$args = wp_parse_args( $args, $defaults );
		
		$html = '';
		$check = sTrack_Core::last_x_days(array('condition' => $args['time_range']));
		$args['time_range'] = $check['condition'];
		$x_days = $check['days'];
		
		// Check for custom
		$check = sTrack_Core::custom_range(array('condition' => $args['time_range']));
		$args['time_range'] = $check['condition'];
		$between = $check['between'];
		
		switch ( $args[ 'time_range' ] ) 
		{
			case 'last_year':
				$html = sprintf(__('in %s','strack'), date_i18n('Y', mktime(0, 0, 0, $args['m'], $args['d'], $args['y']-1)));
				break;
			case 'last_month':
				$html = sprintf(__('in %s','strack'), date_i18n('F', mktime(0, 0, 0, $args['m']-1, $args['d'], $args['y'])));
				break;
			case 'this_year':
				$html = sprintf(__('in %s','strack'), date_i18n('Y', mktime(0, 0, 0, $args['m'], $args['d'], $args['y'])));
				break;
			case 'this_month':
				$html = sprintf(__('in %s','strack'), date_i18n('F', mktime(0, 0, 0, $args['m'], $args['d'], $args['y'])));
				break;
			case 'this_week':
				$s = mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") + 1);
				$e = mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N") + 7);
				$html = sprintf(__('this week (%s)','strack'), date_i18n('D d', $s).' - '.date_i18n('D d', $e));
				break;
			case 'last_week':
				$s = mktime(0, 0, 0, date_i18n("n"), date_i18n("j") - date_i18n("N") - 6);
				$e = mktime(23, 59, 59, date_i18n("n"), date_i18n("j") - date_i18n("N"));
				$html = sprintf(__('last week (%s)','strack'), date_i18n('D d', $s).' - '.date_i18n('D d', $e));
				break;
			case 'last_x_days':
				$html = sprintf(__('in the last %s days','strack'), $x_days);
				break;
			case 'custom':
				$diff = abs($between[1] - $between[0]);

				$years = floor($diff / (365*60*60*24));
				$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
				$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
				// printf("%d years, %d months, %d days\n", $years, $months, $days);
				$range['labels'] = array();
				
				if($years != 0)
				{
					$html = sprintf(__('in %s','strack'), date_i18n('Y', $between[0]));
				}
				elseif($months != 0)
				{
					$html = sprintf(__('in %s','strack'), date_i18n('F', $between[0]));
				}
				elseif($days != 0)
				{
					$html = sprintf(__('in %s','strack'), date_i18n('F', $between[0]));
				}
				break;
			default:
				//$html = sprintf(__('on %s','strack'), date_i18n('l, M d', $args['date']));
				$html = $args[ 'time_range' ] == 'today' ? __('Today','strack') : __('Yesterday','strack');
				break;
		}
		
		return $html;
	}
	
	
	
	/**
	 * Adds stats icon to banners page on main WP PRO Advertising plugin
	 */
	public static function banner_menu($html = '', $bid = 0)
	{
		// Only return if banner has been saved.
		if( !empty($bid))
		{
			$html.= '<li data-link="admin.php?page=strack-statistics&group=id_1&group_id='.$bid.'">';
				$html.= '<i class="fa fa-area-chart" aria-hidden="true" style="margin-right: 5px;"></i> '.__('smarTrack stats','strack');
			$html.= '</li>';
		}
		
		return $html;
	}
	
	
	
	/**
	 * Filter Option Values
	 */
	public static function filter_option_values($args = array())
	{
		global $pro_ads_advertisers, $pro_ads_banners, $wppas_banner_creator, $wppas_adzone_creator, $pro_ads_adzones;
		
		$defaults = array(
			'group' => '',
			'group_id' => '',
			'options' => array()
		);
		$args = wp_parse_args( $args, $defaults );
		$html = '';
		
		if( empty($args['options']))
		{
			/**
			 * Filters for WPPROADS
			*/
			if( $args['group'] == 'id_1' && class_exists('WPPAS_Banner_Creator') )
			{
				$args['options'] = $wppas_banner_creator->all_banners_query();
				$old = $pro_ads_banners->get_banners();
				$args['options'] = wp_parse_args( $old, $args['options'] );
			}
			if( $args['group'] == 'id_2' && class_exists('WPPAS_Adzone_Creator') )
			{
				$args['options'] = $wppas_adzone_creator->all_adzones_query();
				$old = $pro_ads_adzones->get_adzones();
				$args['options'] = wp_parse_args( $old, $args['options'] );
			}
			if( $args['group'] == 'id_3' && class_exists('Pro_Ads_Advertisers') )
			{
				$args['options'] = $pro_ads_advertisers->get_advertisers();
			}
			
			/**
			 * Filters for ADNING
			*/
			if( $args['group'] == 'id_1' && class_exists('ADNI_CPT') )
			{
					$args['options'] = wp_parse_args( ADNI_CPT::get_posts(), $args['options'] );
			}
			if( $args['group'] == 'id_2' && class_exists('ADNI_CPT') )
			{
					$args['options'] = wp_parse_args( ADNI_CPT::get_posts(array('post_type' => ADNI_CPT::$adzone_cpt)), $args['options'] );
			}
			if( $args['group'] == 'id_3' && class_exists('ADNI_CPT') )
			{
					$advertisers = ADNI_Main::load_advertisers();
					$options = array();
					if(!empty($advertisers))
					{
						foreach($advertisers as $advertiser)
						{
							$user = get_userdata( $advertiser );
							$options[] = array(
								'ID' => $advertiser,
								'post_title' => '(#'.$advertiser.') '.$user->display_name
							);
						}
					}
					$advertisers = json_decode(json_encode((object) $options), FALSE);
					$args['options'] = $advertisers;
			}
		}
		
		$html.= '<option value="">'.__('-- Select Option --').'</option>';
		if(!empty($args['options']))
		{
			foreach($args['options'] as $option)
			{
				$title = !empty($option->post_title) ? $option->post_title : $option->ID;
				$selected = $args['group_id'] == $option->ID ? ' selected' : '';
				$html.= '<option value="'.$option->ID.'"'.$selected.'>'.$title.'</option>';
			}
		}
		
		return $html;
	}
	
}
endif;
?>