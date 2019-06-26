<?php
 

class b2c_ctl_site_mobilecost extends b2c_ctl_site_member{

    var $seoTag=array('shopname','mobilecost');

    function __construct($app){
        parent::__construct($app);
        $shopname = $app->getConf('system.shopname');
        $this->shopname = $shopname;
        if(isset($shopname)){
            $this->title = app::get('b2c')->_('生活缴费').'_'.$shopname;
            $this->keywords = app::get('b2c')->_('生活缴费').'_'.$shopname;
            $this->description = app::get('b2c')->_('生活缴费-手机充值').'_'.$shopname;
        }

    }
    /**
     * 手机充值订单列表
     * 2015/7/18
     */
    public function mobileOrderList(){
		if($_SESSION["mobile_flag"]){
    	$phone = !empty($_POST['phone'])?htmlspecialchars($_POST['phone']):'';	//PHONE
    	$money = !empty($_POST['money'])?htmlspecialchars($_POST['money']):'';	//MONEY
        $organization_code = !empty($_POST['organization_code'])?htmlspecialchars($_POST['organization_code']):'';	//organization_code
    	//获取接口数据
    	$params['METHOD'] = 'getUtilityOrder';

        $params['MONEY'] = $money;
        $params['BARCODE'] = $phone;
        //付费机构
        $params['ORGANIZATION_CODE'] = $organization_code;
        $params['ITEM_NAME'] = "PHONE";
        $params['HUMBAS_NO'] = $this->member['uname'];
        $params['COMPANY_NO'] = '0000';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'UtilityService');

		if(!$resSF){
			//错误处理
		}
		$this->pagedata['data'] = $resSF['RESULT_DATA'];
		//获取用户提交过来的参数
		$this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('生活缴费'),'link'=>'#');
		$this->pagedata['_PAGE_']='mobilecost_getcost.html';
		$this->pagedata['title']='付账单-订单确认';
		//获取后台配置缴费规则
		$kv_obj=kernel::single('base_mdl_kvstore');
		$recharge_rules=$kv_obj->getList('*',array('key'=>'recharge.rules.set'));
		$this->pagedata['recharge_rules'] = $recharge_rules[0];
		unset($_SESSION["mobile_flag"]);
		$this->ebappoutput();
		}else{
			$url = $this->gen_url( array('app'=>'b2c', 'ctl'=>'site_mobilecost', 'act'=>'index') );
			$this->splash('failed', $url, app::get('b2c')->_('请勿重复提交表单！'));
		}
    }
    
    /**
     * 获取实际的金额
     * 2015/7/18
     */
    public function getFactMoney(){
    	$PHONE = !empty($_POST['PHONE'])?htmlspecialchars($_POST['PHONE']):'';
    	$MONEY = !empty($_POST['MONEY'])?htmlspecialchars($_POST['MONEY']):'';
    	
    	//获取电话号码的归属地
		$params['METHOD'] = 'getRechargeAmount';
        $params['PHONE'] = $PHONE;
        $params['MONEY'] = $MONEY;
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'PhoneService');
		$data = array();
		if(!$resSF){
			//返回为空的处理
			echo json_encode($data);
			die;
		}
		$data['ORDER_AMOUNT'] = $resSF['RESULT_DATA']['ORDER_AMOUNT'];
		$data['COUNTER_FEE'] = $resSF['RESULT_DATA']['COUNTER_FEE'];
		$data['MONEY'] = $resSF['RESULT_DATA']['MONEY'];
		echo json_encode($data);
		die;
    }
    
	/**
	 * 获取手机的信息
	 * 2015/7/18
	 */
    public function getMobileInfo(){
    	$PHONE = !empty($_POST['PHONE'])?htmlspecialchars($_POST['PHONE']):'';

        if(trim($_POST['PHONE']) == ""){
            $data['PROVINCE_NAME'] = '号码为空';
            $data['TYPE_NAME'] = '';
            $data['TYPE'] = "fail";
            echo json_encode($data);
            die;
        }

    	//获取电话号码的归属地
		$params['METHOD'] = 'getMotoPlaceService';
        $params['PHONE'] = $PHONE;
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'PhoneService');
		$data = array();
        if(!$resSF){
			$data['PROVINCE_NAME'] = '号码输入错误！';
			$data['TYPE_NAME'] = '';
            $data['TYPE'] = "fail";
			echo json_encode($data);
			die;
		}
		if($resSF['RESULT_CODE'] == '10000'){
			$data['PROVINCE_NAME'] = '不支持付费地区';
			$data['TYPE_NAME'] = '';
            $data['TYPE'] = "fail";
			echo json_encode($data);
			die;
		}
		$data['PROVINCE_NAME'] = $resSF['RESULT_DATA']['PROVINCE_NAME'];
		$data['TYPE_NAME'] = $resSF['RESULT_DATA']['TYPE_NAME'];
        $data['ORGANIZATION_CODE'] = $resSF['RESULT_DATA']['ORGANIZATION_CODE'];

        if($resSF['RESULT_DATA']['RECHARGE_AMOUNT_LIST']){
			$data['RECHARGE_AMOUNT_LIST'] .= "<select class='sdm_select' id='stMoney' name='money' onChange='getMoney(this);'>";
            $data['RECHARGE_AMOUNT_LIST'] .= "<option value='0'>请选择</option>";
            foreach($resSF['RESULT_DATA']['RECHARGE_AMOUNT_LIST'] as $key=>$value){
                $data['RECHARGE_AMOUNT_LIST'] .= "<option value='".$value['MONEY']."'>".$value['MONEY']."</option>";
            }
			$data['RECHARGE_AMOUNT_LIST'] .="</select><span style='margin-left: 20px;color:red;' id='cztips'></span>";
        }else{
			$data['RECHARGE_AMOUNT_LIST'] .= "<select class='sdm_select' id='stMoney' name='money' onChange='getMoney(this);'>";
            $data['RECHARGE_AMOUNT_LIST'] .= "<option value='0'>请选择</option><option value='50'>50</option><option value='100'>100</option><option value='300'>300</option>";
			$data['RECHARGE_AMOUNT_LIST'] .="</select><span style='margin-left: 20px;color:red;' id='cztips'></span>";
        }
        $data['TYPE'] = "succ";
		echo json_encode($data);
		die;
    }
	
	/**
	 * 获取初始化充值金额信息
	 * 2015/11/6
	 */
    public function getDefaultMobileInfo(){

    	//获取电话号码的归属地
		$params['METHOD'] = 'getRechargeAmountList';
        $params['PHONE'] = '';
		$resSF = SFSC_HttpClient::doLifCostMain($params, 'PhoneService');
		$data = array();
        if($resSF['RECHARGE_AMOUNT_LIST']){
			$data['RECHARGE_AMOUNT_LIST'] .= "<select class='sdm_select' id='stMoney' name='money' onChange='getMoney(this);'>";
            $data['RECHARGE_AMOUNT_LIST'] .= "<option value='0'>请选择</option>";
            foreach($resSF['RECHARGE_AMOUNT_LIST'] as $key=>$value){
                $data['RECHARGE_AMOUNT_LIST'] .= "<option value='".$value['MONEY']."'>".$value['MONEY']."</option>";
            }
			$data['RECHARGE_AMOUNT_LIST'] .="</select><span style='margin-left: 20px;color:red;' id='cztips'></span>";
        }else{
			$data['RECHARGE_AMOUNT_LIST'] .= "<select class='sdm_select' id='stMoney' name='money' onChange='getMoney(this);'>";
            $data['RECHARGE_AMOUNT_LIST'] .= "<option value='0'>请选择</option><option value='50'>50</option><option value='100'>100</option><option value='300'>300</option>";
			$data['RECHARGE_AMOUNT_LIST'] .="</select><span style='margin-left: 20px;color:red;' id='cztips'></span>";
        }
        $data['TYPE'] = "succ";
		echo json_encode($data);
		die;
    }
    
    public function index(){
		$this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('手机充值'),'link'=>'#');
		$this->pagedata['_PAGE_']='mobilecost.html';
		$this->pagedata['title']='手机充值';
		$this->pagedata['otitle'] = '提供上海手机充值业务';
		//xhk 2015/11/05 获取后台配置缴费规则
		$kv_obj=kernel::single('base_mdl_kvstore');
		$recharge_rules=$kv_obj->getList('*',array('key'=>'recharge.rules.set'));
		$this->pagedata['recharge_rules'] = $recharge_rules[0];
        //getRechargeAmountList
        /*
        $param = array(
            'METHOD'=>'getRechargeAmountList',
            'PHONE'=>''
        );
        $mobilecost = SFSC_HttpClient::get_sfsc_select("");
        */
        $this->pagedata['get_sfsc_select'] = "get_sfsc_select";
		$_SESSION["mobile_flag"]='true';
        $this->ebappoutput();
    }

    
}
