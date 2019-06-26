<?php
/**
 * Created by PhpStorm.
 * User: shaojun
 * Date: 2016/12/8
 * Time: 10:55
 */
class jdsale_misc_finishJdOrders implements base_interface_task{

    function rule() {
        return '00 23 * * *';
    }

    function exec() {
        kernel::single('jdsale_autofinshjdorders')->orders();
        kernel::single('jdsale_checkjdorders')->checkOrders();

    }


    function description() {
        return '京东下单';
    }
}
