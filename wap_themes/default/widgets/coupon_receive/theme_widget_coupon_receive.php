<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2013 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_coupon_receive(&$setting,&$render){
    $_return = false;

    $filter['cpns_id'] = $setting['coupons'];
    $_return['coupons_status_url'] = app::get('wap')->router()->gen_url( array( 'app'=>'b2c','ctl'=>'wap_member','act'=>'coupon_status', 'full'=>1, 'arg0'=>implode(',',$filter['cpns_id']) ) );

    $_return['coupons'] = wap_widgets::load('Coupons')->getPromotionCoupons($filter);
    return $_return;
}
?>
