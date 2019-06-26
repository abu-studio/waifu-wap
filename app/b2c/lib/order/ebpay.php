<?php
class b2c_order_ebpay extends b2c_api_rpc_request
{    
    /**
     * 公开构造方法
     * @params app object
     * @return null
     */
    public function __construct($app)
    {        
        parent::__construct($app);
    }
    
    /**
     * 最终的克隆方法，禁止克隆本类实例，克隆是抛出异常。
     * @params null
     * @return null
     */
    final public function __clone()
    {
        trigger_error(app::get('b2c')->_("此类对象不能被克隆！"), E_USER_ERROR);
	}
	/**
	*水电煤订单付款成功后执行操作
	*
	**/
	public function order_pay_finish(&$sdf,$status='succ',$from='Back',&$msg,&$refund_status=false){
		$arrOrderbillls = $sdf['orders'];
		foreach ($arrOrderbillls as $rel_id=>$objOrderbills){
			switch ($objOrderbills['bill_type'])
            {
				case 'payments':
                    switch ($objOrderbills['pay_object'])
                    {
						case 'order':
							return $this->__order_payment($objOrderbills['rel_id'],$sdf,$status,$msg);
						break;
						default:
							return true;
						break;
					}
				default:
					return true;
				break;
			}
		}
	}
	//付款后水电煤销售订单后续处理
	private function __order_payment($rel_id, &$sdf, &$status='succ',&$msg='',&$refund_status=false,&$refund_type='0')
    {
        $objMath = kernel::single('ectools_math');
        $obj_orders = kernel::single('b2c_mdl_orders');
        $obj_order_items = kernel::single('b2c_mdl_order_items');		
		
        $sdf_order = $obj_orders->dump($rel_id, '*', $subsdf);
        $order_items = array();
		$pass_array  =array();
		$total_pass=array();		
        if ($sdf_order)
        {
			//判断是否支付完成，暂不支持部分付款
			if($sdf_order['pay_status']!='1'){
				return true;
			}			
			if($sdf_order['order_type'] == "ebapp"){
				$order_items=$obj_order_items->getList('*',array('order_id'=>$rel_id));			
				if($order_items){
					$ship_info=$sdf_order['consignee'];
					$ship_info['ship_status']=$ship_status;
					if(!$this->send_epcard($order_items,$sdf,array($rel_id),$total_pass,$ship_info)){
                        $msg = "交易失败";
                        $status = 'failed';
                        return false;
                    }

				}
			}
			return true;		
        }else{
            //合并支付
            $objMath = kernel::single('ectools_math');
            $objOrders = app::get('b2c')->model('orders');
            $obj_payments = app::get('ectools')->model('payments');
            $obj_order_bills = app::get('ectools')->model('order_bills');
            $payment_ids = $obj_payments->getList('payment_id',array('merge_payment_id'=>$rel_id));
            if($payment_ids){
                foreach($payment_ids as $key=>$val){
                    $order_id = $obj_order_bills->getRow('*',array('bill_id'=>$val['payment_id']));

                    $sdf = $obj_payments->getRow('*',array('payment_id'=>$val['payment_id']));
                    //防止而已修改支付信息
                    $orders = $objOrders->dump($order_id['rel_id']);
                    
                    $sdf['cur_amount'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                    $orders['total_amount'] = $objMath->number_div(array($orders['cur_amount'], $orders['cur_rate']));
                    $sdf['money'] = floatval($orders['total_amount'] - $orders['payed']);
                    $sdf['currency']=$orders['currency'];
                    $sdf['cur_money'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                    $sdf['cur_rate'] = $orders['cur_rate'];
                    $sdf['orders']['0'] = $order_id;
                    $this->__order_payment($order_id['rel_id'],$sdf);
                }
                
            }else{
                $msg = app::get('b2c')->_('需要支付的订单号不存在！');
                $status = 'failed';
                return false;
            }
        }
    }
	/**
     * 自动发货水电煤商品
     * @return null
     */
    public function send_epcard($goods_ids,$sdf_payment,$order_ids,$total_pass,$ship_info,$is_merge=false){
				
        $objOrders =kernel::single('b2c_mdl_orders');
        $objOrder_items =kernel::single('b2c_mdl_order_items');
        $objGoods =kernel::single('b2c_mdl_goods');
        $objProducts = kernel::single('b2c_mdl_products');
		$objMath = kernel::single('ectools_math');
        $tag = true;		
		foreach($goods_ids as $key=>$goods_id){
			$goods_info = $objGoods->getRow('store',array('goods_id'=>$goods_id['goods_id']));
			$goods_store = $objMath->number_minus(array($goods_info['store'], $goods_id['send_nums']));
			$objGoods->update(array('store'=>$goods_store),array('goods_id'=>$goods_id['goods_id']));

			$product_info = $objProducts->getRow('store',array('product_id'=>$goods_id['product_id']));
			$product_store = $objMath->number_minus(array($product_info['store'], $goods_id['send_nums']));
			$objProducts->update(array('store'=>$product_store),array('product_id'=>$goods_id['product_id']));

			$update_data['sendnum'] = $objMath->number_plus(array($goods_id['sendnum'], $goods_id['send_nums']));
			$objOrder_items->update($update_data,array('item_id'=>$goods_id['item_id']));
		}
		
        //走自动发货流程
        // 更新发货日志结果	
        foreach($order_ids as $key=>$val){
            $objorder_log =kernel::single('b2c_mdl_order_log');				
            if($tag){
				$log='';				

                $sdf_order_log = array(
                    'rel_id' => $val,
                    'op_id' => '0',
                    'op_name' => 'auto',
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'delivery',
                    'result' => 'SUCCESS',
                    'log_text' =>'系统已发货，无需物流',
                    'addon' => $log_addon,
                );
            }else{
                $sdf_order_log = array(
                    'rel_id' => $val,
                    'op_id' => '0',
                    'op_name' => 'auto',
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'delivery',
                    'result' => 'FAILURE',
                    'log_text' => '发货出错',
                    'addon' => $log_addon,
                );
            }
            $log_id = $objorder_log->save($sdf_order_log);
            if($log_id){
                //ajx crm
                //修改订单状态				
                $aUpdate['order_id'] = $val;
                $aUpdate['ship_status'] = 1;		
                $objOrders->save($aUpdate);	
                $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
                $req_arr['order_id']=$val;
                $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
                $data['confirm_time'] = time()+(app::get('b2c')->getConf('member.to_finish_XU'))*86400;
                $arr = app::get('business')->model('orders')->update($data,array('order_id' => $val));                	
            }
        }

        //调用java order 订单状态
        $ebapporder_object = kernel::single("b2c_mdl_ebapporder");
        $ebapporder_data = $ebapporder_object->dump(array("order_id"=>$aUpdate['order_id']),"ebapporder_id");

        $_sjson = array(
            'METHOD'=>'utilityPay',
            'ORDER_ID'=>$ebapporder_data['ebapporder_id']
        );
        $post_data = array('serviceNo'=>'UtilityService',"inputParam"=>json_encode($_sjson));
        $pageContents = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);
        $pageContents_tmp = SFSC_HttpClient::objectToArray($pageContents);
        if(empty($pageContents_tmp) || $pageContents_tmp['RESULT_CODE'] == "I03102"){
            return false;
        }
        return true;

    }	
}