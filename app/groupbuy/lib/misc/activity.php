<?php

 
class groupbuy_misc_activity implements base_interface_task{

    function rule() {
	return '*/1 * * * *';
    }

    function exec() {
	kernel::single('groupbuy_auto_activity')->exec_auto();
    }

    function description() {
	return '自动取消团购活动';
    }
    
    
    

}
