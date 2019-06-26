<?php

 
class b2c_misc_test1 implements base_interface_task{

    function rule() {
	return '0 0 */1 * *';
    }

    function exec() {
    	$now = date('Ymd H:i:s',time());
		error_log("=-=-=-=-=-=-=-=-".PHP_EOL.var_export($now,1).PHP_EOL,3,DATA_DIR."/".date('Ymd',time())."xxxxxxxxx.log");
    }

    function description() {
	return '测试计划任务';
    }
    
    
    

}
