<?php
/**
 *
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/14
 * Time: 13:26
 */

class b2c_ctl_admin_member_fywapi extends desktop_controller{

    Var $workground = 'b2c_ctl_admin_order';

    public function __construct($app){
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){

        $this->finder(
            'b2c_mdl_member_fyw_log_api', array(
                'title' => $this->app->_('福员外订单日志'),
                'use_buildin_recycle' => true,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
                'use_buildin_export'=>true,
                'use_view_tab'=>true,
                'force_view_tab'=>true,
            )
        );
    }
}
