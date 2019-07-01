<?php
class SFSC_HttpClient {
    static function doPost($url, $post_data){
        //添加支付日志开始
        $post_array = json_decode($post_data['inputParam'],true);
        $methodNeedLog = array('singlePay','singlePayCallBack','sectionPointCancel','updateDocItemStatus');
        if(in_array($post_array['METHOD'] , $methodNeedLog)){
            if('singlePay' == $post_array['METHOD']){
                self::order_into_ectool_cycle($post_array);
            }

            $log_id = self::log_insert($post_array);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,9);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        $output = curl_exec($ch);
        curl_close($ch);
        //添加支付日志结束
        if(!empty($log_id)){
            self::log_update($output , $log_id);
        }
        if($curl_errno >0){
            return false;
        }else{
            return json_decode($output);
        }

    }
    /**
     * 登录检验
     * @param $name
     * @param $pwd
     * @param null $url
     */
    static function doLogin($name,$pwd){
        $url = DO_SERVER_URL;
        $_sjson = "{'USER_NAME':'".$name."','USER_PWD':'".$pwd."'}";
        $post_data = array('serviceNo'=>'EmployeeLogin',"inputParam"=>$_sjson);
        $pageContents = self::doPost($url, $post_data);
        $arr = self::objectToArray($pageContents);

        $_HUMBAS_NO =  $arr["RESULT_DATA"]['HUMBAS_NO'];
        //将HUMBAS_NO 存入session中
        $_SESSION["HUMBAS_NO"] = $_HUMBAS_NO ;
		//更新会员等级
		if(isset($arr['RESULT_DATA']['MEMBER_LEV'])&&$arr['RESULT_DATA']['MEMBER_LEV']!=''){
			$mem_lv=$arr['RESULT_DATA']['MEMBER_LEV'];
			/*
			if($mem_lv=="I03501"){
				$member_lv = 1;
			}
			if($mem_lv=="I03502"){
				$member_lv = 3;
			}
			if($mem_lv=="I03503"){
				$member_lv = 2;
			}
            if($mem_lv=="I03504"){
				$member_lv = 4;
			}
			if($mem_lv=="I03505"){
				$member_lv = 5;
			}
			*/
			$member_lv = (int)substr($mem_lv,-1,1);
			
			
			if($member_lv){
				$obj_account = app::get('pam')->model('account');
				$obj_member = app::get('b2c')->model('members');
				$account = $obj_account->getRow('*',array('login_name'=>$_HUMBAS_NO));
				$obj_member->update(array('member_lv_id'=>$member_lv),array('member_id'=>$account['account_id']));
			}
		}
	return self::objectToArray($pageContents);
    }
      /**
     * 用户名检验
     */
    static  function  doCheckUname($login_name){
        $url = DO_SERVER_URL;
        $_sjson = "{'USER_NAME':'$login_name'}";
        $post_data = array('serviceNo'=>'CheckUname',"inputParam"=>$_sjson);
        $pageContents = self::doPost($url, $post_data);
        $_isExt = self::objectToArray($pageContents);
        return $_isExt;
    }

    /***
     * 外服接口测试应用
     * @param $name
     * @param $idcard
     * @param $login_name
     * @param $login_pwd
     * @param $email
     * @return mixed
     */
    static  function  doRegister($name,$idcard,$login_name,$login_pwd ,$email){
        $url = DO_SERVER_URL;
        $_sjson = "{'name':'".$name."','idcard':'".$idcard."','login_name':'".$login_name."','login_pwd':'".$login_pwd."','email':'".$email."'}";		
        $post_data = array('serviceNo'=>'EmployeeRegister',"inputParam"=>$_sjson);	        
		$pageContents = self::doPost($url, $post_data);	
		return self::objectToArray($pageContents);
    }
	
	/**
     * 会员主页
     */
    static function doMemberMain($login_name){
        $url = DO_SERVER_URL;
        $_sjson = "{'METHOD':'getEmployeeDataByHumbas_No','HUMBAS_NO':'$login_name'}";
        $post_data = array('serviceNo'=>'EmployeeService',"inputParam"=>$_sjson);
        $pageContents = self::doPost($url, $post_data);
		$res = self::objectToArray($pageContents);
        //fedex rdp无返回 模拟数据 by zyp@2018-12-14
        if (empty($res) && substr($login_name,0,5) == 'fedex')
        {
            //显示用户名
            $pamMdl = app::get('pam')->model('account');
            $memberMdl = app::get('b2c')->model('members');
            $memberId = $pamMdl->getRow('account_id', array('login_name'=>$login_name));
            $memberInfo = $memberMdl->getRow('name',array('member_id'=>$memberId['account_id']));
            $res = array(
                'RESULT_DATA'=>array(
                   'NAME' => $memberInfo['name']?$memberInfo['name']:$login_name,
                   'HUMBAS_NO' => $login_name,
                   'COMPANY_NO' => 'FD0001',
                   'PRODUCT_COUNT' => 0,
                   'COMPANY_NAME' => '联邦快递',
                   'SUM' => 0,
                   'CHANNEL_LIST' => array(
                        array(
                           'SYS_OPEN_STATUS' => 'I01501',
                           'CUSTOMER_OPEN_STATUS' => 'I01501',
                           'CHANNEL_ID' => '1',
                           'CUSTOMER_ID' => 'FD0001',
                           'CHANNEL_NAME' => '商城 E-mall',
                        ),
                        array (
                            'SYS_OPEN_STATUS' => 'I01501',
                            'CUSTOMER_OPEN_STATUS' => 'I01501',
                            'CHANNEL_ID' => '85',
                            'CUSTOMER_ID' => 'FD0001',
                            'CHANNEL_NAME' => 'APM年终内购会',
                        ),
                   ),
                ),
                'RESULT_CODE' => '10001',
            );
        }
        if($res["RESULT_DATA"]['customer.model'] ==  "other"){
            $_SESSION['sfsc']['model'] = $res["RESULT_DATA"]['customer.model'];
            $_SESSION['sfsc']['NAME_EN'] = $res["RESULT_DATA"]['NAME_EN'];
            setCookie('JAVA[UNAME_EN]',$res["RESULT_DATA"]['NAME_EN'],time()+"86400","/");
        }elseif($res["RESULT_DATA"]['customer.model'] ==  "zhongan"){
            $_SESSION['sfsc']['model'] = $res["RESULT_DATA"]['customer.model'];
            $_SESSION['sfsc']['NAME_EN'] = $res["RESULT_DATA"]['NAME_EN'];
            setCookie('JAVA[UNAME_EN]',$res["RESULT_DATA"]['NAME_EN'],time()+"86400","/");
        }else{
            $_SESSION['sfsc']['model'] = "";
        }


		//更新商社号、商社名称
		if(isset($res['RESULT_DATA']['COMPANY_NO'])&&isset($res['RESULT_DATA']['COMPANY_NAME'])){
			$obj_account = app::get('pam')->model('account');
			$account = $obj_account->getRow('*',array('login_name'=>$login_name));
			if($res['RESULT_DATA']['COMPANY_NO']!=$account['company_no']||$res['RESULT_DATA']['COMPANY_NAME']!=$account['company_name']){
				$obj_account->update(array('company_no'=>$res['RESULT_DATA']['COMPANY_NO'],'company_name'=>$res['RESULT_DATA']['COMPANY_NAME']),array('login_name'=>$login_name));
			}
		}
        return $res;
    }



	static function objectToArray($e){
            $e=(array)$e;
            foreach($e as $k=>$v){
                if( gettype($v)=='resource' ) return;
                if( gettype($v)=='object' || gettype($v)=='array' )
                    $e[$k]=(array)self::objectToArray($v);
            }
            return $e;
        }
        
	static function doLifCostMain($params, $serviceNo){
        $url = DO_SERVER_URL;
        $_sjson = json_encode($params);
        $post_data = array('serviceNo'=>$serviceNo,"inputParam"=>$_sjson);
        $pageContents = self::doPost($url, $post_data);
        return self::objectToArray($pageContents);
    }



    static function get_sfsc_select($param = array()){

        $_sjson_select = array(
            'METHOD'=>'getOptionList',
            'BIZ_ID'=>'biz009',
            'PARAMS'=>array(
                array(
                    'PARAM'=>'aegis01'
                )
            )
        );
        $post_data = array('serviceNo'=>'SelectService',"inputParam"=>json_encode($_sjson_select));
        $pageContents = self::doPost(DO_SERVER_URL, $post_data);
        $data =  self::objectToArray($pageContents);
        if($data['RESULT_CODE'] == "10001" && !empty($data['RESULT_DATA'])){
            return $data['RESULT_DATA'];
        }
        return false;
    }

    static function order_into_ectool_cycle($sdf){
        $db = kernel::database();

        $orderId = $sdf['order_id'];
        $paymentId = $sdf['payment_id'];
        $tBeginPay = time();

        $sql = "insert into sdb_ectools_order_status_cycle(payment_id,order_id , t_begin_pay,cycle_status) values ('$paymentId', '$orderId' , '$tBeginPay' ,'0')";
        $db->exec($sql);

        //如果之前有过支付，将之前的删除
        $sql = "update sdb_ectools_order_status_cycle set cycle_status='2' where order_id='$orderId' and payment_id != '$paymentId'";
        $db->exec($sql);
        $db->commit(true);
    }

    static function log_insert($post_array){
        $obj_rdp_log = app::get("cardcoupons")->model("rdp_log");
        $singlePay_data =array();
        $singlePay_data['request_data'] = json_encode($post_array);
        $singlePay_data['last_modified'] = time();
        $log_id = $obj_rdp_log->insert($singlePay_data);

        return $log_id;
    }

    static function log_update($output , $log_id){
        if($log_id < 1){
            return false;
        }

        $output = is_array($output)?json_encode($output):$output;
        $obj_rdp_log = app::get("cardcoupons")->model("rdp_log");
        $singlePay_data =array();
        $singlePay_data['result_data'] = $output;
        $singlePay_data['update_modified'] = time();
        $obj_rdp_log->update($singlePay_data,array('log_id'=>$log_id));

        return true;
    }

    static function log_sfsc_pay($message) {
        $logFilename = DATA_DIR . '/sfsc_pay.log';
        $message = date('Y-m-d H:i:s',time()) . "\t" . $message . "\r\n";
        file_put_contents($logFilename , $message, FILE_APPEND);
    }
        
    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 14:04
     * @Desc: 获取java礼包信息
     */
    static function getJavaLibao($uname)
    {
        $tmp_arr = array();
        $_sjson_libao = array(
            'METHOD'=>'getDocItemList',
            'HUMBAS_NO'=>$uname,
            'REC_STATUS'=>'I01101',
        );
        $post_data_libao = array('serviceNo'=>'DocumentItemService',"inputParam"=>json_encode($_sjson_libao));
        $tmpdata_libao = self::doPost(DO_SERVER_URL,$post_data_libao);
        if(!empty($tmpdata_libao) && gettype($tmpdata_libao) == "object")
        {
            $tmp_arr = self::objectToArray($tmpdata_libao);
        }
        return $tmp_arr;
    }


    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 14:04
     * @Desc: 获取java礼包信息
     */
    static function getJavaCondolences($uname,$status="",$grant_name="")
    {
        $tmp_arr = array();
        $_sjson = array(
            'METHOD'=>'getDocItemList',
            'HUMBAS_NO'=>$uname,
        );
        if(!empty($status)){
            $_sjson['REC_STATUS'] = $status;
        }
        if(!empty($grant_name)){
            $_sjson['GRANT_NAME'] = $grant_name;
        }
        $post_data = array('serviceNo'=>'DocumentItemService',"inputParam"=>json_encode($_sjson));
        $tmpdata= self::doPost(DO_SERVER_URL,$post_data);
        if(!empty($tmpdata) && gettype($tmpdata) == "object")
        {
            $tmp_arr = self::objectToArray($tmpdata);
        }
        return $tmp_arr;
    }
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
}
