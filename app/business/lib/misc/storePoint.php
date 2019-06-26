<?php


class business_misc_storePoint implements base_interface_task{

    function rule() {
	return '*/1 * * * *';
    }

    function exec() {
	set_time_limit(0); 
	kernel::single('business_mdl_comment_stores_point')->exec_point();
    }

    function description() {
	return '自动统计店铺动态评分';
    }
    
    
    

}
