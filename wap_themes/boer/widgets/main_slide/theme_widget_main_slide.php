<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2013 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


function theme_widget_main_slide(&$setting,&$render){
    $setting['allimg']="";
    $setting['allurl']="";
    if($system->theme){
        $theme_dir = kernel::base_url().'/wap_themes/'.$smarty->theme;
    }else{
        $theme_dir = kernel::base_url().'/wap_themes/'.app::get('wap')->getConf('current_theme');
    }
    if(!$setting['pic']){
        foreach($setting['img'] as $value){
            $setting['allimg'].=$rvalue."|";
            $setting['allurl'].=urlencode($value["url"])."|";
        }
    }else{
        foreach($setting['pic'] as $key=>&$value){
            if($value['link']){
                if($value['start_day']){
                    $value['start_time'] = $value['start_day'].' '.$value['start_h'].':'.$value['start_m'];

                    $value['start_time'] = strtotime($value['start_time']);
                }
                if($value['end_day']){
                    $value['end_time'] = $value['end_day'].' '.$value['end_h'].':'.$value['end_m'];
                    $value['end_time'] = strtotime($value['end_time']);
                }
                $setting['cur_time'] = time().rand(100,999);
                $cur_time = time();
                /*if(isset($value['start_time']) && $cur_time < $value['start_time']){
                    unset($setting['pic'][$key]);
                    continue;
                }
                if(isset($value['end_time']) && $cur_time > $value['end_time']){
                    unset($setting['pic'][$key]);
                    continue;
                }*/

                if($value["url"]){
                    $value["linktarget"]=$value["url"];
                }
                $setting['allimg'].=$rvalue."|";
                $setting['allurl'].=urlencode($value["linktarget"])."|";
                $setting['pic'][$key]['link'] = str_replace('%THEME%',$theme_dir,$value['link']);

            }
        }
    }
    return $setting;
}

?>
