<?php
function theme_widget_activity(&$setting,&$smarty) {
   	if($setting['start_day']){
        $setting['start_time'] = $setting['start_day'].' '.$setting['start_h'].':'.$setting['start_m'];

        $setting['start_time'] = strtotime($setting['start_time']);
    }
    if($setting['end_day']){
        $setting['end_time'] = $setting['end_day'].' '.$setting['end_h'].':'.$setting['end_m'];
        $setting['end_time'] = strtotime($setting['end_time']);
    }
    $cur_time = time();
return $setting;
}
?>
