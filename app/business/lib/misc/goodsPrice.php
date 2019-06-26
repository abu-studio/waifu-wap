<?php


class business_misc_goodsPrice implements base_interface_task{

    function rule() {
	return '*/1 * * * *';
    }

    function exec() {
	set_time_limit(0); 
	kernel::single('b2c_mdl_member_goods')->changePrice();
    }

    function description() {
	return '自动获取商品降价数据';
    }
    
    
    

}
