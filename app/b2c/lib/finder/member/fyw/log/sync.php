<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/10
 * Time: 10:44
 */
class b2c_finder_member_fyw_log_sync {
    var $detail_basic = '基本信息';

    function __construct($app){
        $this->app = $app;
    }

    //基本信息
    function detail_basic($log_id){
        $render = $this->app->render();
        $mdl_member_fyw_log_sync = app::get('b2c')->model('member_fyw_log_sync');
        $data_tmp =$mdl_member_fyw_log_sync->getRow('*',array('log_id'=>$log_id));

        $render->pagedata['data'] = $data_tmp;
        return $render->fetch('admin/member/fyw/logsync.html');
    }
}