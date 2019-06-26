<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/29
 * Time: 14:31
 */
class b2c_ctl_admin_member_fyworderRefund extends desktop_controller{

    Var $workground = 'b2c_ctl_admin_order';

    public function __construct($app){
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){

        $this->finder(
            'b2c_mdl_orders_fyw', array(
                'title' => $this->app->_('福员外退款单'),
                'use_buildin_recycle' => false,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter' => true,
                'base_filter' =>array('order_status' => '2'),
                'use_buildin_export'=>true,
                'use_view_tab'=>true,
                'force_view_tab'=>true,
            )
        );
    }
}