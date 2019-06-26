<?php
 

class cardcoupons_ctl_admin_cards_offlinenotifylog extends desktop_controller {
    var $workground = 'cardcoupons.wrokground.card';
    function index(){
        $actions_base = array();
        $this->finder('cardcoupons_mdl_cmpay_offlinenotify_log',$actions_base);
    }
}