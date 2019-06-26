<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2013 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_topbarmember($setting,&$smarty){
    $aMinfo = kernel::single("b2c_cart_objects")->get_current_member();
    if (!empty($aMinfo)) 
    {
       $result['header_point'] =$aMinfo['point'];
    }else{
       $result['header_point'] =0;
    }
    return $result;
}
?>
