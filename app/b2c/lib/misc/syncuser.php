<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/25
 * Time: 9:55
 */
class b2c_misc_syncuser  implements  base_interface_task{
    //执行计划任务的方法
    function exec() {
        kernel::single('b2c_fuyuanwai_api_sync')->auto_sync_users();
    }

    //计划任务的默认描述
    function description(){
        return '同步会员信息到福员外';
    }

    //规则, 和linux crontab的规是一样一样的
    function rule(){
        return '00 23 * * *';
    }
}