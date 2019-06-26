<?php
/**
 * Created by PhpStorm.
 * User: shaojun
 * Date: 2016/12/6
 * Time: 15:28
 */
class jdsale_misc_jdMessageGet implements base_interface_task{

    function rule() {
        return '*/5 * * * *';
    }

    function exec() {
        kernel::single('jdsale_automessageget')->auto_getmessage();
    }

    function description() {
        return '京东信息推送接口';
    }
}
