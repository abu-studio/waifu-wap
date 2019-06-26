<?php
 

class b2c_ctl_site_lifecost extends b2c_ctl_site_member{

    var $seoTag=array('shopname','lifecost');

    function __construct($app){
        parent::__construct($app);
        $shopname = $app->getConf('system.shopname');
        $this->shopname = $shopname;
        if(isset($shopname)){
            $this->title = app::get('b2c')->_('生活缴费').'_'.$shopname;
            $this->keywords = app::get('b2c')->_('生活缴费').'_'.$shopname;
            $this->description = app::get('b2c')->_('生活缴费-水电煤').'_'.$shopname;
        }

    }
    
    
    public function sdmOrderList(){
    	$sdmCompany = !empty($_POST['sdmCompany'])?htmlspecialchars($_POST['sdmCompany']):'';	//ORGANIZATION_CODE
    	$sdmMoney = !empty($_POST['sdmMoney'])?htmlspecialchars($_POST['sdmMoney']):'';	//MONEY
    	$sdmChareNum = !empty($_POST['sdmChareNum'])?htmlspecialchars($_POST['sdmChareNum']):'';	//BARCODE
    	$itemName = !empty($_POST['itemName'])?htmlspecialchars($_POST['itemName']):'';	//ITEM_NAME
    	//获取接口数据
    	$params['METHOD'] = 'getUtilityOrder';
        $params['BARCODE'] = $sdmChareNum;
        $params['MONEY'] = $sdmMoney;
        $params['ORGANIZATION_CODE'] = $sdmCompany;
        $params['ITEM_NAME'] = strtoupper($itemName);
		$params['HUMBAS_NO'] = $this->member['uname'];
		$params['COMPANY_NO'] = '0000';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'UtilityService');
		if(!$resSF){
			//错误处理
		}
		$this->pagedata['data'] = $resSF['RESULT_DATA'];
		$this->pagedata['RESULT_CODE'] = $resSF['RESULT_CODE'];

		//获取用户提交过来的参数
		$sdmMoney = !empty($_POST['sdmMoney'])?htmlspecialchars($_POST['sdmMoney']):'';
		$this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('水电煤'),'link'=>'#');
		$this->pagedata['_PAGE_']='lifecost_getcost.html';
		$this->pagedata['title']='付账单-订单确认';
		//水电煤缴费规则
		$type=$_POST['itemName'];
		$kv_obj=kernel::single('base_mdl_kvstore');
    	switch ($type){
    		case 'water':
				$water_rules=$kv_obj->getList('*',array('key'=>'water.rules.set'));
				$this->pagedata['water_rules'] = $water_rules[0];
    			break;
    		case 'electricity':
				$electricity_rules=$kv_obj->getList('*',array('key'=>'electricity.rules.set'));
				$this->pagedata['electricity_rules'] = $electricity_rules[0];
    			break;
    		case 'gas':
				$gas_rules=$kv_obj->getList('*',array('key'=>'gas.rules.set'));
				$this->pagedata['gas_rules'] = $gas_rules[0];
    			break;
    	}
		$this->pagedata['type']=$type;
		
		$this->ebappoutput();
	}
	
    
	/**
	 * 校验水电煤数据是否合格
	 * 2015/7/17
	 */
    public function inValidateBtn(){
    	$BARCODE = !empty($_POST['BARCODE'])?htmlspecialchars($_POST['BARCODE']):'';
    	$MONEY = !empty($_POST['MONEY'])?htmlspecialchars($_POST['MONEY']):'';
    	$ORGANIZATION_CODE = !empty($_POST['ORGANIZATION_CODE'])?htmlspecialchars($_POST['ORGANIZATION_CODE']):'';
    	$ITEM_NAME = !empty($_POST['ITEM_NAME'])?htmlspecialchars($_POST['ITEM_NAME']):'';
    	$ITEM_NAME = strtoupper($ITEM_NAME);
    	//获取省
		$params['METHOD'] = 'checkBarcode';
        $params['BARCODE'] = $BARCODE;
        $params['MONEY'] = $MONEY;
        $params['ORGANIZATION_CODE'] = $ORGANIZATION_CODE;
        $params['ITEM_NAME'] = $ITEM_NAME;
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'UtilityService');
        $resSF_tmp = SFSC_HttpClient::objectToArray($resSF);
		if($resSF_tmp['RESULT_CODE'] != '10001'){
			echo 'error';
			die;
		}else{
			//跳转到订单列表页面
			//$this->redirect(array('app'=>'b2c','ctl'=>'site_cost','act'=>'getcost'));
			echo 'ok';
			die;
		}
		
    }
    
    /**
     * 水电煤
     */
    public function index(){
    	//获取参数
    	$type  = $this->_request->get_param(0);
        //根据类型显示相关的充值项目
    	$otitle = '';
		//xhk 2015/11/05 获取后台配置缴费规则
		$kv_obj=kernel::single('base_mdl_kvstore');
    	switch ($type){
    		case 'water':
    			$otitle = '缴水费';
				$water_rules=$kv_obj->getList('*',array('key'=>'water.rules.set'));
				$this->pagedata['water_rules'] = $water_rules[0];
				$water_guide=$kv_obj->getList('*',array('key'=>'water.guide.set'));
				$image_value=$water_guide[0]['value'];
				$this->pagedata['image_src'] = $image_value;
    			break;
    		case 'electricity':
    			$otitle = '缴电费';
				$electricity_rules=$kv_obj->getList('*',array('key'=>'electricity.rules.set'));
				$this->pagedata['electricity_rules'] = $electricity_rules[0];
				$electricity_guide=$kv_obj->getList('*',array('key'=>'electricity.guide.set'));
				$image_value=$electricity_guide[0]['value'];
				$this->pagedata['image_src'] = $image_value;
    			break;
    		case 'gas':
    			$otitle = '缴燃气费';
				$gas_rules=$kv_obj->getList('*',array('key'=>'gas.rules.set'));
				$this->pagedata['gas_rules'] = $gas_rules[0];
				$gas_guide=$kv_obj->getList('*',array('key'=>'gas.guide.set'));
				$image_value=$gas_guide[0]['value'];
				$this->pagedata['image_src'] = $image_value;
    			break;
    		default:
    			$otitle = '缴水费';
    			$type = 'water';
				$water_rules=$kv_obj->getList('*',array('key'=>'water.rules.set'));
				$this->pagedata['water_rules'] = $water_rules[0];
				$water_guide=$kv_obj->getList('*',array('key'=>'water.guide.set'));
				$image_value=$water_guide[0]['value'];
				$this->pagedata['image_src'] = $image_value;
    			break;
    	}
		$this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('水电煤'),'link'=>'#');
		$this->pagedata['_PAGE_']='lifecost.html';
		$this->pagedata['title']='付账单';
		$this->pagedata['otitle'] = $otitle;
		$this->pagedata['type'] = $type;
		//获取省
		$params['METHOD'] = 'getPayProvince';
		$utype = strtoupper($type);
        $params['ITEM_NAME'] = $utype;
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'UtilityService');
		if(!$resSF['RESULT_CODE']){
			//如果出现错给出提示
		}
		
		//保存省的信息
		$this->pagedata['provinceList'] = $resSF['RESULT_DATA'];
		unset($params);
		
        $this->ebappoutput();
    }
	
	/**
	 * 获取相关城市，省，区
	 * @date 2015/7/17
	 */
	public function getAreaList(){
		$id = !empty($_POST['id'])?htmlspecialchars($_POST['id']):'';
		$type = !empty($_POST['type'])?htmlspecialchars($_POST['type']):'';
		$itemName = !empty($_POST['itemName'])?htmlspecialchars($_POST['itemName']):'';
		
		//参数校验
		if(!$id){
			echo 'error';
			die;
		}
		if($type==1){
			$params['METHOD'] = 'getPayCity';
			$uitemName = strtoupper($itemName);
	        $params['ITEM_NAME'] = $uitemName;
	        $params['PROVINCE_CODE'] = $id;
			$resSF = SFSC_HttpClient::doLifCostMain($params, 'UtilityService');
	    	if(!$resSF['RESULT_CODE']){
				//如果出现错给出提示
				echo 'error';
				die;
			}
			
			$strHtml = "";
			$strHtml .= "<select class='sdm_select' id='ctid' onChange='getAreaList(this, 2);'>";
			$strHtml .= "<option value='0'>请选择城市</option>";
			foreach ($resSF['RESULT_DATA'] as $k=>$v){
				$strHtml .= "<option value='".$v['CITY_CODE']."'>".$v['CITY_NAME']."</option>";
			}
			$strHtml .= "</select>";
			echo $strHtml;
			die;
		}else if($type==2){
			//获取 付费机构 信息
			$params['METHOD'] = 'getPayOrganization';
			$uitemName = strtoupper($itemName);
	        $params['ITEM_NAME'] = $uitemName;
	        $params['CITY_CODE'] = $id;
			$resSF = SFSC_HttpClient::doLifCostMain($params, 'UtilityService');
	    	if(!$resSF['RESULT_CODE']){
				//如果出现错给出提示
			}
			
			$strHtml = "";
			$strHtml .= "<select class='sdm_select_l' id='sdmCompany' name='sdmCompany'>";
			$strHtml .= "<option value='0'>请选择出账机构</option>";
			foreach ($resSF['RESULT_DATA'] as $k=>$v){
				$strHtml .= "<option value='".$v['ORGANIZATION_CODE']."' lang='".$v['BARCODE_LENGTH']."'>".$v['ORGANIZATION_NAME']."</option>";
			}
			$strHtml .= "</select>";
			echo $strHtml;
			die;
		}
		
		
		
	}

    public function utilitypay(){
        //机构编号, 机构名称 , 机构类型, 付费方式 ,付费id , java订单号，账期
        //后台配置的 产品信息
        //判断参数是否齐全

        $obj_filter = kernel::single('b2c_site_filter');

        $this->begin(array('app'=>'b2c','ctl'=>'site_lifecost','act'=>'utilitypay'));

        if(trim($obj_filter->check_input($_POST['order_id'])) == "" || trim($obj_filter->check_input($_POST['order_amount'])) == "" || trim($obj_filter->check_input($_POST['organization_code'])) == ""){
            $this->end(false, "生成订单失败！", "",true,true);
            die();
        }

        $kv_obj = kernel::single('base_mdl_kvstore');
        $jiaofei = $kv_obj->dump(array('key'=>'jiaofei.store.set'),'value');
        $product_objects =  kernel::single("b2c_mdl_products");
        $product_data = $product_objects->dump(array('goods_id'=>$jiaofei['value']),"*");
        $goods = array(
             'goods' => Array (
                    'num' => "1",
                    'goods_id' => $jiaofei['value'],
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
            'ebapp'=>true
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

        //调用java 订单接口 将生成好的订单给java系统
        if($result){

            $_sjson = array(
                'METHOD'=>'getUtilityOrder',
                'ORDER_ID'=>$obj_filter->check_input($_POST['order_id']),
                'MALL_ORDER_ID'=>$order_data['order_id'],
                'HUMBAS_NO'=>$this->member['uname'],
                'BARCODE'=>$obj_filter->check_input($_POST['barcode']),
                'MONEY'=>$obj_filter->check_input($_POST['order_amount']),
                'ORGANIZATION_CODE'=>$obj_filter->check_input($_POST['organization_code']),
                'ITEM_NAME'=>'',
                'COMPANY_NO'=>'',
				'SEC'=>'true'
            );

            $post_data = array('serviceNo'=>'UtilityService',"inputParam"=>json_encode($_sjson));
            $pageContents = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);
            $pageContents_tmp = SFSC_HttpClient::objectToArray($pageContents);

            //paycost
            if($pageContents_tmp['RESULT_CODE'] == "I03103"){
                $db->rollback();
                $this->end(false, "支付单重复！", "",true,true);
            }

        }else{
            $db->rollback();
        }



        if ($result){
            // 发票高级配置埋点
            foreach( kernel::servicelist('invoice_setting') as $services ) {
                if ( is_object($services) ) {
                    if ( method_exists($services, 'saveInvoiceData') ) {
                        $services->saveInvoiceData($post_array['order_id'],$post_array['payment']);
                    }
                }
            }


            //水电煤模块需要增加的表字段信息
            $ebapporder_object = kernel::single("b2c_mdl_ebapporder");
			if(strlen($_POST['barcode'])>12){
				$check_ebapporder = $ebapporder_object->dump(array("barcode"=>$obj_filter->check_input($_POST['barcode']),"organization_type"=>$obj_filter->check_input($_POST['organization_type'])),"*");
				if(!empty($check_ebapporder)){
                $db->rollback();
                $this->end(false, "商城订单已存在,请直接支付！", "",true,true);
				}
			}
            $ebapporder_data = array(
                'order_id'=>$post_array['order_id'],
                'organization_code'=>$obj_filter->check_input($_POST['organization_code']),
                'organization_name'=>$obj_filter->check_input($_POST['organization_name']),
                'barcode'=>$obj_filter->check_input($_POST['barcode']),
                'ebapporder_id'=>$obj_filter->check_input($_POST['order_id']),
                'organization_type'=>$obj_filter->check_input($_POST['organization_type']),
            );
            $flag = $ebapporder_object->save($ebapporder_data);

			$barcode = $ebapporder_data['barcode'];
			$fee_type = $ebapporder_data['organization_type'];
			switch ($fee_type) {
				case 'WATER':
				$fee_type = '水费';break;
				case 'ELECTRICITY':
				$fee_type = '电费';break;
				case 'GAS':
				$fee_type = '燃气费';break;
				case 'PHONE':
				$fee_type = '话费';
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
            if($result){
				if(in_array($ebapporder_data['organization_type'],array('WATER','ELECTRICITY','GAS'))){
					$log_text[] = array(
						'txt_key'=>'订单创建成功！费用类型：'.$fee_type.'，条码值：'.$barcode,
						'data'=>array(),
					);
					$data_memo = array('memo'=>'订单创建成功！费用类型：'.$fee_type.'，条码值：'.$barcode);
					$order->update($data_memo,array('order_id'=>$order_id));
				}else{
					if(in_array($ebapporder_data['organization_type'],array('PHONE'))){
					$log_text[] = array(
						'txt_key'=>'订单创建成功！费用类型：'.$fee_type.'，手机号码：'.$barcode,
						'data'=>array(),
					);
					$data_memo = array('memo'=>'订单创建成功！费用类型：'.$fee_type.'，手机号码：'.$barcode);
					$order->update($data_memo,array('order_id'=>$order_id));
					}else{
						$log_text[] = array(
						'txt_key'=>'订单创建成功！',
						'data'=>array(),
						);
					}
				}
                $log_text = serialize($log_text);
            }else{
                $log_text[] = array(
                    'txt_key'=>'订单创建失败！',
                    'data'=>array(),
                );
                $log_text = serialize($log_text);
            }
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
            $this->end(true, "订单生成成功！", $this->gen_url(array('app'=>'b2c','ctl'=>'site_lifecost','act'=>'ebappPayments','args'=>array($post_array['order_id'],false,true))),'',true);
        }else{
            $this->end(false, $msg, "",true,true);
        }


    }





    /**
     * 水电煤支付订单提交页面
     * @params string order id
     * @params boolean 支付方式的选择
     */
    public function ebappPayments($order_id,$selecttype=false,$from=false)
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

        //begin 获取银行信息
        $bankInfo = kernel::single('b2c_banks_info')->getBank();
        $this->pagedata['bankinfo'] = $bankInfo;


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

        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'site_lifecost', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('水电煤'),'link'=>'#');
        $this->pagedata['_PAGE_']='ebappPayments.html';
        $this->pagedata['title']='付账单-订单确认';
        $this->ebappoutput();

    }
	
	
    
}
