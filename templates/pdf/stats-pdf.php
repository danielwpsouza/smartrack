<?php
$h = '';

// echo '<pre>'.print_r($args, true).'</pre>';

$data = array();
$total = array('views' => 0, 'clicks' => 0);
foreach($args['labels']['labels'] as $i => $label)
{
    $views = is_numeric($args['views'][$i]) ? $args['views'][$i] : 0;
    $clicks = is_numeric($args['clicks'][$i]) ? $args['clicks'][$i] : 0;
    $total['views'] = $total['views'] + $views;
    $total['clicks'] = $total['clicks'] + $clicks;
    $ctr = sTrack_Core::get_ctr(array('clicks' => $clicks, 'impressions' => $views));
    $date = $label;
}

$stats_for_type = __('Banner','adn');
$stats_for_type = $args['posttype'] === 'adni_adzones' ? __('Adzone','adn') : $stats_for_type;
$stats_for_type = $args['posttype'] === 'user' ? __('Advertiser','adn') : $stats_for_type;


$range_title = sTrack_TPL::stats_title(array(
    'group' => $args['args']['group'],
    'group_id' => $args['args']['group_id'],
    'unique' => $args['args']['unique'],
    'time_range' => $args['args']['time_range']
));

        
$h.= '<h1>';
    $h.= sprintf(__('Stats for %s %s', 'adn'), $stats_for_type.': ', $args['title'].' - #'.$args['id']);
$h.= '</h1>';


$h.= '<div style="background-color:#EFEFEF; color:#999; font-size:24px; text-align:center;">';
    $h.= strip_tags($range_title);
    //$h.= sprintf(__('Stats for %s'), date_i18n('l, M d, Y', $args['s_date']));
$h.= '</div><br>';

$h.= '<table>';
		
    $h.= '<tr>';
        $h.= '<td style="text-align:center; color:#FFF;">';
            $h.= '<div style="color:#5db4fc;font-size:16px;">'.__('Views','wpproads').'</div>';
            $h.= '<div style="font-size:16px; background-color:#5db4fc;">'.$total['views'].'</div>';
        $h.= '</td>';
        $h.= '<td style="text-align:center; color:#FFF;">';
            $h.= '<div style="color:#f23e5f;font-size:16px;">'.__('Clicks','wpproads').'</div>';
            $h.= '<div style="font-size:16px;background-color:#f23e5f;">'.$total['clicks'].'</div>';
        $h.= '</td>';
        $h.= '<td style="text-align:center; color:#FFF;">';
            $h.= '<div style="color:#78CA48;font-size:16px;">'.__('CTR','wpproads').'</div>';
            $h.= '<div style="font-size:16px;background-color:#78CA48;">'.$ctr.'</div>';
        $h.= '</td>';
    $h.= '</tr>';
    
$h.= '</table><br><br>';

$h.= '<table style="border: 1px solid #EFEFEF; color:#686868;">';
    
    $time_date = !empty($args['labels']['time_range']) ? __('Date','adn') : __('Time','adn');
    $h.= '<tr>';
        $h.= '<th style="border: 1px solid #EFEFEF; border-bottom:1px solid #666; background-color:#EFEFEF;"><strong style="padding:10px;">'.$time_date.'</strong></th>';
        $h.= '<th style="border: 1px solid #EFEFEF; border-bottom:1px solid #666; background-color:#5db4fc; color:#FFF;text-align:center;"><strong>'.__('Views','wpproads').'</strong></th>';
        $h.= '<th style="border: 1px solid #EFEFEF; border-bottom:1px solid #666; background-color:#f23e5f;color:#FFF;text-align:center;"><strong>'.__('Clicks','wpproads').'</strong></th>';
        $h.= '<th style="border: 1px solid #EFEFEF; border-bottom:1px solid #666; background-color:#78CA48;color:#FFF;text-align:center;"><strong>'.__('CTR','wpproads').'</strong></th>';
    $h.= '</tr>';

    foreach($args['labels']['labels'] as $i => $label)
    {
        $views = is_numeric($args['views'][$i]) ? $args['views'][$i] : 0;
        $clicks = is_numeric($args['clicks'][$i]) ? $args['clicks'][$i] : 0;
        if(!empty($views) || !empty($clicks))
        {
            $h.= '<tr>';
                $h.= '<td style="border: 1px solid #EFEFEF;">'.$label.'</td>'; // date_i18n('G:i',$key)
                $h.= '<td style="border: 1px solid #EFEFEF;text-align:center;"><strong>'.$views.'</strong></td>';
                $h.= '<td style="border: 1px solid #EFEFEF;text-align:center;"><strong>'.$clicks.'</strong></td>';
                $h.= '<td style="border: 1px solid #EFEFEF;text-align:center;"><strong>'.sTrack_Core::get_ctr(array('clicks' => $clicks, 'impressions' => $views)).'</strong></td>';
            $h.= '</tr>';
        }
    }

$h.= '</table>';
?>