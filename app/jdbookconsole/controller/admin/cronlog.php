<?php


class jdbookconsole_ctl_admin_cronlog extends desktop_controller{

    var $workground = 'jdbookconsole.workground.order';

    public function __construct($app){
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){

        $this->finder(
            'jdsale_mdl_cron_log', array(
                'title' => $this->app->_('定时执行任务日志'),
                'base_filter'=>array('cron_kind'=>'jdbook'),
                'use_buildin_recycle' => false,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
				'use_buildin_export'=>true,
            )
        );
    }
}
