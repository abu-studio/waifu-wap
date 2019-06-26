<?php
 

class sand_ctl_site_sand extends b2c_ctl_site_member{

    var $seoTag=array('shopname','sand');

    function __construct($app){
		$app = app::get('b2c');
        parent::__construct($app);
        $shopname = $app->getConf('system.shopname');
        $this->shopname = $shopname;
        if(isset($shopname)){
            $this->title = app::get('b2c')->_('杉德生活').'_'.$shopname;
            $this->keywords = app::get('b2c')->_('杉德生活').'_'.$shopname;
            $this->description = app::get('b2c')->_('杉德生活').'_'.$shopname;
        }

    }

	function recharge(){
		//判断商社号是否在列表中
		$minfo=$this->get_current_member();
		$acount_object = app::get('pam')->model('account');
		$account_data = $acount_object->getRow('company_no',array('account_id'=>$minfo['member_id']));
		$companys = app::get('site')->getConf('sand.company') ? app::get('site')->getConf('sand.company'): array();
		if(!in_array(strtoupper($account_data['company_no']),$companys)){
			$this->begin(array('app' => 'b2c','ctl' => 'site_lifecost', 'act'=>'index'));
            $this->end(false, app::get('b2c')->_('您无权限访问！'));
		}
		
		//获取我的福点余额
		$_sjson = array(
			'METHOD'=>'getBalanceInfoByRelationId',
			'RELATION_ID'=>$this->member['uname'],
			'QUERY_TIME'=>""
		);
		$post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
		$tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
		if($tmpdata != null && gettype($tmpdata) == "object"){
			$tmp22 = SFSC_HttpClient::objectToArray($tmpdata);
		}else{
			$tmp22['RESULT_DATA'] = array('INCOME'=>0,'SUM'=>0,'EXPENSES'=>0);
		}
		$this->pagedata['title']='积分充值';
		$this->pagedata['otitle'] = '提供杉德卡充值业务';
		$this->pagedata['my_fd'] = $tmp22['RESULT_DATA']['SUM']?$tmp22['RESULT_DATA']['SUM']:0;    //我的福点
         //当前登陆用户等级
        $siteMember = $this->get_current_member();
        $member_lv_id = $siteMember['member_lv'];
        $member_lv_object = app::get('b2c')->model('member_lv');
        $member_lv_data = $member_lv_object->dump(array('member_lv_id'=>$member_lv_id));
		$this->pagedata['CounterFee'] = $member_lv_data['commission']?$member_lv_data['commission']:0;

		//获取用户杉德卡开户状态
		$memberobj = app::get('b2c')->model('members');
		$memdata = $memberobj->getRow('sandopen,mobile',array('member_id'=>$siteMember['member_id']));
		$this->pagedata['memdata'] = $memdata;
		$this->ebappoutput();
	}
	
	//ajax 校验福点
	function chkfd(){
		$post = $_POST;
		$point = $post['point'];
		$money = $post['money'];
        $siteMember = $this->get_current_member();
        //当前登陆用户等级
        $member_lv_id = $siteMember['member_lv'];
        $member_lv_object = app::get('b2c')->model('member_lv');
        $member_lv_data = $member_lv_object->dump(array('member_lv_id'=>$member_lv_id));
		$CounterFee = $member_lv_data['commission']?$member_lv_data['commission']:0;
		$commission = ceil($CounterFee*$money/100);
		$payfd = $money+$commission;
		if($payfd>$point){
			$data['result'] = 'error';
		}else{
			$data['result'] = 'true';
		}
		echo  json_encode($data);
	}
	
	 public function sandOrderList(){
		$siteMember = $this->get_current_member();
		//将开户手机号加入members
		$phone = trim($_POST['mobile']);
		if($phone){
			$member_object = app::get('b2c')->model('members');
			$member_data['member_id'] = $siteMember['member_id'];
			$member_data['sandopen_mobile'] = $phone;
			$member_object->save($member_data);
		}

        $data['money'] = !empty($_POST['money'])?htmlspecialchars($_POST['money']):'0';
        //金额向上取整
        $data['money'] = ceil($data['money']);
        //获取手续费
        $member_lv_id = $siteMember['member_lv'];
        $member_lv_object = app::get('b2c')->model('member_lv');
        $member_lv_data = $member_lv_object->dump(array('member_lv_id'=>$member_lv_id));
		$commission = $member_lv_data['commission']?$member_lv_data['commission']:0;
        $data['commission'] = ceil($commission/100*$data['money']);
        //实付金额
        $data['pay_money'] = $data['money'] + $data['commission'];
        $this->pagedata['data']=$data;
        $this->pagedata['_PAGE_']='check_sandOrder.html';
        $this->pagedata['title']='杉德支付单-订单确认';
        $this->ebappoutput();
    }

	public function utilitypay(){
        
        //后台配置的 产品信息
        //判断参数是否齐全

        $obj_filter = kernel::single('b2c_site_filter');

        $this->begin(array('app'=>'sand','ctl'=>'site_sand','act'=>'utilitypay'));
        if(trim(trim($obj_filter->check_input($_POST['order_amount'])) == "")){
            $this->end(false, "生成订单失败！", "",true,true);
            die();
        }

		$goods_id = app::get('site')->getConf('sand.product') ? app::get('site')->getConf('sand.product'): '';
        $product_objects =  kernel::single("b2c_mdl_products");
        $product_data = $product_objects->dump(array('goods_id'=>$goods_id),"*");
        $goods = array(
             'goods' => Array (
                    'num' => "1",
                    'goods_id' => $goods_id,
                    'pmt_id' => "",
                    'product_id' =>$product_data['product_id']
             )
        );

        //模拟加入购物车
        $aCart = array();
        kernel::single('fastbuy_cart_fastbuy_goods')->get_shuidianmei_arr(
            $goods,
            array(),
            $aCart
        );

        //加入订单
        $msg = "";
        $post_array = array(
            'purchase'=>array(
                'member_id'=>$this->member['member_id'] ? $this->member['member_id'] : 0,
            ),
            'payment'=>array(
                'currency'=>'CNY',
                'pay_app_id'=>'sfscpay',
                'dis_point'=>'0'
            ),
            'sand'=>true
        );
        //事物处理开始
        $db = kernel::database();
        $transaction_status = $db->beginTransaction();

        $order = &$this->app->model('orders');
        $post_array['order_id'] = $order_id = $order->gen_id();
        $post_array['member_id'] = $this->member['member_id'] ? $this->member['member_id'] : 0;
        $obj_order_create = kernel::single("b2c_order_create");
		
		
        $order_data = $obj_order_create->generate($post_array,'',$msg,$aCart);
        $order_data['total_amount'] = $obj_filter->check_input($_POST['order_amount']);
        $order_data['cur_amount'] = $obj_filter->check_input($_POST['order_amount']);
        $order_data['store_id'] = $aCart['object']['goods'][0]['store_id'];
        $result = $obj_order_create->save($order_data, $msg);

        
        if ($result){
            // 发票高级配置埋点
            foreach( kernel::servicelist('invoice_setting') as $services ) {
                if ( is_object($services) ) {
                    if ( method_exists($services, 'saveInvoiceData') ) {
                        $services->saveInvoiceData($post_array['order_id'],$post_array['payment']);
                    }
                }
            }

            
       
			

            // 取到日志模块
            if ($this->member['member_id']['member_id'])
            {
                $obj_members = $this->app->model('members');
                $arrPams = $obj_members->dump($this->member['member_id'], '*', array(':account@pam' => array('*')));
            }

            $obj_order_create = kernel::single("b2c_order_remark");
            $arr_remark = array(
                'order_bn' => $order_id,
                'mark_text' => $post_array['memo'],
                'op_name' => (!$this->member['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                'mark_type' => 'b0',
            );

            $log_text = "";
			$log_text[] = array(
						'txt_key'=>'订单创建成功！费用类型：杉德卡充值',
						'data'=>array(),
					);
			$log_text = serialize($log_text);
			

			
            $orderLog = $this->app->model("order_log");
            $sdf_order_log = array(
                'rel_id' => $order_id,
                'op_id' => $this->member['member_id'],
                'op_name' => (!$this->member['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'creates',
                'result' => 'SUCCESS',
                'log_text' => $log_text,
            );

            $log_id = $orderLog->save($sdf_order_log);


        }else{
            $db->rollback();
        }
        $db->commit($transaction_status);

        if($result){
            $this->end(true, "订单生成成功！", $this->gen_url(array('app'=>'sand','ctl'=>'site_sand','act'=>'sandPayments','args'=>array($post_array['order_id'],false,true))),'',true);
        }else{
            $this->end(false, $msg, "",true,true);
        }


    }
	
	
	/**
     * 杉德付订单提交页面
     * @params string order id
     */
    public function sandPayments($order_id,$selecttype=false,$from=false)
    {
        $objOrder = &$this->app->model('orders');
        $sdf = $objOrder->dump($order_id);
        $objMath = kernel::single("ectools_math");
        if(!$sdf){
            exit;
        }
        $sdf['total'] = $sdf['cur_amount'];
        $sdf['cur_amount'] = $objMath->number_minus(array($sdf['cur_amount'], $sdf['payed']));
        $sdf['total_amount'] = $objMath->number_div(array($sdf['cur_amount'], $sdf['cur_rate']));

        $this->pagedata['order'] = $sdf;
        // 货到付款不能进入此页面
        if ($sdf['payinfo']['pay_app_id'] == '-1')
        {
            $this->begin(array('app' => 'b2c','ctl' => 'site_member', 'act'=>'orderdetail', 'arg0'=>$order_id));
            $this->end(false, app::get('b2c')->_('配送方式只支持货到付款'));
        }

        if($selecttype){
            $selecttype = 1;
        }else{
            $selecttype = 0;
        }

        $this->pagedata['order']['selecttype'] = $selecttype;
        $opayment = app::get('ectools')->model('payment_cfgs');
        $this->pagedata['payments'] = $opayment->getListByCode($sdf['currency']);

        $system_money_decimals = $this->app->getConf('system.money.decimals');
        $system_money_operation_carryset = $this->app->getConf('system.money.operation.carryset');
        foreach ($this->pagedata['payments'] as $key=>&$arrPayments)
        {
            if (!$sdf['member_id'])
            {
                if (trim($arrPayments['app_id']) == 'deposit')
                {
                    unset($this->pagedata['payments'][$key]);
                    continue;
                }
            }

            if ($arrPayments['app_id'] == $this->pagedata['order']['payinfo']['pay_app_id'])
            {
                $arrPayments['cur_money'] = $objMath->formatNumber($this->pagedata['order']['cur_amount'], $system_money_decimals, $system_money_operation_carryset);
                $arrPayments['total_amount'] = $objMath->formatNumber($this->pagedata['order']['total_amount'], $system_money_decimals, $system_money_operation_carryset);
            }
            else
            {
                $arrPayments['cur_money'] = $this->pagedata['order']['cur_amount'];
                $cur_discount = $objMath->number_multiple(array($sdf['discount'], $this->pagedata['order']['cur_rate']));
                if ($this->pagedata['order']['payinfo']['cost_payment'] > 0)
                {
                    if ($this->pagedata['order']['cur_amount'] > 0)
                        $cost_payments_rate = $objMath->number_div(array($arrPayments['cur_money'], $objMath->number_plus(array($this->pagedata['order']['cur_amount'], $this->pagedata['order']['payed']))));
                    else
                        $cost_payments_rate = 0;
                    $cost_payment = $objMath->number_multiple(array($objMath->number_multiple(array($this->pagedata['order']['payinfo']['cost_payment'], $this->pagedata['order']['cur_rate'])), $cost_payments_rate));
                    $arrPayments['cur_money'] = $objMath->number_minus(array($arrPayments['cur_money'], $cur_discount));
                    $arrPayments['cur_money'] = $objMath->number_minus(array($arrPayments['cur_money'], $cost_payment));
                    $arrPayments['cur_money'] = $objMath->number_plus(array($arrPayments['cur_money'], $objMath->number_multiple(array($arrPayments['cur_money'], $arrPayments['pay_fee']))));
                    $arrPayments['cur_money'] = $objMath->number_plus(array($arrPayments['cur_money'], $cur_discount));
                }
                else
                {
                    $arrPayments['cur_money'] = $objMath->number_minus(array($arrPayments['cur_money'], $cur_discount));
                    $cost_payment = $objMath->number_multiple(array($arrPayments['cur_money'], $arrPayments['pay_fee']));
                    $arrPayments['cur_money'] = $objMath->number_plus(array($arrPayments['cur_money'], $cost_payment));
                    $arrPayments['cur_money'] = $objMath->number_plus(array($arrPayments['cur_money'], $cur_discount));
                }

                $arrPayments['total_amount'] = $objMath->formatNumber($objMath->number_div(array($arrPayments['cur_money'], $this->pagedata['order']['cur_rate'])), $system_money_decimals, $system_money_operation_carryset);
                $arrPayments['cur_money'] = $objMath->formatNumber($arrPayments['cur_money'], $system_money_decimals, $system_money_operation_carryset);
            }
        }

        $objCur = app::get('ectools')->model('currency');
        $aCur = $objCur->getFormat($this->pagedata['order']['currency']);
        $this->pagedata['order']['cur_def'] = $aCur['sign'];

        $this->pagedata['return_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'result'));
        $this->pagedata['res_url'] = $this->app->res_url;
        $this->pagedata['form_action'] = $this->gen_url(array('app'=>'b2c','ctl'=>'site_paycenter','act'=>'dopayment','arg0'=>'order'));
        $obj_order_payment_html = kernel::servicelist('b2c.order.pay_html');
        $app_id = 'b2c';
        if ($obj_order_payment_html)
        {
            foreach ($obj_order_payment_html as $obj)
            {
                $obj->gen_data($this, $app_id);
            }
        }
		/*
        if ($sdf['cur_amount'] == '0' && $sdf['pay_status'] == '0')
        {
            // 模拟支付流程
            $objPay = kernel::single("ectools_pay");
            $sdffds = array(
                'payment_id' => $objPay->get_payment_id(),
                'order_id' => $sdf['order_id'],
                'rel_id' => $sdf['order_id'],
                'op_id' => $sdf['member_id'],
                'pay_app_id' => $sdf['payinfo']['pay_app_id'],
                'currency' => $sdf['currency'],
                'payinfo' => array(
                    'cost_payment' => $sdf['payinfo']['cost_payment'],
                ),
                'pay_object' => 'order',
                'member_id' => $sdf['member_id'],
                'op_name' => $this->user->user_data['account']['login_name'],
                'status' => 'ready',
                'cur_money' => $sdf['cur_amount'],
                'money' => $sdf['total_amount'],
            );
            $is_payed = $objPay->gopay($sdffds, $msg);
            if (!$is_payed){
                $msg = app::get('b2c')->_('订单自动支付失败！');
                $this->end(false, $msg, $this->gen_url(array('app'=>'b2c','ctl'=>'site_cart','act'=>'checkout')));
            }

            $obj_pay_lists = kernel::servicelist("order.pay_finish");
            $is_payed = false;
            foreach ($obj_pay_lists as $order_pay_service_object)
            {
                $is_payed = $order_pay_service_object->order_pay_finish($sdffds, 'succ', 'font',$msg);
            }
        }
		
		*/
		
        //begin 获取银行信息
        $_sjson = array(
            'METHOD'=>'getBalanceInfoByRelationId',
            'RELATION_ID'=>$this->member['uname'],
            'QUERY_TIME'=>""
        );
        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($_sjson));
        $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        if($tmpdata != null && gettype($tmpdata) == "object"){
            $tmp22 = SFSC_HttpClient::objectToArray($tmpdata);
        }else{
            $tmp22['RESULT_DATA'] = array('INCOME'=>0,'SUM'=>0,'EXPENSES'=>0);
        }

        $this->pagedata['sfsc_balance']=$tmp22['RESULT_DATA'];
        $this->pagedata['_PAGE_']='sandPayments.html';
        $this->pagedata['title']='付账单-订单确认';
        $this->ebappoutput();

    }
	
	function mysand(){
				
	}
	

	
    
}
