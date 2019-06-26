<?php
/**
 * 立即购买按钮
 * @package default
 * @author kxgsy163@163.com
 */
class jdsale_site_product_btn_fastbuy
{
    
    private $file = 'site/product/btn/fastbuy.html';
    private $order = 90;
    
    
 
    
    public function __get($var)
    {
        return $this->$var;
    }
    #End Func
    
    public function get_order() {
    	return $this->order;
    }
    
}