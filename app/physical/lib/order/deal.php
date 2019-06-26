<?php
class physical_order_deal 
{
    /**
     * 构造方法
     * @param object app
     */
    public function __construct($app)
    {
		$this->app = $app;
    }
    
    /**
     * 体检付款后处理
     */
    public function order_pay_finish($payment,$status='succ',$from='Back',&$msg,&$refund_status=false)
    {	
		$db = kernel::database();
		$order_id=$payment['orders'][0]['rel_id'];
		//判断是否是体检预约订单，且还是先填写预约信息后付款的订单
		$obj_exchange = $this->app->model('exchange');
		$exchange_info = $obj_exchange->dump(array("order_id"=>$order_id));
		if(!$exchange_info){
			return true;
		}
		//根据订单取得商品和卡券信息
		$sql="select card.card_id ,card.goods_id ,item.product_id ,item.order_id from ".DB_PREFIX."cardcoupons_cards as card JOIN ".DB_PREFIX."b2c_order_items as item ON card.goods_id=item.goods_id where item.order_id='".$order_id."'";
		$card_info=$db->select($sql);
		if(!$card_info){
			return true;
		}
		$obj_orders = $this->app->model('orders');
        $time = time();
		$b2corder_info=kernel::single('b2c_mdl_order_items')->dump(array('order_id'=>$order_id));
        //取得卡密并关联订单
		$msg='';
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
		$pass_info=$pass_obj->usePassByOrder($card_info,1,$msg);
		$cards_pass_info =$pass_obj->dump(array('card_no'=>$pass_info[1]['card_no'],'card_id'=>$pass_info[1]['card_id']));
		if(empty($cards_pass_info) || !$cards_pass_info['card_pass_id']){
			return false;
		}

		//事物处理开始
		
		$transaction_status = $db->beginTransaction();

		//体检兑换流程临时信息
		if($exchange_info &&$status=='succ'){
			//预约单生成
			$order_data = $exchange_info;
			unset($order_data['id']);
			$order_data['card_pass_id'] = $cards_pass_info['card_pass_id'];
			$order_data['status'] = 2;
			$order_data['create_time'] = $time;
			$order_data['update_time'] = $time;
			$id = $obj_orders->insert($order_data);
			if($id){
				//更新卡密信息
				$sql1 = "UPDATE sdb_cardcoupons_cards_pass SET exchange_no = '{$id}',exchange_prefix = 'physical',use_time ='{$time}',status = '3' where card_pass_id = ".$cards_pass_info['card_pass_id'];
				if( $obj_orders->db->exec($sql1) ){
					//删除体检兑换流程临时信息
					if( $obj_exchange->delete( array('id'=>$exchange_info['id']) ) ){
						$db->commit($transaction_status);
					}else{
						$db->rollback();
					}
				}else{
					$db->rollback();
				}
			}else{
				$db->rollback();
			}
		}
		$this->send_card($b2corder_info,$payment,array($order_id));
		return true;
	}
	    /**
     * 自动发货虚拟商品
     * @return null
     */
    public function send_card($goods_ids,$sdf_payment,$order_ids,$is_merge=false){
        $objOrders =kernel::single('b2c_mdl_orders');
        $objOrder_items =kernel::single('b2c_mdl_order_items');
        $objGoods =kernel::single('b2c_mdl_goods');
        $objProducts = kernel::single('b2c_mdl_products');
		$objMath = kernel::single('ectools_math');
        $tag = true;

		$goods_info = $objGoods->getRow('store',array('goods_id'=>$goods_ids['goods_id']));
		$goods_store = $objMath->number_minus(array($goods_info['store'], 1));
		$objGoods->update(array('store'=>$goods_store),array('goods_id'=>$goods_ids['goods_id']));

		$product_info = $objProducts->getRow('store',array('product_id'=>$goods_ids['product_id']));
		$product_store = $objMath->number_minus(array($product_info['store'], 1));
		$objProducts->update(array('store'=>$product_store),array('product_id'=>$goods_ids['product_id']));

		$update_data['sendnum'] = $objMath->number_plus(array($goods_id['sendnum'], 1));
		$objOrder_items->update($update_data,array('item_id'=>$goods_id['item_id']));
        //走自动发货流程
        // 更新发货日志结果
        foreach($order_ids as $key=>$val){
            $objorder_log =kernel::single('b2c_mdl_order_log');
            if($tag){
                $sdf_order_log = array(
                    'rel_id' => $val,
                    'op_id' => '0',
                    'op_name' => 'auto',
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'delivery',
                    'result' => 'SUCCESS',
                    'log_text' => '系统已发货，无需物流',
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
                $aUpdate['ship_status'] = '1';
                $objOrders->save($aUpdate);

                $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
                $req_arr['order_id']=$val;
                $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
                $data['confirm_time'] = time()+(app::get('b2c')->getConf('member.to_finish_XU'))*86400;
                $arr = app::get('business')->model('orders')->update($data,array('order_id' => $val));
            }
        }
    }
	function order_pay_finish_extends(){
		return true;
	}
}
