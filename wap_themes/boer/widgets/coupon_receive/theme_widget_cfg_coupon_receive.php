<?php

    function theme_widget_cfg_coupon_receive(){

        $data['coupons'] = wap_widgets::load('Coupons')->getReceiveCoupons();

        return $data;
    }
?>
