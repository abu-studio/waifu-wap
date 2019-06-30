<?php
 

class b2c_ctl_site_paycenter extends b2c_frontpage{

    var $noCache = true;

    public function __construct(&$app){
        set_time_limit(120);
        ignore_user_abort(true);

        parent::__construct($app);
        $this->_response->set_header('Cache-Control', 'no-store');
        if(!$this->action) $this->action = 'index';
        $this->action_view = $this->action.".html";
        kernel::single('base_session')->start();
        if(!empty($_SESSION['sfsc']['vcat'])){
            $this->set_tmpl('hfcart');
        }

    }

    /**
     * 生成支付单据处理
     * @params string - pay_object ('order','recharge','joinfee')
     * @return null
     */
    public function dopayment($pay_object='order',$type='manual')
    {
        if ($pay_object)
        {
            $arrMember = $this->get_current_member();
            $objOrders = $this->app->model('orders');
            $objPay = kernel::single('ectools_pay');
            $objMath = kernel::single('ectools_math');
            // 得到商店名称
            $shopName = $this->app->getConf('system.shopname');

            $obj_filter = kernel::single('b2c_site_filter');

            // Post payment information.
            $sdf = $obj_filter->check_input($_POST['payment']);
			//支付方式重新赋值（避免过滤逗号）
			$sdf['pay_app_id'] = $_POST['payment']['pay_app_id'];

            $sdf['money'] = floatval($sdf['money']);
            $sfsc_payed_money = 0;
            //ajx 防止恶意修改支付金额，导致支付状态异常
            if($pay_object == 'order'){
                $orders = $objOrders->dump($sdf['order_id']);
                if($type=='manual'&&$orders['callback_status']=='error'){
                    $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders'));
                    $this->end(false, '系统繁忙，请稍后再试！');
                }
                if('3' == $orders['pay_status'] && $orders['payed']>0){
                    $sfsc_payed_money = $orders['payed'];
                }
                $sdf['cur_amount'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                $orders['total_amount'] = $objMath->number_div(array($orders['cur_amount'], $orders['cur_rate']));
                $sdf['money'] = floatval($orders['total_amount'] - $orders['payed']);
                $sdf['currency']=$orders['currency'];
                $sdf['cur_money'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                $sdf['cur_rate'] = $orders['cur_rate'];
                $sdf['QS_payment_id'] = trim($orders['java_payment_company']);
            }
            
            $payment_id = $sdf['payment_id'] = $objPay->get_payment_id();

            if ($arrMember)
                $sdf['member_id'] = $arrMember['member_id'];

            if (!$sdf['pay_app_id']){
                if($type=='manual'){
                    $this->splash('failed', 'close', app::get('b2c')->_('支付方式不能为空！'));
                }else{
                    echo '支付方式不能为空！';die;
                }
            }
           
            $sdf['pay_object'] = $pay_object;
            $sdf['shopName'] = $shopName;

            switch ($sdf['pay_object'])
            {
                case 'order':
                $arrOrders = $objOrders->dump($sdf['order_id'], '*');

                //线下支付
                if ($sdf['pay_app_id'] == 'offline')
                {
                    if (isset($sdf['member_id']) && $sdf['member_id'])
                        $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderdetail', 'arg0'=>$sdf['order_id']));
                    else
                        $this->begin(array('app'=>'b2c','ctl'=>'site_order','act'=>'index', 'arg0'=>$sdf['order_id']));
                }

                //判断是银盛支付时 是否选择了银行
				if($sdf['pay_app_id'] == 'ysepay'&&!$sdf['banktype']){
					$this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderPayments', 'arg0'=>$sdf['order_id']));
					 $this->end(false,'请选择一家银行');
				}

                if ($arrOrders['payinfo']['pay_app_id'] != $sdf['pay_app_id'])
                {
                    $class_name = "";
                    $obj_app_plugins = kernel::servicelist("ectools_payment.ectools_mdl_payment_cfgs");

                    foreach ($obj_app_plugins as $obj_app)
                    {
                        $app_class_name = get_class($obj_app);
                        $arr_class_name = explode('_', $app_class_name);
                        if (isset($arr_class_name[count($arr_class_name)-1]) && $arr_class_name[count($arr_class_name)-1])
                        {
                            if ($arr_class_name[count($arr_class_name)-1] == $sdf['pay_app_id'])
                            {
                                $pay_app_ins = $obj_app;
                                $class_name = $app_class_name;
                                break;
                            }
                        }
                        else
                        {
                            if ($app_class_name == $sdf['pay_app_id'])
                            {
                                $pay_app_ins = $obj_app;
                                $class_name = $app_class_name;
                                break;
                            }
                        }
                    }
                    $strPaymnet = app::get('ectools')->getConf($class_name);
                    $arrPayment = unserialize($strPaymnet);

                    $cost_payment = $objMath->number_multiple(array($objMath->number_minus(array($arrOrders['total_amount'], $arrOrders['payinfo']['cost_payment'])), $arrPayment['setting']['pay_fee']));
                    $total_amount = $objMath->number_plus(array($objMath->number_minus(array($arrOrders['total_amount'], $arrOrders['payinfo']['cost_payment'])), $cost_payment));
                    $cur_money = $objMath->number_multiple(array($total_amount, $arrOrders['cur_rate']));

                    // 更新订单支付信息
                    $arr_updates = array(
                        'order_id' => $sdf['order_id'],
                        'payinfo' => array(
                                        'pay_app_id' => $sdf['pay_app_id'],
                                        'cost_payment' => $objMath->number_multiple(array($cost_payment, $arrOrders['cur_rate'])),
                                    ),
                        'total_amount' => $total_amount,
                        'cur_amount' => $cur_money,
                    );
                    $changepayment_services = kernel::servicelist('b2c_order.changepayment');
                    foreach($changepayment_services as $changepayment_service)
                    {
                        $changepayment_service->generate($arr_updates);
                    }

                    $objOrders->save($arr_updates);

                    $arrOrders = $objOrders->dump($sdf['order_id'], '*');
                    /** 需要想中心发送支付方式修改的动作 **/
                    $obj_b2c_pay = kernel::single('b2c_order_pay');
                    $obj_b2c_pay->order_payment_change($arrOrders);
                }

                // 检查是否能够支付
                $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));

                //更新订单后重新获得应该支付的金额 Add by PanF   2014-06-25  begin
                $orders = $objOrders->dump($sdf['order_id']);
                $sdf['cur_amount'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                $orders['total_amount'] = $objMath->number_div(array($orders['cur_amount'], $orders['cur_rate']));
                $sdf['money'] = floatval($orders['total_amount'] - $orders['payed']);
                $sdf['currency']=$orders['currency'];
                $sdf['cur_money'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                $sdf['cur_rate'] = $orders['cur_rate'];
                //原选择的支付方式（支付宝）有手续费，再选择其他无手续费的支付方式，post数据中含有手续费 Add by PanF   2014-06-25 end
                $sdf_post = $sdf;
                $sdf_post['money'] = $sdf['cur_money'];
                if (!$obj_checkorder->check_order_pay($sdf['order_id'],$sdf_post,$message))
                {
                    if ($sdf['pay_app_id'] != 'offline'){
                    $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderPayments', 'arg0'=>$sdf['order_id']));
                        }
                        $this->end(false, $message);
                }
                if ($sdf['pay_app_id'] == 'offline')
                {
                     $this->end(true,  app::get('b2c')->_('订单已成功提交了'));
                }

                if (!$sdf['pay_app_id'])
                    $sdf['pay_app_id'] = $arrOrders['payinfo']['pay_app_id'];

                $sdf['currency'] = $arrOrders['currency'];
                $sdf['total_amount'] = $arrOrders['total_amount'];
                $sdf['payed'] = $arrOrders['payed'] ? $arrOrders['payed'] : '0.000';
                $sdf['money'] = $objMath->number_div(array($sdf['cur_money'], $arrOrders['cur_rate']));

                $sdf['payinfo']['cost_payment'] = $arrOrders['payinfo']['cost_payment'];

                    // 相关联的id.
                    $sdf['rel_id'] = $sdf['order_id'];
                    break;
                case 'recharge':
                    // 得到充值信息
                    $sdf['rel_id'] = $sdf['member_id'];
                    break;
                case 'joinfee':
                    // 得到加盟费信息
                    break;
                
                case 'earnest':
                    //支付保证金
                    //判断是银盛支付时 是否选择了银行
                    if($sdf['pay_app_id'] == 'ysepay'&&!$sdf['banktype']){
                        $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderPayments', 'arg0'=>$sdf['order_id']));
                         $this->end(false,'请选择一家银行');
                    }
                   
                    $sdf['rel_id'] = $sdf['member_id']; 
                    break;
                default:
                    // 其他的卡充值
                    $sdf['rel_id'] = $sdf['rel_id'];
                    break;

            }
            
            if ($sdf['pay_app_id'] == 'deposit')
                $sdf['return_url'] = "";
            else
                if (!$sdf['return_url'])
                    $sdf['return_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$payment_id));

            $sdf['status'] = 'ready';
            // 需要加入service给其他实体和虚拟卡
            $obj_prepaid = kernel::service('b2c.prepaidcards.add');
             
            $is_save_prepaid = false;
            if ($obj_prepaid)
            {
                $is_save_prepaid = $obj_prepaid->gen_charge_log($sdf);
            }

            //包含福点支付时 获取帐号余额
            if(strpos($sdf['pay_app_id'],'sfscpay') !== false)
            {
                //获取我的福点余额
                $_sjson = array(
                    'METHOD'=>'getBalanceInfoByRelationId',
                    'RELATION_ID'=> empty($sdf['QS_payment_id']) ? $this->member['uname'] : $sdf['QS_payment_id'],
                    'QUERY_TIME'=>""
                );
                $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
                $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
                if($tmpdata != null && gettype($tmpdata) == "object"){
                    $amountOfAccount= SFSC_HttpClient::objectToArray($tmpdata);
                }else{
                    $amountOfAccount['RESULT_DATA'] = array('INCOME'=>0,'SUM'=>0,'EXPENSES'=>0);
                }
                $needs_sfsc_money = $amountOfAccount['RESULT_DATA']['SUM'];
                if($sfsc_payed_money > 0){
                    $needs_sfsc_money = $sfsc_payed_money;
                }

                $total_money = $sdf['money'];
                $total_amount = $sdf['total_amount'];
                $total_cur_money = $sdf['cur_money'];
                //判断是否组合支付
                if(strpos($sdf['pay_app_id'],',')){
                    //获取组合支付开关状态
                    $is_open_combine_pay = app::get('ectools')->getConf('site.is_open_combine_pay');
                    if(!$is_open_combine_pay){
                        if($type=='manual'){
                            $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders'));
                            $this->end(false, '组合支付功能已关闭！');
                        }else{
                            echo '组合支付功能已关闭！';die;
                        }
                    }

                    //将组合支付的两种方式分离为两组数据
                    $sfsc_sdf = $sdf;
                    $sfsc_sdf['pay_app_id'] = 'sfscpay';
                    $arr_payments=explode(',',$sdf['pay_app_id']);
                    foreach($arr_payments as $k=>$v){
                        if($v=='sfscpay')unset($arr_payments[$k]);
                    }
                    $sdf['pay_app_id'] = implode(',',$arr_payments);

                    $sfsc_sdf['money'] = round($needs_sfsc_money , 2);
                    $sfsc_sdf['total_amount'] = $sfsc_sdf['money'];
                    $sfsc_sdf['cur_money'] = $sfsc_sdf['money'];
                    $sfsc_sdf['cur_amount'] = $sfsc_sdf['money'];
                    $sfsc_sdf['action'] = 'freeze';

                    $sdf['money'] = round($orders['total_amount'] - $needs_sfsc_money ,2);
                    $sdf['total_amount'] = $sdf['money'];
                    $sdf['cur_money'] = round($orders['cur_amount'] - $needs_sfsc_money ,2);
                    $sdf['cur_amount'] = $sdf['money'];

                    do{
                        $sfsc_sdf['payment_id'] = $objPay->get_payment_id();
                    }while($sfsc_sdf['payment_id']==$sdf['payment_id']);
                    //判断现存福点支付单
                    $check_sfsc = $objPay->get_payment_by_order($sfsc_sdf['order_id'],'sfscpay','sfsc_freeze');
                    if(isset($check_sfsc['payment_id'])){
                        $sfsc_sdf['payment_id'] = $check_sfsc['payment_id'];
                        $sfsc_sdf['money'] = floatval($check_sfsc['money']);
                        $sfsc_sdf['cur_money'] = floatval($check_sfsc['cur_money']);

                        $sdf['money'] = floatval($orders['total_amount'] - $check_sfsc['money']);
                        $sdf['cur_money'] = floatval($orders['cur_amount'] - $check_sfsc['cur_money']);
                        $this->combine_payment($sdf,$sfsc_sdf);
                    }elseif($needs_sfsc_money >= $orders['total_amount']){
                        $sdf['money'] = $needs_sfsc_money;
                        $sdf['cur_money'] = $needs_sfsc_money;
                        $sdf['pay_app_id'] = 'sfscpay';
                    }else{
                        $this->combine_payment($sdf,$sfsc_sdf);
                    }
                }else{
                    //没有组合支付，只是 福点支付 时，查下福点余额是否足够
                    if(-1 == bccomp($needs_sfsc_money , $total_cur_money , 2)){
                        $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders'));
                        $msg = '福点帐户余额不足，请更换支付方式';
                        $this->end(false, $msg);
                    }
                }
            }


			//支付宝传入订单ID的信息
			$sdf['order_ids']=$sdf['order_id'];
             
            $is_payed = $objPay->generate($sdf, $this, $msg);
             
            if ($is_save_prepaid && $is_payed)
            {
                $obj_prepaid->update_charge_log($sdf);
            }
		    
            if ($sdf['pay_app_id'] == 'deposit')
            {
                // 预存款支付
                if (isset($arrMember['member_id']) && $arrMember['member_id']){
                    $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders'));
                }else{
                    $this->begin(array('app'=>'b2c','ctl'=>'site_order','act'=>'index', 'arg0'=>$sdf['order_id']));
                }

                if ($is_payed){
                    $this->redirect(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$sdf['payment_id']));
                    //$this->end(true,  app::get('b2c')->_('预存款支付成功！'), array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$sdf['payment_id']));
                }else{
                    $this->end(false, $msg);
                }

            }elseif($sdf['pay_app_id'] == 'sfscpay'){
                if (isset($arrMember['member_id']) && $arrMember['member_id'])
                    $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders'));
                else
                    $this->begin(array('app'=>'b2c','ctl'=>'site_order','act'=>'index', 'arg0'=>$sdf['order_id']));
                if($type=='manual'){
                    if ($is_payed){
						if($orders['order_type'] == 'sand'){
                            $this->redirect(array('app'=>'b2c','ctl'=>'site_member','act'=>'sandlist'));
							//$this->end(true,  app::get('b2c')->_('福点支付成功！'), array('app'=>'b2c','ctl'=>'site_member','act'=>'sandlist'));
						}else{
                            $this->redirect(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$sdf['payment_id']));
							//$this->end(true,  app::get('b2c')->_('福点支付成功！'), array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$sdf['payment_id']));
						}
                    }else{
                        $this->end(false, $msg);
                    }
                }else{
                    if ($is_payed){
                    }else{
                       die;
                    }
                }
            }
        }
    }

	//组合支付逻辑-hy
	public function combine_payment(&$sdf,$sfsc_sdf){
		$objPay = kernel::single('ectools_pay');
        $objPayments = app::get('ectools')->model('payments');
		$db = kernel::database();
		$transaction_status = $db->beginTransaction();

        $is_freezed = true;
		if($sfsc_sdf['money'] > 0){
            $is_freezed = $objPay->generate($sfsc_sdf, $this, $msg);
        }

		if($is_freezed){
			$objPayments->update(array('status'=>'sfsc_freeze'),array('payment_id'=>$sfsc_sdf['payment_id']));
            if($sfsc_sdf['total_amount'] > 0){
                $objOrders = app::get("b2c")->model('orders');
                $objOrders->update(array('payed' => $sfsc_sdf['total_amount'] , 'pay_status' => '3') , array('order_id' => $sfsc_sdf['order_id']));
            }
			$db->commit($transaction_status);
		}else{
			$db->rollback();
			$this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderPayments','arg0'=>$sdf['order_id']));
			$this->end(false, '福点部分冻结失败！');
		}
		
		$transaction_status = $db->beginTransaction();
		$check = $objPay->get_payment_by_order($sdf['order_id'],$sdf['pay_app_id'],'ready');
		if(!isset($check['payment_id'])){
			$check = $objPay->get_payment_by_order($sdf['order_id'],$sdf['pay_app_id'],'succ');
		}
		if(isset($check['payment_id'])){
			$classname = 'ectools_payment_plugin';
			$classname .= '_'.$sdf['pay_app_id'];
			if(class_exists($classname)){
				$class=new $classname($this->app);
				if(method_exists($class,'check_payment_vaild')){
					$check_valid = $class->check_payment_vaild($check['payment_id']);
					if($check_valid['result']==true&&$check_valid['status']=='valid'){
						$sdf['payment_id'] = $check['payment_id'];
						$db->commit($transaction_status);
					}else{
						if($check_valid['status']=='closed'){
							$objPayments->update(array('status'=>'cancel'),array('payment_id'=>$check['payment_id']));
							$db->commit($transaction_status);
						}elseif($check_valid['status']=='success'||$check_valid['status']=='finished'){
							$db->rollback();
							$this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderPayments','arg0'=>$sdf['order_id']));
							$this->end(false, '第三方支付单已支付完成！');
						}else{
							$db->rollback();
							$this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderPayments','arg0'=>$sdf['order_id']));
							$this->end(false, '第三方支付单状态校验失败！');
						}
					}
				}
			}
		}
		$db->commit($transaction_status);
	}

    public function result($payment_id)
    {
        
        $app_ectools = app::get('ectools');
        $oPayment = $app_ectools->model('payments');
        $objOrderItems = $this->app->model('order_items');
        $objGoods = $this->app->model('goods');
        $objOrders = $this->app->model('orders');
        $goods_ids = array();
        $order_ids = array();
        if($payment_id){
            $pay_data = $oPayment->getList('*',array('merge_payment_id'=>$payment_id));
        }
        if($pay_data){
            foreach($pay_data as $key=>$val){
                $subsdf = array('orders'=>array('*'));
                $sdf_payment = $oPayment->dump($val['payment_id'], '*', $subsdf);

                if ($sdf_payment['orders'])
                {
                    // 得到订单日志
                    $objOrderlog = $this->app->model('order_log');
                    foreach ($sdf_payment['orders'] as $order_id=>$arrOrderbills)
                    {
                        $orderlog = $objOrderlog->get_latest_orderlist($arrOrderbills['rel_id'], $arrOrderbills['pay_object'], $arrOrderbills['bill_type']);
                        $arrOrderlogs[$orderlog['log_id']] = $orderlog;
                    }

                    $this->pagedata['payment'] = &$sdf_payment;
                    $this->pagedata['payment']['order_id'] = $order_id;
                    $this->pagedata['orderlog'] = $arrOrderlogs;
                }

                $this->pagedata['pay_succ'] = $app_ectools->getConf('site.paycenter.pay_succ');
                $this->pagedata['pay_failure'] = $app_ectools->getConf('site.paycenter.pay_failure');
                $this->pagedata['send_immediately'] = app::get('b2c')->getConf('site.order.send_type');
                $this->pagedata['base_path'] = kernel::base_url().'/';
                $this->pagedata['payment_id'] = $payment_id;
            }
            $this->page('site/paycenter/result_merge.html');
            
        }else{
            $subsdf = array('orders'=>array('*'));
            $sdf_payment = $oPayment->dump($payment_id, '*', $subsdf); 
            if ($sdf_payment['orders'])
            {
                // 得到订单日志
                $objOrderlog = $this->app->model('order_log');
                foreach ($sdf_payment['orders'] as $order_id=>$arrOrderbills)
                {
                    $orderlog = $objOrderlog->get_latest_orderlist($arrOrderbills['rel_id'], $arrOrderbills['pay_object'], $arrOrderbills['bill_type']);
                    $arrOrderlogs[$orderlog['log_id']] = $orderlog;
                }

                $this->pagedata['payment'] = &$sdf_payment;
                $this->pagedata['payment']['order_id'] = $order_id;
                $this->pagedata['orderlog'] = $arrOrderlogs;

            }
            $this->pagedata['pay_failure'] = $app_ectools->getConf('site.paycenter.pay_failure');
            $this->pagedata['send_immediately'] = app::get('b2c')->getConf('site.order.send_type');
            $this->pagedata['base_path'] = kernel::base_url().'/';
            $this->pagedata['payment_id'] = $payment_id;
            //企业中心订单成功之后的提示信息
            $minfo=$this->get_current_member();
            if($minfo['seller'] == 'qiye'){
                $sdf_payment['money']=sprintf("%1.2f",$sdf_payment['money']);
                $this->splash('success', $this->gen_url(array('app'=>'qiyecenter','ctl'=>'site_member','act'=>'orders')), app::get('b2c')->_('订单'.$order_id.': 在线付款 ￥ '.$sdf_payment['money'].' 成功。'));
            }else{
                $this->pagedata['pay_succ'] = $app_ectools->getConf('site.paycenter.pay_succ');
            }
            $this->page('site/paycenter/result_'.$sdf_payment['pay_type'].'.html');         
        }

    }

    /**
     * 合并付款
     * @params string - pay_object ('order','recharge','joinfee')
     * @return null
     */
    public function all_dopayment($pay_object='order',$type='manual')
    {
        if ($pay_object)
        {
            $arrMember = $this->get_current_member();
            $objOrders = $this->app->model('orders');
            $objPay = kernel::single('ectools_pay');
            $objMath = kernel::single('ectools_math');
            // 得到商店名称
            $shopName = $this->app->getConf('system.shopname');

            $obj_filter = kernel::single('b2c_site_filter');

            // Post payment information.
            
            $merge_payment_id = $objPay->get_payment_id();
            $money = 0;
            //判断是银盛支付时 是否选择了银行

            if($obj_filter->check_input($_POST['payments']['pay_app_id']) == 'ysepay'&&!$obj_filter->check_input($_POST['payments']['banktype'])){
                foreach($obj_filter->check_input($_POST['payment']) as $k=>$v){
                    $orders_id[] = $v['order_id'];
                }
                $orderStr = base64_encode(implode('|',$orders_id));
                $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'all_orderPayments', 'args'=>array(false,'',$orderStr)));
                $this->end(false,'请选择一家银行');
            }
			//支付宝将订单传过去
			$order_ids=array();
            foreach($obj_filter->check_input($_POST['payment']) as $all_key => $all_val){
                $sdf = $all_val;
				$order_ids[] = $sdf['order_id'];
                $sdf['bankaccounttype'] = $obj_filter->check_input($_POST['payments']['bankaccounttype']);
                $sdf['banktype'] = $obj_filter->check_input($_POST['payments']['banktype']);
                $sdf['merge_payment_id'] = $merge_payment_id;

                $sdf['money'] = floatval($sdf['money']);

                //ajx 防止恶意修改支付金额，导致支付状态异常
                if($pay_object == 'order'){
                    $orders = $objOrders->dump($sdf['order_id']);
                    if($type=='manual'&&$orders['callback_status']=='error'){
                        $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders'));
                        $this->end(false, '系统繁忙，请稍后再试！');
                    }
                    $sdf['cur_amount'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                    $orders['total_amount'] = $objMath->number_div(array($orders['cur_amount'], $orders['cur_rate']));
                    $sdf['money'] = floatval($orders['total_amount'] - $orders['payed']);
                    $sdf['currency']=$orders['currency'];
                    $sdf['cur_money'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                    $sdf['cur_rate'] = $orders['cur_rate'];
                }

                $payment_id = $sdf['payment_id'] = $objPay->get_payment_id();

                if ($arrMember)
                    $sdf['member_id'] = $arrMember['member_id'];

                if (!$sdf['pay_app_id'])
                    $this->splash('failed', 'close', app::get('b2c')->_('支付方式不能为空！'));

                $sdf['pay_object'] = $pay_object;
                $sdf['shopName'] = $shopName;


                switch ($sdf['pay_object'])
                {
                    case 'order':
                    $arrOrders = $objOrders->dump($sdf['order_id'], '*');

                    //线下支付
                    if ($sdf['pay_app_id'] == 'offline')
                    {
                        if (isset($sdf['member_id']) && $sdf['member_id'])
                            $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderdetail', 'arg0'=>$sdf['order_id']));
                        else
                            $this->begin(array('app'=>'b2c','ctl'=>'site_order','act'=>'index', 'arg0'=>$sdf['order_id']));
                    }

                    if ($arrOrders['payinfo']['pay_app_id'] != $sdf['pay_app_id'])
                    {
                        $class_name = "";
                        $obj_app_plugins = kernel::servicelist("ectools_payment.ectools_mdl_payment_cfgs");
                        foreach ($obj_app_plugins as $obj_app)
                        {
                            $app_class_name = get_class($obj_app);
                            $arr_class_name = explode('_', $app_class_name);
                            if (isset($arr_class_name[count($arr_class_name)-1]) && $arr_class_name[count($arr_class_name)-1])
                            {
                                if ($arr_class_name[count($arr_class_name)-1] == $sdf['pay_app_id'])
                                {
                                    $pay_app_ins = $obj_app;
                                    $class_name = $app_class_name;
                                }
                            }
                            else
                            {
                                if ($app_class_name == $sdf['pay_app_id'])
                                {
                                    $pay_app_ins = $obj_app;
                                    $class_name = $app_class_name;
                                }
                            }
                        }
                        $strPaymnet = app::get('ectools')->getConf($class_name);
                        $arrPayment = unserialize($strPaymnet);

                        $cost_payment = $objMath->number_multiple(array($objMath->number_minus(array($arrOrders['total_amount'], $arrOrders['payinfo']['cost_payment'])), $arrPayment['setting']['pay_fee']));
                        $total_amount = $objMath->number_plus(array($objMath->number_minus(array($arrOrders['total_amount'], $arrOrders['payinfo']['cost_payment'])), $cost_payment));
                        $cur_money = $objMath->number_multiple(array($total_amount, $arrOrders['cur_rate']));

                        // 更新订单支付信息
                        $arr_updates = array(
                            'order_id' => $sdf['order_id'],
                            'payinfo' => array(
                                            'pay_app_id' => $sdf['pay_app_id'],
                                            'cost_payment' => $objMath->number_multiple(array($cost_payment, $arrOrders['cur_rate'])),
                                        ),
                            'total_amount' => $total_amount,
                            'cur_amount' => $cur_money,
                        );

                        $changepayment_services = kernel::servicelist('b2c_order.changepayment');
                        foreach($changepayment_services as $changepayment_service)
                        {
                            $changepayment_service->generate($arr_updates);
                        }

                        $objOrders->save($arr_updates);

                        $arrOrders = $objOrders->dump($sdf['order_id'], '*');
                        /** 需要想中心发送支付方式修改的动作 **/
                        $obj_b2c_pay = kernel::single('b2c_order_pay');
                        $obj_b2c_pay->order_payment_change($arrOrders);
                    }
                    // 检查是否能够支付
                    $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));

                    //更新订单后重新获得应该支付的金额 Add by PanF   2014-06-25  begin
                    $orders = $objOrders->dump($sdf['order_id']);
                    $sdf['cur_amount'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                    $orders['total_amount'] = $objMath->number_div(array($orders['cur_amount'], $orders['cur_rate']));
                    $sdf['money'] = floatval($orders['total_amount'] - $orders['payed']);
                    $sdf['currency']=$orders['currency'];
                    $sdf['cur_money'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                    $sdf['cur_rate'] = $orders['cur_rate'];
                    //原选择的支付方式（支付宝）有手续费，再选择其他无手续费的支付方式，post数据中含有手续费 Add by PanF   2014-06-25 end

                    $sdf_post = $sdf;
                    $sdf_post['money'] = $sdf['cur_money'];
                    //判断是否已经支付
                    if($sdf['cur_money'] > 0){
                        if (!$obj_checkorder->check_order_pay($sdf['order_id'],$sdf_post,$message))
                        {
                                if ($sdf['pay_app_id'] != 'offline'){
                            $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orderPayments', 'arg0'=>$sdf['order_id']));
                                }
                                $this->end(false, $message);
                        }
                    }

                    if ($sdf['pay_app_id'] == 'offline')
                    {
                         $this->end(true,  app::get('b2c')->_('订单已成功提交了'));
                    }

                    if (!$sdf['pay_app_id'])
                        $sdf['pay_app_id'] = $arrOrders['payinfo']['pay_app_id'];

                    $sdf['currency'] = $arrOrders['currency'];
                    $sdf['total_amount'] = $arrOrders['total_amount'];
                    $sdf['payed'] = $arrOrders['payed'] ? $arrOrders['payed'] : '0.000';
                    $sdf['money'] = $objMath->number_div(array($sdf['cur_money'], $arrOrders['cur_rate']));

                    $sdf['payinfo']['cost_payment'] = $arrOrders['payinfo']['cost_payment'];

                        // 相关联的id.
                        $sdf['rel_id'] = $sdf['order_id'];
                        break;
                    case 'recharge':
                        // 得到充值信息
                        $sdf['rel_id'] = $sdf['member_id'];
                        break;
                    case 'joinfee':
                        // 得到加盟费信息
                        break;
                    
                    case 'earnest':
                        //支付保证金
                        $sdf['rel_id'] = $sdf['member_id'];
                        break;
                    default:
                        // 其他的卡充值
                        $sdf['rel_id'] = $sdf['rel_id'];
                        break;

                }

                if ($sdf['pay_app_id'] == 'deposit')
                    $sdf['return_url'] = "";
                else
                    if (!$sdf['return_url'])
                        $sdf['return_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$merge_payment_id));

                $sdf['status'] = 'ready';
                // 需要加入service给其他实体和虚拟卡
                $obj_prepaid = kernel::service('b2c.prepaidcards.add');
                 
                $is_save_prepaid = false;
                if ($obj_prepaid)
                {
                    $is_save_prepaid = $obj_prepaid->gen_charge_log($sdf);
                }

                if($sdf['cur_money'] > 0){
                    $is_payed = $objPay->all_generate($sdf, $this, $msg);
                }

                $money = $money + $sdf['cur_money'];

            }
            $sdf['cur_money'] = $money;
            $sdf['money'] = $money;
            $sdf['cur_amount'] = $money;
            $sdf['total_amount'] = $money;
            $sdf['cur_money'] = $money;
            $sdf['merge_payment_id'] = $merge_payment_id;
			$sdf['payment_id'] = $merge_payment_id;
            $rel_order = array('rel_id'=>$merge_payment_id,'bill_type'=>'payments','pay_object'=>'order','bill_id'=>$sdf['merge_payment_id'],'money'=>$money);
            $sdf['orders']['0'] = $rel_order;
			$sdf['order_ids']=implode('，',$order_ids);
            $is_payed = $objPay->all_dopay($sdf, $this, $msg);
            
            if ($is_save_prepaid && $is_payed)
            {
                $obj_prepaid->update_charge_log($sdf);
            }

          

            if ($sdf['pay_app_id'] == 'deposit')
            {
                // 预存款支付
                if (isset($arrMember['member_id']) && $arrMember['member_id']){
                    $this->begin(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders'));
                }else{
                    $this->begin(array('app'=>'b2c','ctl'=>'site_order','act'=>'index', 'arg0'=>$sdf['order_id']));
                }
                if ($is_payed) {
                    //$this->end(true,  app::get('b2c')->_('预存款支付成功！'), array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$sdf['merge_payment_id']));
                    $this->redirect(array('app'  => 'b2c',
                                          'ctl'  => 'site_paycenter',
                                          'act'  => 'result',
                                          'arg0' => $sdf['merge_payment_id']
                    ));
                }else{
                    $this->end(false, $msg);
                }

            }elseif($sdf['pay_app_id'] == 'sfscpay'){

                if (isset($arrMember['member_id']) && $arrMember['member_id']) {
                    $this->begin(array(
                        'app' => 'b2c',
                        'ctl' => 'site_member',
                        'act' => 'orders'
                    ));
                }else {
                    $this->begin(array('app'  => 'b2c',
                                       'ctl'  => 'site_order',
                                       'act'  => 'index',
                                       'arg0' => $sdf['order_id']
                    ));
                }
                if ($is_payed){
                    $this->redirect(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$sdf['merge_payment_id']));
                    //$this->end(true,  app::get('b2c')->_('福点支付成功！'), array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result', 'arg0'=>$sdf['merge_payment_id']));
                }else{
                    $this->end(false, $msg);
                }
            }
        }
    }
}
