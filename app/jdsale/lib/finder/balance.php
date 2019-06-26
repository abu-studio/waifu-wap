<?php
class jdsale_finder_balance{
    var $column_local_order_id;
	var $column_local_price;

    function __construct($app){
        $this->app = $app;
		$this->column_local_order_id = app::get('b2c')->_('本地订单号');
		$this->column_local_price = app::get('b2c')->_('本地订单价格');
    }
		
	/*
	public function column_local_order_id($row)
    {
        $jdorders = app::get('jdsale')->model('jdorders');
		$info = $jdorders->getRow('order_id',array('jdorders_id'=>$row['order_id']));
		return $info['order_id'];
    }

	public function column_local_price($row)
    {
       $jdorders = app::get('jdsale')->model('jdorders');
	   $orders = app::get('b2c')->model('orders');
	   $info = $jdorders->getRow('order_id',array('jdorders_id'=>$row['order_id']));
	   $orderDate = $orders->getRow('final_amount',array('order_id'=>$info['order_id']));
	   return $orderDate['final_amount'];
    }
	*/
	
}
