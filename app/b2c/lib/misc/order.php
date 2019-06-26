<?php

 
class b2c_misc_order implements base_interface_task{

    function rule() {
	return '0 0 */1 * *';
    }

    function exec() {
	kernel::single('b2c_orderautojob')->order_auto_operation();
    }

    function description() {
	return '订单自动处理脚本';
    }
    
    
    

}
