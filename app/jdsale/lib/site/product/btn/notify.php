<?php
/**
 * 缺货登记按钮
 * @package default
 * @author kxgsy163@163.com
 */
class jdsale_site_product_btn_notify
{
    
    private $file = 'site/product/btn/notify.html';
    private $order = 60;
    
    
    
    
    
    public function __get($var)
    {
        return $this->$var;
    }
    #End Func
    
    public function get_order() {
    	return $this->order;
    }
    
}