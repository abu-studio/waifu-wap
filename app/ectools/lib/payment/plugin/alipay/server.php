<?php

 
/**
 * alipay notify 验证接口
 * 
 * @version 0.1
 * @package ectools.lib.payment.plugin
 */
class ectools_payment_plugin_alipay_server extends ectools_payment_app {
	
	/**
	 * 支付后返回后处理的事件的动作
	 * @params array - 所有返回的参数，包括POST和GET
	 * @return null
	 */
    public function callback(&$recv)
	{
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', substr(__CLASS__, 0, strrpos(__CLASS__, '_')));
        $mer_id = $mer_id == '' ? '2088002003028751' : $mer_id;
        $mer_key = $this->getConf('mer_key', substr(__CLASS__, 0, strrpos(__CLASS__, '_')));
        $mer_key = $mer_key=='' ? 'afsvq2mqwc7j0i69uzvukqexrzd0jq6h' : $mer_key;         

        if($this->is_return_vaild($recv,$mer_key)){
            $ret['payment_id'] = $recv['out_trade_no'];
			$ret['account'] = $mer_id;
			$ret['bank'] = app::get('ectools')->_('支付宝');
			$ret['pay_account'] = app::get('ectools')->_('付款帐号');
			$ret['currency'] = 'CNY';
			$ret['money'] = $recv['total_fee'];
			$ret['paycost'] = '0.000';
			$ret['cur_money'] = $recv['total_fee'];
            $ret['trade_no'] = $recv['trade_no'];
			$ret['t_payed'] = strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time();
			$ret['pay_app_id'] = "alipay";
			$ret['pay_type'] = 'online';			
			$ret['memo'] = $recv['body'];
			
            switch($recv['trade_status']){
				// ipn方式回来
				case 'WAIT_BUYER_PAY':
					echo "success";
					$ret['status'] = 'ready';
					break;
                case 'TRADE_FINISHED':
					echo "success";
                    $ret['status'] = 'succ';
                    break;
                case 'TRADE_SUCCESS':
					echo "success";
                    $ret['status'] = 'succ';
                    break;
                case 'WAIT_SELLER_SEND_GOODS':
					echo 'success';
                    $ret['status'] = 'progress';
                    break;
           }

        }else{
            $ret['message'] = 'Invalid Sign';            
            $ret['status'] = 'invalid';
        }
		
		return $ret;
    }
    
    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($form,$key)
	{
        ksort($form);
        foreach($form as $k=>$v){
            if($k!='sign'&&$k!='sign_type'){
                $signstr .= "&$k=$v";
            }
        }

        $signstr = ltrim($signstr,"&");
        $signstr = $signstr.$key;   

        if($form['sign']==md5($signstr)){
            return true;
        }
        #记录返回失败的情况	
		kernel::log(app::get('ectools')->_('支付单号：') . $form['out_trade_no'] . app::get('ectools')->_('签名验证不通过，请确认！')."\n");
		kernel::log(app::get('ectools')->_('本地产生的加密串：') . $signstr);
		kernel::log(app::get('ectools')->_('支付宝传递打过来的签名串：') . $form['sign']);
		$str_xml .= "<alipayform>";
        foreach ($form as $key=>$value)
        {
            $str_xml .= "<$key>" . $value . "</$key>";
        }
        $str_xml .= "</alipayform>";
         
        return false;
    }
    
	/**
	 * 批量退款异步回调
	 * @params array - 所有返回的参数，包括POST和GET
	 * @echo 退款单处理结果（To支付宝）
	 */
    public function refund($recv)
	{
		if($recv){
			$verify_result = $this->verifyNotify($recv);
			if($verify_result){
				if($this->update_refunds($recv)){
					echo "success";
					exit;
				}
			}
		}
		echo 'fail';
		exit;
	}

	//更新退款单信息&状态
	function update_refunds($recv){
		$db = kernel::database();
		$transaction_status = $db->beginTransaction();
		$obj_refunds = app::get('ectools')->model('refunds');
		$batch_no = $recv['batch_no'];
		$result_details = $recv['result_details'];

		$result_arr = explode('#',$result_details);
		foreach($result_arr as $k=>$v){
			$row = explode('^',$v);
			$trade_no = $row[0];
			$money = $row[1];
			$result = $row[2];

			//查询交易号对应支付单
			$sql_payments = 'SELECT * FROM sdb_ectools_payments p LEFT JOIN sdb_ectools_order_bills ob ON p.payment_id=ob.bill_id WHERE p.trade_no="'.$trade_no.'" AND p.status="succ" AND p.pay_app_id="alipay"';
			$payments = $db->select($sql_payments);
			//若参与了合并支付，则无法处理
			if(count($payments)>1||$payments[0]['merge_payment_id']){
				continue;
			}
			//得到订单号
			$order_id = $payments[0]['rel_id'];
			//根据订单号查询退款单
			$sql_refunds = 'SELECT * FROM sdb_ectools_order_bills ob LEFT JOIN sdb_ectools_refunds p ON ob.bill_id=p.refund_id WHERE ob.rel_id="'.$order_id.'" AND ob.bill_type="refunds" AND p.status="ready" AND p.pay_app_id="alipay" ORDER BY p.t_begin ASC';
			$refunds = $db->select($sql_refunds);
			$refund = $refunds[0];
			//遇到异常时，更新退款单备注
			if(strtoupper($result)!='SUCCESS'){
				$update = $obj_refunds->update(array('memo'=>'（支付宝退款失败：'.$result.'）'.$refund['memo']),array('refund_id'=>$refund['refund_id']));
				if(!$update){
					$db->rollback();
					return false;
				}
				continue;
			}
			if(floatval($money)!=floatval($refund['cur_money'])){
				$update = $obj_refunds->update(array('memo'=>'（支付宝退款金额与退款单金额不符，请人工核实）'.$refund['memo']),array('refund_id'=>$refund['refund_id']));
				if(!$update){
					$db->rollback();
					return false;
				}
				continue;
			}
			$update = $obj_refunds->update(array('status'=>'succ','memo'=>'（支付宝退款完成）'.$refund['memo']),array('refund_id'=>$refund['refund_id']));
			if(!$update){
				$db->rollback();
				return false;
			}
		}

		$db->commit($transaction_status);
		return true;
	}

    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyNotify($recv){
		if(empty($recv)) {//判断POST来的数组是否为空
			return false;
		}else{
			//生成签名结果
			$isSign = $this->getSignVeryfy($recv, $recv["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'false';
			if (!empty($recv["notify_id"])){
				$responseTxt = $this->getResponse($recv["notify_id"]);
			}
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
	function getSignVeryfy($para_temp, $sign) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);
		//对待签名参数数组排序
		$para_sort = $para_filter;
		ksort($para_sort);
		reset($para_sort);
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);

		$isSgin = false;
		$mer_key = $this->getConf('mer_key', substr(__CLASS__, 0, strrpos(__CLASS__, '_')));
		$prestr = $prestr.$mer_key;
		if($sign==md5($prestr)){
			$isSgin = true;
		}

		return $isSgin;
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	function paraFilter($para) {
		$para_filter = array();
		while (list ($key, $val) = each ($para)) {
			if($key == "sign" || $key == "sign_type" || $val == "")continue;
			else	$para_filter[$key] = $para[$key];
		}
		return $para_filter;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	function createLinkstring($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".$val."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);
		
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
		
		return $arg;
	}

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
	function getResponse($notify_id) {
		$transport = strtolower(trim('http'));
		$partner = $this->getConf('mer_id', substr(__CLASS__, 0, strrpos(__CLASS__, '_')));
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
		} else {
			$veryfy_url = 'http://notify.alipay.com/trade/notify_query.do?';
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$cacert_url = getcwd().'//app/ectools/lib/payment/plugin/alipay/cacert.pem';
		$responseTxt = $this->getHttpResponseGET($veryfy_url, $cacert_url);
		
		return $responseTxt;
	}

	/**
	 * 远程获取数据，GET模式
	 * 注意：
	 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
	 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
	 * @param $url 指定URL完整路径地址
	 * @param $cacert_url 指定当前工作目录绝对路径
	 * return 远程输出的数据
	 */
	function getHttpResponseGET($url,$cacert_url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
		$responseText = curl_exec($curl);
		//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($curl);
		
		return $responseText;
	}
}
