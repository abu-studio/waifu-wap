<?php
/**
 * Created by PhpStorm.
 * User: shaojun
 * Date: 2016/12/8
 * Time: 10:55
 */
class jdsale_misc_checkJdOrders implements base_interface_task{

    function rule() {
        return '30 00 * * *';
    }

    function exec() {
		$day = app::get("b2c")->getConf("to_check_orders_time") ? app::get("b2c")->getConf("to_check_orders_time") : 1;
		//to_check_orders_time
        kernel::single('jdsale_checkjdorders')->checkOrders($day);

    }


    function description() {
        return '京东对账接口';
    }
}
