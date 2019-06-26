<?php


class jdsale_ctl_admin_aftersales extends desktop_controller{

    var $workground = 'jdsale.workground.order';

    public function __construct($app){
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){

        $this->finder(
            'jdsale_mdl_afs_log', array(
                'title' => $this->app->_('售后申请列表'),
                'base_filter'=>array('order_kind'=>'jdorder'),
                'use_buildin_recycle' => false,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
				'use_buildin_export'=>true,
            )
        );
    }


}
