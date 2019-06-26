<?php


class business_misc_settlement implements base_interface_task{

    function rule() {
	return '*/1 * * * *';
    }

    function exec() {
	set_time_limit(0);
	header("cache-control:no-cache,must-revalidate");
	kernel::single('business_settlement_autosettlement')->autosettlement();
    }

    function description() {
	return '自动周期结算';
    }
    
    
    

}
