<?php

 
class b2c_misc_checkOrder implements base_interface_task{

    function rule() {
	return '*/10 * * * *';
    }

    function exec() {
    /* 禁用
        $orderObj = app::get('b2c')->model('orders');
        $dataArr = $orderObj ->getList('*',array('callback_status'=>'error','pay_status'=>0));
        foreach($dataArr as $item){
            if($item['status']=='active'){
                $_POST['payment'] = array (
                  'order_id' =>$item['order_id'],
                  'money' => $item['final_amount'],
                  'currency' => $item['currency'],
                  'cur_money' =>$item['final_amount'],
                  'cur_rate' => $item['cur_rate'],
                  'cur_def' => '￥',
                  'pay_app_id' => $item['payment'],
                  'cost_payment' => $item['cost_payment'],
                  'cur_amount' => $item['final_amount'],
                  'memo' => '',
                  'bankaccounttype' => '92',
                  'banktype' => '9001000',
                );
                $result = kernel::single("b2c_ctl_site_paycenter")->dopayment('order','auto');
            }else{
                $arr_callback = array(
					'order_id' => $item['order_id'],
					'store_id' => $item['store_id'],
					'payment' => $item['payment'],
					'cur_money' => $item['final_amount'] ,
					'pay_status' => $item['pay_status'],
				);
                $sfscpay = kernel::single('ectools_payment_plugin_sfscpay');
                if(!$sfscpay->sfscpaycallback($msg,$arr_callback)){
                   $orderObj->update(array('callback_status'=>'normal'),array('order_id'=>$item['order_id']));
                }
            }
        }
        */
    }

    function description() {
	return '巡检出现错误记录的订单';
    }
}
