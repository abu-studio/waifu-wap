<?php
    class b2c_ctl_wap_brandcenter extends wap_frontpage
    {

        public function __construct(&$app) {
            parent::__construct($app);
            $shopname = app::get('wap')->getConf('wap.name');
            $this->shopname = $shopname;
            if ( isset($shopname) ) {
                $this->title = app::get('b2c')->_('品牌中心页').'_'.$shopname;
                $this->keywords = app::get('b2c')->_('品牌中心页').'_'.$shopname;
                $this->description = app::get('b2c')->_('品牌中心页').'_'.$shopname;
            }
            $this->_response->set_header('Cache-Control', 'no-store, no-cache');
        }
        public function index()
        {
            $this->set_tmpl('brandcenter');
            $this->page('wap/brandcenter/index.html');
        }
    }
