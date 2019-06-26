<?php


class business_ctl_site_activelist extends b2c_frontpage{

    function __construct($app){
        parent::__construct($app);
        $shopname = app::get('b2c')->getConf('system.shopname');
        $this->shopname = $shopname;
        if(isset($shopname)){
            $this->title = app::get('activelist')->_('活动列表').'_'.$shopname;
            $this->keywords = app::get('activelist')->_('活动列表').'_'.$shopname;
            $this->description = app::get('activelist')->_('活动列表').'_'.$shopname;
        }
        
    }

    public function index($file){
         $this->set_tmpl('activelist');
         $this->page('site/activelist.html');
    }

}
