<?php

 
class groupbuy_misc_ship implements base_interface_task{

    function rule() {
	return '*/1 * * * *';
    }

    function exec() {
	kernel::single('groupbuy_auto_ship')->exec_auto();
    }

    function description() {
	return '团购自动处罚未及时发货的商家';
    }
    
    
    

}
