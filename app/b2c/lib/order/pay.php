<?php

 

class b2c_order_pay extends b2c_api_rpc_request
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
     * 订单支付后的处理
     * @params array 支付完的信息
     * @params 支付时候成功的信息
     */
    public function pay_finish(&$sdf, $status='succ',&$msg='',&$refund_status=false)
    {
        // redirect to payment list page.
        $arrOrderbillls = $sdf['orders'];
        $is_success = true;
        $str_op_id = "";
        $str_op_name =  "";
        $objMath = kernel::single('ectools_math');
        $objOrderItems = $this->app->model('order_items');
        $objGoods = $this->app->model('goods');
        $objOrders = $this->app->model('orders');

        foreach ($arrOrderbillls as $rel_id=>$objOrderbills)
        {
            switch ($objOrderbills['bill_type'])
            {
                case 'payments':
                    switch ($objOrderbills['pay_object'])
                    {
                        case 'order':
                            if ($status == 'succ' || $status == 'progress'){
                                //支付信息的完成
                                $is_success = $this->__order_payment($objOrderbills['rel_id'], $sdf, $status,$msg,$refund_status,$refund_type);
                                if(!$refund_status){
                                    //判断是否有虚拟商品
                                    if(isset($objOrderbills['rel_id'])){
                                        //获取会员信息，和店铺信息
                                        $member_info = $objOrders->dump($objOrderbills['rel_id'],'member_id,store_id');
                                        //获取商品id，和商品的个数
                                        $good_ids = $objOrderItems->getList('goods_id,nums',array('order_id'=>$objOrderbills['rel_id']));
                                        if(!empty($good_ids)){
                                            foreach($good_ids as $key=>$val){
                                                $good_kind = $objGoods->dump($val['goods_id'],'goods_kind');
                                                if($good_kind['goods_kind'] == 'entity'){
                                                    $goods_ids[] = array('goods_id'=>$val['goods_id'],'nums'=>$val['nums'],'member_id'=>$member_info['member_id'],'store_id'=>$member_info['store_id'],'order_id'=>$objOrderbills['rel_id']);
                                                    $target = true;
                                                }
												$this->updateRank($objOrderbills['rel_id']);
                                            }
                                        }
                                        if($target){
                                            $rel_order_ids[] = $objOrderbills['rel_id'];
                                            $target = false;
                                        }
                                    }
                                }
                            }
                            break;
                        case 'recharge':
                            // 预存款充值
                            $obj_joinfee = kernel::servicelist('b2c.other_joinfee.pay_finish');
                            $sdf['status'] = $status;
                            if ($obj_joinfee)
                            {
                                foreach ($obj_joinfee as $obj)
                                {
                                    if ($obj->get_type() == $objOrderbills['pay_object'])
                                    {
                                        $obj->generate_bills($sdf, $objOrderbills, $sdf['pay_type'], $this->str_op_id, $this->str_op_name, $errorMsg);
                                    }
                                }
                            }
                            break;

                         case 'earnest':
                             
                            // 支付保证金
                            $obj_joinfee = kernel::servicelist('b2c.other_earnest.pay_finish');
                          
                            $sdf['status'] = $status;
                            if ($obj_joinfee)
                            {
                                foreach ($obj_joinfee as $obj)
                                {
                                    if ($obj->get_type() == $objOrderbills['pay_object'])
                                    {

                                      
                                        $obj->generate_bills($sdf, $objOrderbills, $sdf['pay_type'], $this->str_op_id, $this->str_op_name, $errorMsg);
                                    }
                                }
                            }
                            break;

                        case 'joinfee':                            
                            break;
                       
                        default:
                            // 其他充值方式
                            $obj_joinfee = kernel::servicelist('b2c.other_joinfee.pay_finish');
                            $sdf['status'] = $status;
                            if ($obj_joinfee)
                            {
                                foreach ($obj_joinfee as $obj)
                                {
                                    if ($obj->get_type() == $objOrderbills['pay_object'])
                                    {
                                        $obj->generate_bills($sdf, $objOrderbills, $sdf['pay_type'], $this->str_op_id, $this->str_op_name, $errorMsg);
                                    }
                                }
                            }
                            break;
                    }
                    break;
                case 'refunds':
                    // 只支持预存款
                    $objAdvance = $this->app->model("member_advance");
                    $sdf_order = $this->dump($objOrderbills['rel_id'], '*');

                    // Order information update.
                    if ($sdf['cur_money'] < $sdf_order['cur_amount'] && $status != 'failed')
                        $pay_status = '4';
                    else if ($status == 'succ')
                        $pay_status = '5';
                    else
                        $pay_status = '2';

                    $arrOrder = array(
                        'order_id' => $objOrderbills['rel_id'],
                        'pay_app_id' => $sdf['pay_app_id'],
                        'payed' => $objMath->number_minus(array($sdf_order['payed'], $sdf['cur_money'])) < 0 ? 0 : $objMath->number_minus(array($sdf_order['payed'], $sdf['cur_money'])),
                        'pay_status' => $pay_status,
                    );
                    $this->save($arrOrder);

                    $status = $objAdvance->add($sdf_order['member_id'], $sdf['payed'], app::get('b2c')->_('后台订单退款'), $errorMsg, $sdf['payment_id'], '', 'deposit', $sdf_order['memo']);
                    break;
            }

            //添加合并付款判断
            $objOrder = $this->app->model("orders");
            //获取需要合并的 订单信息
            $order_ids = $objOrder->getList('order_id',array('order_id'=>$objOrderbills['rel_id']));
            if($order_ids){
                // 改变日志操作结果(正常流程)
                if (is_object($this->app) && $this->app)
                {
                    $objOrderLog = $this->app->model("order_log");
                    if ($status == 'succ' || $status === true || $status == 'progress')
                        $status_log = 'SUCCESS';
                    else
                        $status_log = 'FAILURE';

                    $log_text[] = array(
                        'txt_key'=>'买家已经付款，订单<span class="siteparttitle-orage">%s</span>付款<span class="siteparttitle-orage">%s</span>元',
                        'data'=>array(
                            0=>$objOrderbills['rel_id'],
                            1=>$sdf['cur_money'],
                        ),
                    );
                    $log_text = serialize($log_text);

                    //获取当前后台操作员
                    $back_str_op_name = kernel::single('desktop_user')->get_login_name();
                    $sdf_order_log = array(
                        'rel_id' => $objOrderbills['rel_id'],
                        'op_id' => ($this->from == 'Back') ? $sdf['op_id'] : $this->str_op_id,
                        'op_name' => ($this->from == 'Back') ? $back_str_op_name : $this->str_op_name,
                        'alttime' => time(),
                        'bill_type' => $objOrderbills['pay_object'],
                        'behavior' => $objOrderbills['bill_type'],
                        'result' => $status_log,
                        'log_text' => $log_text,
                    );

                    $log_id = $objOrderLog->save($sdf_order_log);
                }

                if ($status_log == 'FAILURE')
                    $is_success = false;
             }else{
                 //合并付款流程
                 $objMath = kernel::single('ectools_math');
                 $obj_payments = app::get('ectools')->model('payments');
                 $obj_order_bills = app::get('ectools')->model('order_bills');
                 $payment_ids = $obj_payments->getList('payment_id',array('merge_payment_id'=>$objOrderbills['rel_id']));
                 if($payment_ids){
                        foreach($payment_ids as $key=>$val){
                            $order_id = $obj_order_bills->getRow('*',array('bill_id'=>$val['payment_id']));

                            $sdf = $obj_payments->getRow('*',array('payment_id'=>$val['payment_id']));
                            //防止而已修改支付信息
                            $orders = $objOrder->dump($order_id['rel_id']);
                            
                            $sdf['cur_amount'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                            $orders['total_amount'] = $objMath->number_div(array($orders['cur_amount'], $orders['cur_rate']));
                            $sdf['money'] = floatval($orders['total_amount'] - $orders['payed']);
                            $sdf['currency']=$orders['currency'];
                            $sdf['cur_money'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                            $sdf['cur_rate'] = $orders['cur_rate'];
                            $sdf['orders']['0'] = $order_id;
                            

                            $objOrderLog = $this->app->model("order_log");
                            if ($status == 'succ' || $status === true || $status == 'progress')
                                $status_log = 'SUCCESS';
                            else
                                $status_log = 'FAILURE';

                            $key = '合并支付成功！';

                            //获取当前后台操作员
                            $back_str_op_name = kernel::single('desktop_user')->get_login_name();
                            $sdf_order_log = array(
                                'rel_id' => $order_id['rel_id'],
                                'op_id' => ($this->from == 'Back') ? $sdf['op_id'] : $this->str_op_id,
                                'op_name' => ($this->from == 'Back') ? $back_str_op_name : $this->str_op_name,
                                'alttime' => time(),
                                'bill_type' => $order_id['pay_object'],
                                'behavior' => $order_id['bill_type'],
                                'result' => $status_log,
                                'log_text' => $key,
                            );

                            $log_id = $objOrderLog->save($sdf_order_log);
                        }
                        if ($status_log == 'FAILURE')
                            $is_success = false;
                    }
                    
                }
                if($refund_status || $refund_type == '2'){
                    $objOrderLog = $this->app->model("order_log");
                    $order_bill = app::get('ectools')->model("order_bills");
                    $status_log = 'SUCCESS';
                    
                    $money = $order_bill->dump(array('rel_id'=>$objOrderbills['rel_id'],'bill_type'=>'refunds'),'money');

                    $log_text1[] = array(
                        'txt_key'=>'订单已付款或超付，本次支付已经退款<span class="siteparttitle-orage">'.$money['money'].'</span>元',
                    );
                    $log_text1 = serialize($log_text1);

                    //获取当前后台操作员
                    $sdf_order_log = array(
                        'rel_id' => $objOrderbills['rel_id'],
                        'op_id' => '0',
                        'op_name' => 'auto',
                        'alttime' => time(),
                        'bill_type' => $objOrderbills['pay_object'],
                        'behavior' => 'refunds',
                        'result' => $status_log,
                        'log_text' => $log_text1,
                    );

                    $log_id = $objOrderLog->save($sdf_order_log);
                }

        }

        if(!empty($goods_ids)){
            $this->sned_card($goods_ids,$sdf_payment,$rel_order_ids);
        }
		$obj_pay_extend_lists = kernel::servicelist("order.pay_finish_extend");
		foreach($obj_pay_extend_lists as $key=>$obj_pay_extend_list){
            $is_cardsuccess = $obj_pay_extend_list->order_pay_finish($sdf,'succ','Back',$msg);
            if(!$is_cardsuccess){
                return $is_cardsuccess;
            }
		}

        $obj_ebpay_extend_lists = kernel::servicelist("order.ebpay_finish_extend");
        foreach($obj_ebpay_extend_lists as $k=>$obj_ebpay_extend_list){
            $is_ebsuccess = $obj_ebpay_extend_list->order_pay_finish($sdf,'succ','Back',$msg);
            if(!$is_ebsuccess){
                return $is_ebsuccess;
            }
        }
        return $is_success;
    }

    /**
     * 自动发货虚拟商品
     * @return null
     */
    public function sned_card($goods_ids,$sdf_payment,$order_ids,$is_merge=false){
        $objEntityGoods = $this->app->model('goods_entity_items');
        $objGoodsEntity = $this->app->model('entity_goods');
        $objOrders = $this->app->model('orders');
        $objGoods = $this->app->model('goods');
        $objProducts = $this->app->model('products');
        $tag = true;
        foreach($goods_ids as $key=>$val){
            for($i=0;$i<$val['nums'];$i++){
                $entity_info = $objEntityGoods->dump(array('goods_id'=>$val['goods_id'],'is_used'=>'1'),'*');
                $data = array('item_id'=>$entity_info['items_id'],'member_id'=>$val['member_id'],'store_id'=>$val['store_id'],'order_id'=>$val['order_id'],'send_time'=>time());
                if(isset($data['item_id'])){
                    $rs = $objGoodsEntity->save($data);
                }
                if($rs){
                    $up_rs = $objEntityGoods->update(array('is_used'=>'2','order_id'=>$val['order_id'],'send_time'=>time()),array('items_id'=>$entity_info['items_id']));
                }
                if(!$up_rs){
                    $tag = false;
                }else{
                    $goods_info = $objGoods->dump(array('goods_id'=>$val['goods_id']),'store');
                    $goods_store = $goods_info['store'] - 1;
                    $objGoods->update(array('store'=>$goods_store),array('goods_id'=>$val['goods_id']));

                    $product_info = $objProducts->dump(array('product_id'=>$entity_info['product_id']),'store,freez');
                    $product_store = $product_info['store'] - 1;
                    $product_freez = $product_info['freez'] - 1;
                    $objProducts->update(array('store'=>$goods_store,'freez'=>$product_freez),array('product_id'=>$entity_info['product_id']));
                }
            }
        }
        //走自动发货流程

        // 更新发货日志结果
        foreach($order_ids as $key=>$val){
            $objorder_log = $this->app->model('order_log');
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

    private function __order_payment($rel_id, &$sdf, &$status='succ',&$msg='',&$refund_status=false,&$refund_type='0')
    {
        $objMath = kernel::single('ectools_math');
        $obj_orders = $this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $sdf_order = $obj_orders->dump($rel_id, '*', $subsdf);
        $order_items = array();
		
        if ($sdf_order)
        {
            if ($sdf_order['member_id'])
            {
                $obj_members = $this->app->model('members');
                $arr_members = $obj_members->dump($sdf_order['member_id'], '*', array(':account@pam' => array('*')));
                $this->str_op_id = $sdf_order['member_id'];
                $this->str_op_name = $arr_members['pam_account']['login_name'];
            }
            else
            {
                $this->str_op_id = '0';
                $this->str_op_name = '';
            }

            if ($sdf_order['pay_status'] == '1' || $sdf_order['status'] != 'active')
            {
                $this->order_pay_refund($rel_id,$sdf,&$refund_status,$sdf['money'],$refund_type);
                $msg = app::get('b2c')->_('该订单已经支付或非法，无需重复支付！');
                $status = 'succ';
                return false;
            }

            // Order information update.  bccomp
            // if ($objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) < $sdf_order['cur_amount'] && $status != 'failed'){
            if (bccomp($objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) , $sdf_order['cur_amount'],2)==-1 && $status != 'failed'){
                $pay_status = '3';
            }
            else if ($status == 'succ' || $status == 'progress')
            {
                if ($status == 'succ')
                    $pay_status = '1';
                else
                    $pay_status = '2';
            }
            else
            {
                // if ($objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) > $sdf_order['cur_amount'])
                if (bccomp($objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) , $sdf_order['cur_amount'],2))
                {
                    $relmoney = $objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) - $sdf_order['cur_amount'];
                    $this->order_pay_refund($rel_id,$sdf,&$refund_status,$relmoney,$refund_type);
                    $msg = app::get('b2c')->_('支付金额超过需要支付的总金额！');
                    $status = 'succ';
                }
                $pay_status = '0';
            }

            // if ($objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) > $sdf_order['cur_amount'])
            if (bccomp($objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) , $sdf_order['cur_amount'],2))
            {
                $relmoney = $objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) - $sdf_order['cur_amount'];
                $this->order_pay_refund($rel_id,$sdf,&$refund_status,$relmoney,$refund_type);
                $msg = app::get('b2c')->_('支付金额超过需要支付的总金额！');
                $status = 'succ';
            }
            // $arrOrder = array(
            //     'order_id' => $rel_id,
            //     'payment' => $sdf['pay_app_id'],
            //     'payed' => ($objMath->number_plus(array($sdf_order['payed'] > $sdf['cur_money'])) > $sdf_order['cur_amount']) ? $sdf_order['cur_amount'] : $objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])),
            //     'pay_status' => $pay_status,
            // );
            $arrOrder = array(
                'order_id' => $rel_id,
                'payment' => $sdf['pay_app_id'],
                'payed' => (bccomp($objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])) , $sdf_order['cur_amount'],2)) ? $sdf_order['cur_amount'] : $objMath->number_plus(array($sdf_order['payed'], $sdf['cur_money'])),
                'pay_status' => $pay_status,
            );
			//组合支付：福点部分完成
			$check = kernel::single('ectools_pay')->get_payment_by_order($rel_id,'sfscpay','sfsc_freeze');
			if(isset($check['payment_id'])&&strpos($sdf_order['payinfo']['pay_app_id'],'sfscpay')!==false&&strpos($sdf_order['payinfo']['pay_app_id'],',')!==false){
                // if(($objMath->number_plus(array($arrOrder['payed'], $check['cur_money'])) >= $sdf_order['cur_amount'])){
                $reNum = bccomp($objMath->number_plus(array($arrOrder['payed'], $check['cur_money'])) , $sdf_order['cur_amount'],2);
				if($reNum == 1 || $reNum == 0){
					$arrOrder['payed'] = $sdf_order['cur_amount'];
					$pay_status = '1';
				}else{
					$arrOrder['payed'] = $objMath->number_plus(array($arrOrder['payed'], $check['cur_money']));
					$pay_status = '3';
				}
				$arrOrder['pay_status'] = $pay_status;
				$arrOrder['payment'] = $sdf_order['payinfo']['pay_app_id'];
				//更新福点支付单状态
				app::get('ectools')->model('payments')->update(array('status'=>'succ'),array('payment_id'=>$check['payment_id']));
				$sdf_order_log = array(
					'rel_id' => $rel_id,
					'op_id' => '0',
					'op_name' => 'auto',
					'alttime' => time(),
					'bill_type' => 'order',
					'behavior' => 'payments',
					'result' => 'SUCCESS',
					'log_text' => '买家福点已支付，订单<span class="siteparttitle-orage">'.$rel_id.'</span>付款<span class="siteparttitle-orage">'.$check['cur_money'].'</span>元',
					'addon' => $log_addon,
				);
				kernel::single('b2c_mdl_order_log')->save($sdf_order_log);
			}else{ 
				unset($check);
			}
            $is_save = $obj_orders->update($arrOrder,array('order_id'=>$rel_id));
            if (!$is_save){
                $msg = app::get('b2c')->_('订单支付状态保存失败！');
                return false;
            }
            if (!$obj_orders->db->affect_row()){
                $msg = app::get('b2c')->_('订单重复支付！');
                return false;
            }

			$order_saved = $obj_orders->dump($rel_id, '*', $subsdf);
			if(strpos($order_saved['payinfo']['pay_app_id'],'sfscpay')!==false&&$sdf['is_card']!=true){
				//福点or组合支付完成回调接口-hy
				$sfscpay = kernel::single('ectools_payment_plugin_sfscpay');
				$arr_callback = array(
					'order_id' => $rel_id,
					'store_id' => $sdf_order['store_id'],
					'payment' => $arrOrder['payment'],
					'cur_money' => isset($check['cur_money'])?$check['cur_money']:$sdf['cur_money'],
					'pay_status' => $pay_status,
				);
                if(!$sfscpay->sfscpaycallback($msg,$arr_callback)){
                    //福点支付：失败回滚订单数据
                    if($order_saved['payinfo']['pay_app_id']=='sfscpay'){
                        $arr_rollback = array(
                            'order_id' => $rel_id,
                            'payed' => $sdf_order['payed'],
                            'pay_status' => $sdf_order['pay_status'],
                        );
                        $obj_orders->update($arr_rollback,array('order_id'=>$rel_id));
                        
                    }
                    $msg = app::get('b2c')->_('福点支付回调失败！');
                    return false;
                }
			}

            // 支付完了，预存款  sfsc 自己开发的接口调用
            if ($sdf['pay_app_id'] == 'deposit')
            {
                $objAdvance = $this->app->model("member_advance");
                $is_frontend = ($this->from == 'Back') ? false: true;
                $status = $objAdvance->deduct($sdf_order['member_id'], $sdf['money'], app::get('b2c')->_('预存款支付订单'), $msg, $sdf['payment_id'], $rel_id, 'deposit', $sdf_order['memo'],$is_frontend);
                $errorMsg[] = $msg;
                if (!$status)
                {
                    return false;
                }
            }else
            {
                $errorMsg[] = ($status == 'succ' || $status === true) ? (app::get('b2c')->_("订单号：") . $rel_id . ' ' . $arrPayments['app_name'] . app::get('b2c')->_("支付交易号: ") . $sdf['trade_no'] . app::get('b2c')->_("，交易成功！")) : app::get('b2c')->_("订单号：") . $rel_id . ' ' . $arrPayments['app_name'] . app::get('b2c')->_("支付交易失败！");
            }

            // 为会员添加积分
            if (isset($sdf_order['member_id']) && $sdf_order['member_id'] && $arrOrder['payed'] == $sdf_order['cur_amount'])
            {
                $arr_orders = $obj_orders->getList('*', array('order_id'=>$rel_id));
                $arr_orders[0]['pay_status'] = '1';
                $is_change_point = true;
                // 扣除积分，使用积分
                $obj_reducte_point = kernel::service('b2c_member_point_reducte');
                $operator = ($this->from == 'Back') ? $sdf['op_id'] : $sdf_order['member_id'];
                $policy_stage = $this->app->getConf("site.consume_point.stage");
                if ($arr_orders[0]['pay_status'] == '1' && $arr_orders[0]['ship_status'] == '1' && $policy_stage == '2')
                    $stage = '1';
                elseif ($arr_orders[0]['pay_status'] == '1' && $policy_stage == '1')
                    $stage = '1';
                else
                    $stage = '0';
                /** end **/
                if ($stage)
                    $is_change_point = $obj_reducte_point->change_point($sdf_order['member_id'], 0 - intval($sdf_order['score_u']), $msg, 'order_pay_use', 1, $stage, $rel_id, $operator);

                if (!$is_change_point)
                {
                    $status = 'failed';
                    return false;
                }
                $policy_stage = $this->app->getConf("site.get_policy.stage");
                if ($arr_orders[0]['pay_status'] == '1' && $arr_orders[0]['ship_status'] == '1' && $policy_stage == '2')
                    $stage = '1';
                elseif ($arr_orders[0]['pay_status'] == '1' && $policy_stage == '1')
                    $stage = '1';
                else
                    $stage = '0';

                // 获得积分
                $obj_add_point = kernel::service('b2c_member_point_add');
                if ($stage)
                    $obj_add_point->change_point($sdf_order['member_id'], intval($sdf_order['score_g']), $msg, 'order_pay_get', 2, $stage, $rel_id, $operator);

                // 增加经验值
                //$obj_member = $this->app->model('members');
                //$obj_member->change_exp($sdf_order['member_id'], floor($sdf_order['cur_amount']));
                //经验的获得移动至确认收货处 
            }

            if ($pay_status == '1')
                $sdf['pay_status'] = 'PAY_FINISH';
            else if ($pay_status == '2')
                $sdf['pay_status'] = 'PAY_TO_MEDIUM';
            else if ($pay_status == '3')
                $sdf['pay_status'] = 'PAY_PART';
            else
                $sdf['pay_status'] = 'FAILED';

            $sdf['order_id'] = $rel_id;

            // 冻结库存
            if ($arrOrder['payed'] == $sdf_order['cur_amount'])
            {
                $store_mark = $this->app->getConf('system.goods.freez.time');
                // 所有的goods type 处理的服务的初始化.
                $arr_service_goods_type_obj = array();
                $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
                foreach ($arr_service_goods_type as $obj_service_goods_type)
                {
                    $goods_types = $obj_service_goods_type->get_goods_type();
                    $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
                }
                $arr_common_type = array('goods', 'gift');

                if ($store_mark == '2')
                {
                    $objGoods = $this->app->model('goods');
                    if ($sdf_order['order_objects']){
                        foreach ($sdf_order['order_objects'] as $k=>$v)
                        {
                            if (in_array($v['obj_type'], $arr_common_type))
                                $order_items = array_merge($order_items,$v['order_items']);
                            else
                            {
                                // 扩展区块的商品预占库存处理
                                $str_service_goods_type_obj = $arr_service_goods_type_obj[$v['obj_type']];
                                $is_freeze = $str_service_goods_type_obj->freezeGoods($v);
                                if (!$is_freeze)
                                {
                                    $status = 'failed';
                                    $msg = app::get('b2c')->_('库存冻结失败！');
                                    return false;
                                }
                            }
                        }
					}
					
					//将数组加入$order_items 加入goods_kind字段数据
					$goodsObj = app::get('b2c')->model('goods');
					foreach($order_items as $k=>$va){
						$goodsDate = $goodsObj->getRow('goods_kind',array('goods_id'=>$va['goods_id']));
						$order_items[$k]['goods_kind'] = $goodsDate['goods_kind'];
					}
					
                    // 判断是否已经发过货.
                    if ($sdf_order['ship_status'] == '1' || $sdf_order['ship_status'] == '2')
                    {
                        foreach ($order_items as $key=>$dinfo)
                        {
                            if ($dinfo['products']['sendnum'] < $dinfo['products']['nums'])
                            {
                                $semds = $objMath->number_plus(array($dinfo['nums'], $dinfo['sendnum']));
                                if ($semds > 0)
                                {
                                    $arr_params = array(
                                        'goods_id' => $dinfo['goods_id'],
                                        'product_id' => $dinfo['products']['product_id'],
                                        'quantity' => $semds,
                                    );
									//如果商品是卡劵。那么类型改为cards
									if($dinfo['goods_kind'] == 'card'){
										$dinfo['item_type'] = 'cards';
									}
                                    if ($dinfo['item_type'] == 'product' || $dinfo['item_type'] == 'jdgoods' || $dinfo['item_type'] == 'jdbook')
                                        $dinfo['item_type'] = 'goods';
                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[$dinfo['item_type']];
                                    $is_freeze = $str_service_goods_type_obj->freezeGoods($arr_params);
                                    if (!$is_freeze)
                                    {
                                        $status = 'failed';
                                        $msg = app::get('b2c')->_('库存冻结失败！');
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        foreach ($order_items as $key=>$dinfo)
                        {
                            $arr_params = array(
                                'goods_id' => $dinfo['goods_id'],
                                'product_id' => $dinfo['products']['product_id'],
                                'quantity' => $dinfo['quantity'],
                            );
							//如果商品是卡劵。那么类型改为cards
							if($dinfo['goods_kind'] == 'card'){
								$dinfo['item_type'] = 'cards';
							}
                            if ($dinfo['item_type'] == 'product' || $dinfo['item_type'] == 'jdgoods' || $dinfo['item_type'] == 'jdbook')
                                $dinfo['item_type'] = 'goods';
                            $str_service_goods_type_obj = $arr_service_goods_type_obj[$dinfo['item_type']];
                            $is_freeze = $str_service_goods_type_obj->freezeGoods($arr_params);
                            if (!$is_freeze)
                            {
                                $status = 'failed';
                                $msg = app::get('b2c')->_('库存冻结失败！');
                                return false;
                            }
                        }
                    }
                }

                //支付，处理其他app自身业务逻辑
                $arr_service_pay = kernel::servicelist("order_pay_operation");
                foreach((array)$arr_service_pay as $obj_service_order_pay) {
                    if(method_exists($obj_service_order_pay, "check_order_info")) {
                        if(!$obj_service_order_pay->check_order_info($sdf_order, $message)) {
                            $status = 'failed';
                            $msg = $message;
                            return false;
                        }
                    }
                }
            }

            // 与中心交互
            $is_need_rpc = false;
            $obj_rpc_obj_rpc_request_service = kernel::servicelist('b2c.rpc_notify_request');
            foreach ($obj_rpc_obj_rpc_request_service as $obj)
            {
                if ($obj && method_exists($obj, 'rpc_judge_send'))
                {
                    if ($obj instanceof b2c_api_rpc_notify_interface)
                        $is_need_rpc = $obj->rpc_judge_send($sdf_order);
                }

                if ($is_need_rpc) break;
            }

            if ($is_need_rpc){
                $data['queue_title'] = app::get('b2c')->_('发送订单到外部');
                $data['start_time'] = time();
                $data['params'] = $sdf;
                $data['worker'] = 'b2c_queue.send_payments';
                $queue = app::get('base')->model('queue');
                $queue->insert($data);
            }

            $aUpdate['order_id'] = $rel_id;
            $aUpdate['order_kind'] = $order_saved['order_kind'];
            $aUpdate['pay_time'] = date('Y-m-d', time());
            $aUpdate['money'] = $sdf['cur_money'];
            $aUpdate['email'] = (!$sdf_order['member_id']) ? $sdf_order['consignee']['email'] : $arr_members['contact']['email'];
            $aUpdate['pay_status'] = $sdf['pay_status'];
            $aUpdate['is_frontend'] = ($this->from == 'Back') ? false: true;
            $aUpdate['pay_account'] = $arr_members['pam_account']['login_name'];
            if($aUpdate['order_kind']=="card"){
                $obj_orders->fireEvent("exchange", $aUpdate, $sdf_order['member_id']);
            }else{
				//如果是杉德卡订单，就不触发短信接口
				if($sdf_order['order_type'] != 'sand'){
					$obj_orders->fireEvent("payed", $aUpdate, $sdf_order['member_id']);
				}
            }

			//支付成功，当订单是杉德卡时，创建一个杉德订单
			if($sdf_order['order_type'] == 'sand'){
				$members_object = app::get('b2c')->model('members');
				//修改开户状态，
				$members_object->update(array('sandopen'=>'1'),array('member_id'=>$sdf_order['member_id']));

				//获取手续费
				$member_lv = $members_object->getRow('member_lv_id,sandopen_mobile',array('member_id'=>$sdf_order['member_id']));
				$member_lv_id = $member_lv['member_lv_id'];
				$member_lv_object = app::get('b2c')->model('member_lv');
				$member_lv_data = $member_lv_object->dump(array('member_lv_id'=>$member_lv_id));
				$commission = $member_lv_data['commission']?$member_lv_data['commission']:0;
				$amount = floor($sdf_order['cur_amount']/(1+$commission/100));
				
				//获取商社号   商社名称 登录名称
				$obj_members = app::get('pam')->model('account');
				$account_data = $obj_members->getRow('*',array('account_id'=>$sdf_order['member_id']));

				//获取订单创建时间
				$order_object = app::get('b2c')->model('orders');
				$orderdata = $order_object->getRow('createtime',array('order_id'=>$sdf_order['order_id']));
				$sand['order_time'] = $orderdata['createtime'];
				$sand['order_id'] = $sdf_order['order_id'];
				$sand['member_id'] = $sdf_order['member_id'];
				$sand['amount'] = $amount;
				$sand['sandopen_mobile'] = $member_lv['sandopen_mobile'];
				$sand['createtime'] = time();
				$sand['pay_amount'] = $sdf_order['cur_amount'];
				$sand['counter_fee'] = $sdf_order['cur_amount']-$amount;
				$sand['member_name'] = $account_data['login_name'];
				$sand['company_no'] = $account_data['company_no'];
				$sand['company_name'] = $account_data['company_no'];
				$sand['sand_name'] = $account_data['login_name'].'efescobm';
				$objsand = app::get('sand')->model('sandorder');
				$objsand->save($sand);
			}	
        }
        else
        {
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
		return true;
    }

    public function order_pay_finish(&$sdf, $status='succ', $from='Back',&$msg='',&$refund_status=false)
    {
        $this->from = $from;
        return $this->pay_finish($sdf, $status,$msg,$refund_status);
    }

    public function order_payment_change($sdf)
    {
        $arr_data = array();
        /*$arr_data['tid'] = $sdf['order_id'];
        $arr_data['payment_tid'] = $sdf['payinfo']['pay_app_id'];
        $obj_payment_cfgs = app::get('ectools')->model('payment_cfgs');
        $arr_payments = $obj_payment_cfgs->getPaymentInfo($sdf['payinfo']['pay_app_id']);
        $arr_data['payment_type'] = $arr_payments['app_display_name'];  
        $arr_data['tariff'] = $sdf['payinfo']['cost_payment'];      
        
        $arr_callback = array(
            'class' => 'b2c_api_callback_app', 
            'method' => 'callback',
            'params' => array(
                'method' => 'store.trade.payment_type.update',
                'tid' => $arr_data['tid'],
            ),
        );
        
        parent::request('store.trade.payment_type.update', $arr_data, $arr_callback, 'Payment Change', 1);*/

        $arr_data['order_id'] = $sdf['order_id'];

        //新的版本控制api
        $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
        $obj_apiv->rpc_caller_request($arr_data, 'orderpaymentchange');
    }

    public function order_pay_finish_extends($sdf)
    {
        if( !is_array($sdf['orders']) ) return;

        $objOrders = $this->app->model('orders');
        foreach( $sdf['orders'] as $row )
        {
            $order_id = $row['rel_id'];
            $tmp = $objOrders->getList('pay_status', array('order_id'=>$order_id));
            $sdf_order = $tmp[0];
        }
        if (!$sdf_order) return;

        $obj_payment_extends_op = kernel::servicelist('b2c.order_payment_extend.options');
        if ($obj_payment_extends_op)
        {
            foreach ($obj_payment_extends_op as $obj)
                $obj->order_pay_extends($sdf, $sdf_order);
        }
    }

    public function order_pay_refund($rel_id,$sdf,&$refund_status,$relmoney,&$refund_type){
        //判断订单是否已经违法
        $objorders = $this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $orderinfo = $objorders->dump($rel_id,'*',$subsdf);

        //生成退款单
        $sdf_ref['money'] = $relmoney;
        $sdf_ref['order_id'] = $rel_id;

        $refunds = app::get('ectools')->model('refunds');
        $sdf_ref['op_id'] = '0';
        $sdf_ref['op_name'] = 'auto';                                    
        $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');
        $sdf_ref['payment'] =  $orderinfo['payinfo']['pay_app_id'];

        $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf_ref['payment']);
            
        $time = time();
        $sdf_ref['refund_id'] = $refund_id = $refunds->gen_id();
        $sdf_ref['pay_app_id'] = $sdf_ref['payment'];
        $sdf_ref['member_id'] = $orderinfo['member_id'] ? $orderinfo['member_id'] : 0;

        $obj_members = app::get('pam')->model('account');
        $buy_name = $obj_members->getRow('login_name',array('account_id'=>$sdf_ref['member_id']));
        $sdf_ref['account'] = $buy_name['login_name'];

        $sdf_ref['currency'] = $sdf['currency'];
        $sdf_ref['paycost'] = 0;
        $sdf_ref['cur_money'] = $relmoney;
        //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $orderinfo['cur_rate']));
        $sdf_ref['t_begin'] = $time;
        $sdf_ref['t_payed'] = $time;
        $sdf_ref['t_confirm'] = $time;
        $sdf_ref['pay_object'] = 'order';
        //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
        $sdf_ref['status'] = 'ready';
        $sdf_ref['app_name'] = $arrPaymentInfo['app_name'];
        $sdf_ref['app_version'] = $arrPaymentInfo['app_version'];
        $sdf_ref['refund_type'] = '1';
        $sdf_ref['is_safeguard'] = '1';

        $obj_refunds = kernel::single("ectools_refund");
        $controller = kernel::single('aftersales_ctl_site_member',array('app'=>app::get('aftersales'),'arg1'=>false));
        $rs_buyer = $obj_refunds->generate($sdf_ref,$controller,$msg);
        
        //退款开始
        $obj_bills = app::get('ectools')->model('order_bills');
        $order_id = $obj_bills->getRow('rel_id',array('bill_id'=>$refund_id));
        $payment_id = $refunds->get_payment($order_id['rel_id']);
        $obj_payment = app::get('ectools')->model('payments');
        $cur_money = $obj_payment->dump($payment_id['bill_id'],'*');

        //判断是否是合并付款
        if($cur_money['merge_payment_id'] != ''){
            $payment_id['bill_id'] = $cur_money['merge_payment_id'];
            $cur_money['cur_money'] = 0;
            $total = $obj_payment->getList('*',array('merge_payment_id'=>$payment_id['bill_id'],'status'=>'succ'));
            foreach($total as $key=>$val){
                $cur_money['cur_money'] = $cur_money['cur_money'] + $val['cur_money'];
            }
        }

        //退款
        $refund_data = $refunds->getRow('*',array('return_id'=>$sdf_ref['refund_id']));
        //echo "<pre>";print_r($refund_data);exit;
        if($refund_data['pay_app_id'] != 'deposit'){
            $obj_refunds = kernel::single("ectools_refund");
            if($refund_data['cur_money'] == 0){
				$result = "success";
                $ref_rs = $obj_refunds->generate_after($sdf);
            }else{
                //$refund_data['payment_info'] = $cur_money;
				$refund_data = $obj_refunds->make_data($refund_data);
				if($refund_data){
					foreach($refund_data as $key=>$re_data){
						$result = $obj_refunds->dorefund($re_data,$this);
						$ref_rs = $obj_refunds->callback($re_data,$result);
					}
				}
            }
        }else{
			//预存款退款
			$ref_rs = $obj_refunds->refund_advance($refund_data,$msg);
		}

        if($relmoney == $sdf['cur_money']){
            $refund_status=true;
            $refund_type='1';
        }else{
            $refund_type='2';
        }

        
    }

	private function updateRank($order_id=0){
        if(!$order_id) return true;
        $objOrder = app::get('b2c')->model('orders');
        $subsdf = array('order_objects'=>array('obj_id',array('order_items'=>array('goods_id,nums',array(':goods'=>array('goods_id,count_stat'))))), 'order_pmt'=>array('*'));
        $sdf_order = $objOrder->dump($order_id, 'order_id', $subsdf);
        if(!$sdf_order['order_id']) return true;
        $objGoods = app::get('b2c')->model('goods');
        $weekMark = 'buy';
        $item = 'buy_count';
        foreach((array)$sdf_order['order_objects'] as $objects){
            if(!$objects['obj_id']) continue;
            foreach((array)$objects['order_items'] as $items){
                if(!isset($items['goods']['goods_id']) || $items['goods']['goods_id'] != $items['goods_id']) continue;
                $count_stat = unserialize($items['goods']['count_stat']);
                $dayNum = $objGoods->day(time());
                $dayBegin = floor(mktime(0,0,0,date('m'),01,date('Y'))/86400);
                $dayEnd = $dayBegin + date('t', mktime(0,0,0,date('m'),date('d'),date('Y')));
                $weekNum = $num = intval($items['quantity']);
                if(isset($count_stat[$weekMark])){
                    foreach($count_stat[$weekMark] as $day => $countNum){
                        if($dayNum > $day+9) unset($count_stat[$weekMark][$day]);
                        if($dayNum < $day+8) $weekNum += $countNum;
                    }
                }
                $count_stat[$weekMark][$dayNum] += $num;
                $sqlCol = '';
                $monthMark = 'mbuy'; 
                $monthNum = $num;
                if(isset($count_stat[$monthMark])){
                    foreach($count_stat[$monthMark] as $day => $countNum){
                        //if($dayBegin>$day || $dayEnd<$day) unset($count_stat[$monthMark][$day]);
                        //else $monthNum += $countNum;
                        if($dayNum > $day+32) unset($count_stat[$monthMark][$day]);
                        if($dayNum < $day+30) $monthNum += $countNum;
                    }
                }
                $count_stat[$monthMark][$dayNum] += $num;
                $sqlCol .= ','.$weekMark.'_m_count='.intval($monthNum);
                $objStore = app::get('business')->model('storemanger');
                //sdb_business_storemanger   sdb_b2c_goods
                $sql =" update sdb_business_storemanger as s inner join ".
                  " (select sum(buy_m_count)+".intval($num)." as _count,store_id  from sdb_b2c_goods where store_id in (select store_id from sdb_b2c_goods where goods_id=".intval($items['goods_id']).") group by store_id) as c on s.store_id=c.store_id ".
                  " set s.buy_m_count=c._count ";

                $objGoods->db->exec($sql);
                //sdb_b2c_goods  更新商品信息表中数据信息
                $sqlCol .= ','.$weekMark.'_w_count='.intval($weekNum).', count_stat=\''.serialize($count_stat).'\'';
                $objGoods->db->exec("UPDATE sdb_b2c_goods SET ".$item." = ".$item."+".intval($num).$sqlCol." WHERE goods_id =".intval($items['goods_id']));
            }
        }

        //获取该订单的 会员id 和会员name
        $orderData = $objGoods->db->selectrow('SELECT o.member_id, m.login_name,o.ship_email FROM sdb_b2c_orders o LEFT JOIN sdb_pam_account m ON o.member_id = m.account_id WHERE o.order_id = '.$objGoods->db->quote($order_id));
        //获取该订单的 产品id  产品价格 产品名称
        $orderItem = $objGoods->db->select('SELECT i.price, p.goods_id, i.product_id, p.name,p.spec_info, i.nums FROM sdb_b2c_order_items i LEFT JOIN sdb_b2c_products p ON p.product_id = i.product_id WHERE i.order_id = '.$objGoods->db->quote($order_id));
        foreach( $orderItem as $iKey => $iValue ){
            // sdb_b2c_sell_logs 往商品售卖表中添加一条信息
            $sql = 'INSERT INTO sdb_b2c_sell_logs (member_id,name,price,goods_id,product_id,product_name,spec_info,number,createtime) VALUES ( "'.($orderData['member_id']?$orderData['member_id']:0).'", "'.($orderData['login_name']?$orderData['login_name']:$orderData['ship_email']).'", "'.$iValue['price'].'", "'.$iValue['goods_id'].'", "'.$iValue['product_id'].'", "'.htmlspecialchars($iValue['name']).'", "'.$iValue['spec_info'].'" , "'.$iValue['nums'].'", "'.time().'" )';
            $objGoods->db->exec($sql);
        }
    }

    public function check_payed($order_id){
        $obj_order_bills = app::get('ectools')->model('order_bills');
        $payed=0;
        $order_bills  =  $obj_order_bills->getList('*',array('rel_id'=>$order_id,'bill_type'=>'payments','pay_object'=>'order'));
        if(!empty($order_bills)){
            $bills_id =array();
            foreach($order_bills as $val){
                $bills_id[]=$val['bill_id'];
            }
            $obj_payments = app::get('ectools')->model('payments');
            $payments = $obj_payments->getList('*',array('payment_id'=>$bills_id,'status'=>'succ'));
            if(!empty($payments)){
                foreach($payments as $val ){
                    $payed+=$val['cur_money'];
                }
            }
        }
        return $payed;
    }
}
