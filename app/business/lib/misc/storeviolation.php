<?php


class business_misc_storeviolation implements base_interface_task{

    function rule() {
	return '*/1 * * * *';
    }

    function exec() {
	set_time_limit(0); 
	kernel::single('business_mdl_storeviolation')->exec_violation();
    }

    function description() {
	return '自动处理店铺违规';
    }
    
    
    

}
