<?php

 
class groupbuy_misc_order implements base_interface_task{

    function rule() {
	return '*/1 * * * *';
    }

    function exec() {
	kernel::single('groupbuy_auto_pay')->exec_auto();
    }

    function description() {
	return '自动取消团购订单';
    }
    
    
    

}
