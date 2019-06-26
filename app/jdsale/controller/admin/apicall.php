<?php


class jdsale_ctl_admin_apicall extends desktop_controller{

    var $workground = 'jdsale.workground.order';

    public function __construct($app){
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){

        $this->finder(
            'jdsale_mdl_api_log', array(
                'title' => $this->app->_('接口调用日志'),
                'use_buildin_recycle' => true,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
				'use_buildin_export'=>true,
            )
        );
    }
}
