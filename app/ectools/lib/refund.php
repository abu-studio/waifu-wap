<?php

 
/**
 * 电商退款统一处理入口
 * 
 * @version 0.1
 * @package ectools.lib
 */
class ectools_refund extends ectools_operation
{    
    /**
     * 私有构造方法，不能直接实例化，只能通过调用getInstance静态方法被构造
     * @params object 当前应用的app对象
     * @return null
     */
    public function __construct($app)
    {        
        $this->app = $app;
    }

    /**
     * 最终的克隆方法，禁止克隆本类实例，克隆是抛出异常。
     * @params null
     * @return null
     */
    final public function __clone()
    {
        trigger_error(app::get('ectools')->_("此类对象不能被克隆！"), E_USER_ERROR);
    }
    
    /**
     * 创建退款单
     * @params array - 订单数据
     * @params obj - 应用对象
     * @params string - 支付单生成的记录
     * @return boolean - 创建成功与否
     */
    public function generate(&$sdf, &$controller=null, &$msg='')
    {
         // 异常处理    
        if (!isset($sdf) || !$sdf || !is_array($sdf))
        {
            trigger_error(app::get('ectools')->_("退款单信息不能为空！"), E_USER_ERROR);exit;
        }

        $is_save = false;

		//为退款单结算单增加店铺id add by luf
		$obj_orders = app::get('b2c')->model('orders');
		$stores = $obj_orders->dump($sdf['order_id'],'store_id');
		$sdf['store_id'] = $stores['store_id'];
		//end
        
        // 保存的接口方法        
        $obj_refund_create = kernel::single("ectools_refund_create");
        $is_save = $obj_refund_create->generate($sdf);
        
        if (!$is_save)
        {
            $msg = app::get('ectools')->_('退款单生成失败！');
            return false;
        }
		
		/*$obj_refund_special = kernel::servicelist('ectools_refund.ectools_mdl_special_refund');
		foreach ($obj_refund_special as $obj_app)
		{
			$is_save = $obj_app->dorefund($sdf);
		}
        
		if ($is_save)
		{
			$obj_api_refund = kernel::single("ectools_refund_update");
			$sdf['status'] = 'succ';
			$is_save = $obj_api_refund->generate($sdf);
			
			if (!$is_save)
			{
				$msg = app::get('ectools')->_('退款单编辑失败！');
				return false;
			}
		}*/
        
        return $is_save;
    }

    public function generate_after($sdf)
    {
		$obj_refund_special = kernel::servicelist('ectools_refund.ectools_mdl_special_refund');
		foreach ($obj_refund_special as $obj_app)
		{
			$is_save = $obj_app->dorefund($sdf);
		}

        $obj_api_refund = kernel::single("ectools_refund_update");
        $sdf['status'] = 'succ';
        $sdf['t_payed'] = time();
        $is_save = $obj_api_refund->generate($sdf);

		//添加更新支付单
		$obj_payment = $this->app->model('payments');
		$pay_data = array();
		$pay_data['refund_money'] = $sdf['cur_money'];
		if($sdf['cur_money'] == $sdf['payment_info']['cur_money']){
			$pay_data['refund_status'] = '3';
		}else{
			$pay_data['refund_status'] = '2';
		}

		$obj_payment->update($pay_data,array('payment_id'=>$sdf['payment_info']['payment_id']));
        
        if (!$is_save)
        {
            $msg = app::get('ectools')->_('退款单编辑失败！');
            return false;
        }
        
        return $is_save;
    }

    public function dorefund($sdf,$controller)
    {
		//组合支付（全额退款）：福点部分线上进行
		$order = app::get('b2c')->model("orders")->dump($sdf['order_id']);
		if($sdf['pay_app_id']=='sfscpay'&&strpos($order['payinfo']['pay_app_id'],'sfscpay')!==false&&strpos($order['payinfo']['pay_app_id'],',')!==false){
			$sfsc_refund = false;
			//添加退款接口   10001 成功 10000 失败
			$sdf['METHOD'] = "pointCancel";
			$post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
			$tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
			$tmpdata = SFSC_HttpClient::objectToArray($tmpdata);
			if(empty($tmpdata) && !is_numeric($tmpdata['RESULT_CODE'])){
				//$db->rollback();
			}elseif($tmpdata['RESULT_CODE'] == 10000){
				//$db->rollback();
			}elseif($tmpdata['RESULT_CODE'] == 10001){
				$sfsc_refund = true;
				//$db->commit($transaction_status);
			}else{
				//$db->rollback();
			}
			if($sfsc_refund){
				kernel::single('ectools_mdl_refunds')->update(array('status'=>'succ'),array('refund_id'=>$sdf['refund_id']));
				return "组合支付福点部分已完成退款";
			}
		}
		$str_app = "";
        $pay_app_id = ($sdf['pay_app_id']) ? $sdf['pay_app_id'] : $sdf['pay_type'];
        $obj_app_plugins = kernel::servicelist("ectools_refund.ectools_mdl_refund_cfgs");
        foreach ($obj_app_plugins as $obj_app)
        {
            $app_class_name = get_class($obj_app);
            $arr_class_name = explode('_', $app_class_name);
            if (isset($arr_class_name[count($arr_class_name)-1]) && $arr_class_name[count($arr_class_name)-1])
            {
                if ($arr_class_name[count($arr_class_name)-1] == $pay_app_id)
                {
                    $pay_app_ins = $obj_app;
                    $str_app = $app_class_name;
                }
            }
			else
			{
				if ($app_class_name == $pay_app_id)
				{
					$pay_app_ins = $obj_app;
					$str_app = $app_class_name;
				}
			}
        }
        
		
		if(!empty($str_app)){
			$pay_app_ins = new $str_app($controller->app);
			if ($sdf['pay_type']=='online')
			{
				$is_refund = $pay_app_ins->dorefund($sdf); 
				return $is_refund;
			}else{

			}

			return "success";
		}else{
			return "未接入退款接口";
		}
    }

    public function callback($sdf,$result,$type="")
    {
        $obj_refunds = kernel::single("ectools_refund");
        $refunds = $this->app->model('refunds');
		if($type=="server"){
            if($sdf['status'] == 'ready'){
                if($result == 'success'){
                    $ref_rs = $this->generate_after($sdf);
                }else{
                    $refunds->update(array('memo'=>$result),array('refund_id'=>$sdf['refund_id']));
                }
            }
        }else{
            if($sdf['status'] == 'ready'){
                if($result == 'success'){
                    $ref_rs = $this->generate_after($sdf);
                }else{
                    $refunds->update(array('memo'=>$result),array('refund_id'=>$sdf['refund_id']));
                }
            }
        }

        return true;
    }

	//预存款退款方法
	public function refund_advance($refund_data,&$msg = ''){
		//预存款退款
		$objAdvance = app::get('b2c')->model("member_advance");
		$obj_bill = $this->app->model('order_bills');

		$rel_id = $obj_bill->dump(array('bill_id'=>$refund_data['refund_id']),'rel_id');
		$status = $objAdvance->add($refund_data['member_id'], $refund_data['cur_money'], app::get('b2c')->_('预存款退款'),$msg, $refund_data['refund_id'], $rel_id['rel_id'], 'deposit', app::get('b2c')->_('退还订单消费'));
		if($status){
			$ref_rs = $this->generate_after($refund_data);
			if($ref_rs){
				return true;
			}else{
				$msg .= '更新退款单失败！';
				return false;
			}
		}else{
			$msg .= '预存款退款失败！';
			return false;
		}
	}

	//分步付款的退款校验
	public function make_data($refund_data){
		$db = kernel::database();
        $transaction_status = $db->beginTransaction();
		$flag = true;
		$obj_bills = $this->app->model('order_bills');
		$obj_refund = $this->app->model('refunds');
		//获取订单号
		$rel_id = $obj_bills->dump(array('bill_id'=>$refund_data['refund_id']),'rel_id');
		//获取支付单
		$payments = $this->get_payments($rel_id['rel_id']);

		//支付单排序-提高福点支付单的优先级
		foreach($payments as $k=>$v){
			if($v['pay_app_id']=='sfscpay'){
				unset($payments[$k]);
				array_splice($payments,0,0,array($v));
			}
		}

		$data = array();
		//分次支付  判断退款金额大小
		foreach($payments as $key=>$val){
			if($refund_data['cur_money'] > $val['r_money']){
				//拆分退款单（更新原有退款单，生成新的退款单）					
				//组装数据
				$r_data = array('money'=>$val['r_money'],'cur_money'=>$val['r_money'],'memo'=>'多次支付拆分退款单');
				$b_data = array('money'=>$val['r_money']);
				//组合支付：退款方式以支付单为准-hy
				if(strpos($refund_data['pay_app_id'],'sfscpay')!==false&&strpos($refund_data['pay_app_id'],',')!==false){
					$arrPaymentInfo = $this->app->model('payment_cfgs')->getPaymentInfo($val['pay_app_id']);
					$r_data['pay_app_id'] = $val['pay_app_id'];
					$r_data['pay_account'] = $arrPaymentInfo['app_display_name'];
					$r_data['pay_name'] = $arrPaymentInfo['app_name'];
					$r_data['pay_ver'] = $arrPaymentInfo['app_version'];
				}
				$rs_r = $obj_refund->update($r_data,array('refund_id'=>$refund_data['refund_id']));
				$rs_b = $obj_bills->update($b_data,array('bill_id'=>$refund_data['refund_id']));
				
				if($rs_r && $rs_b){
					$ref_data = $obj_refund->dump(array('refund_id'=>$refund_data['refund_id']),'*');
					$ref_data['payment_info'] = $val;
					//添加订单号如数组
					$ref_data['order_id'] = $rel_id['rel_id'];
					$data[] = $ref_data;
					
					//修改refund_data数据
					$refund_data['money'] -= $val['r_money'];
					$refund_data['cur_money'] -= $val['r_money'];
					$refund_data['refund_id'] = $refund_id = $obj_refund->gen_id();
					$refund_data['order_id'] = $rel_id['rel_id'];
					$refund_data['payment'] = $refund_data['pay_app_id'];
					$refund_data['pay_object'] = 'order';
					$objPaymemtcfg = $this->app->model('payment_cfgs');
					$arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($refund_data['payment']);
					$refund_data['app_name'] = $arrPaymentInfo['app_name'];
					$refund_data['app_version'] = $arrPaymentInfo['app_version'];
					$refund_data['memo'] = '多次支付拆分退款单';
					//生成新的退款单
					$controller = kernel::single('aftersales_ctl_site_member',array('app'=>app::get('aftersales'),'arg1'=>false));
					$res = $this->generate($refund_data, $controller, $msg);
					if(!$res){
						$flag = false;
						break;
					}
				}else{
					$flag = false;
					break;
				}
			}else{
				//组合支付：退款方式以支付单为准-hy
				if(strpos($refund_data['pay_app_id'],'sfscpay')!==false&&strpos($refund_data['pay_app_id'],',')!==false){
					$arrPaymentInfo = $this->app->model('payment_cfgs')->getPaymentInfo($val['pay_app_id']);

					$r_data = array(
						'pay_app_id'=>$val['pay_app_id'],
						'pay_account'=>$arrPaymentInfo['app_display_name'],
						'pay_name'=>$arrPaymentInfo['app_name'],
						'pay_ver'=>$arrPaymentInfo['app_version']
					);
					$obj_refund->update($r_data,array('refund_id'=>$refund_data['refund_id']));
					$refund_data['pay_app_id'] = $val['pay_app_id'];
					$refund_data['pay_account'] = $arrPaymentInfo['app_display_name'];
					$refund_data['pay_name'] = $arrPaymentInfo['app_name'];
					$refund_data['pay_ver'] = $arrPaymentInfo['app_version'];
				}
				$refund_data['payment_info'] = $val;
				//添加订单号如数组
				$refund_data['order_id'] = $rel_id['rel_id'];
				$data[] = $refund_data;
				break;
			}
		}
		if($flag){
			$db->commit($transaction_status);
			return $data;
		}else{
			$db->rollback();
			return false;
		}
	}

	public function get_payments($rel_id){
		$obj = $this->app->model('order_bills');
        $sql = "SELECT a.*,(a.cur_money-a.refund_money) as r_money FROM " . kernel::database()->prefix .
        "ectools_payments as a LEFT JOIN " . kernel::database()->prefix .
        "ectools_order_bills as b on a.payment_id = b.bill_id WHERE b.bill_type = 'payments' AND b.rel_id = '" . $rel_id .
        "' AND a.status in ('succ','sfsc_freeze') AND a.refund_status!='3' ORDER BY r_money DESC";
        $bill = $obj->db->select($sql);
        
        return $bill;
	}
}

?>
