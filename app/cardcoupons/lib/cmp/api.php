<?php
/**
 * 外部调用接口统一调用的api类
 *
 * @version 0.1
 * @package cardcoupons.lib
 */
class cardcoupons_cmp_api
{
    private $app;	
    private $signKey = "Od3uFXqlRejyaWvzX4GGVy7ilYLw02K10am1q3DMWoCLM0WmYw6owon2Heg3vkcs";
    private $reqUrl = "https://ipos.10086.cn/ips/cmpayService";
    private $merchantId = "888009915000282";
    /*
     * 构造方法
     * @param object 当前应用的app
     * @return null
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /*  和包平台调用总汇
     *
     * */
    public function m(){		
        $Type = $_REQUEST['Type'];
		if($Type == ""){
			$Type = $_REQUEST['type'];
		}		
        switch ($Type)
        {
            case "MercProduceQry":
                $this->MercProduceQry();
                break;
            case "OfflineNotify":
                $this->OfflineNotify();
                break;
            default:
                echo "FAILURE";
				die();
        }
    }


    /*
        商户库存管理查询接口(被动，和包平台)
    */
    private function MercProduceQry(){
        //请求参数-签名字段: merchantId|jrnNo|signType|type|version|prodNo|requestTime|reserved
        //返回参数-签名字段: merchantId|merchantNo|signType|type|version|returnCode|message|prodNo|prodNum|reserved|
        //我方平台给商户分配的唯一标识
		//$this->write_cmpapi_log($Type,"到这个方法了","临时记录日志信息");
        $merchantId = $_REQUEST['merchantId'];
        //平台请求商户的交易流水号
        $jrnNo = $_REQUEST['jrnNo'];
        //只能是MD5或RSA
        $signType = $_REQUEST['signType'];
        //MercProduceQry
        $Type = 'MercProduceQry';
        //1.0.0
        $version = $_REQUEST['version'];
        //商品编号
        $prodNo = $_REQUEST['prodNo'];
        //平台请求商户的时间
        $requestTime = $_REQUEST['requestTime'];
        //保留字段
        $reserved = $_REQUEST['reserved'];
        //签名字段
        $hmac = $_REQUEST['hmac'];
        //交易流水号
        $merchantNo = date("Ymd").time();
        $prodNum = '0';
        $returnCode = "SUCCESS";
        $message = "SUCCESS";
		$obj_products = app::get("b2c")->model("products");
		$cmpapi = "MercProduceQry";
		if($prodNo != ""){
			$goods_id = $obj_products->dump(array("bn"=>$prodNo),"goods_id");
		}else{
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"无对应产品信息");	
			echo "FAILURE";
			return false;
		}
		
        //获取本平台的货品数量 start
        $db = kernel::database();
        $sql = "SELECT count(pass.card_pass_id) FROM sdb_cardcoupons_cards_pass AS pass LEFT JOIN sdb_cardcoupons_cards AS card ON pass.card_id = card.card_id WHERE card.goods_id =".$goods_id['goods_id']." AND pass.`status` ='0' AND pass.`type`='virtual' AND pass.`source`='external' AND pass.`disabled`='false' AND pass.`ex_status`='true'";        
		$card_goods = $db->select($sql);
        if($card_goods[0]['count(pass.card_pass_id)']){
            $prodNum = $card_goods[0]['count(pass.card_pass_id)'];
        }
		
		
		if($prodNum == "0"){
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"该商品无库存");
			echo "FAILURE";
			return false;
		}

        //获取本平台的货品数量 end

        $signData = $merchantId.$merchantNo.$signType.$Type.$version.$returnCode.$message.$prodNo.$prodNum.$reserved;
        $hmacl = $this->get_sign($this->signKey,$signData);
        //返回参数-签名字段: merchantId|merchantNo|signType|Type|version|returnCode|message|prodNo|prodNum|reserved|
        echo $hmacl."|".$merchantId."|".$merchantNo."|".$signType."|".$Type."|".$version."|".$returnCode."|".$message."|".$prodNo."|".$prodNum."|".$reserved;

    }


    /*
        短信支付接口信息(和包平台支付成功后，调用该接口--被动)
    */
    private function OfflineNotify(){		
        //签名字段
        $hmac = $_REQUEST['hmac'];
        //商户编号
        $merchantId = $_REQUEST['merchantId'];
        //流水号
        $payNo = $_REQUEST['payNo'];
        //返回码
        $returnCode = $_REQUEST['returnCode'];
        //返回码描述
        $message = $_REQUEST['message'];
        //签名方式
        $signType = $_REQUEST['signType'];
        //接口类型
        $type = 'OfflineNotify';
        //版本号
        $version = $_REQUEST['version'];
        //支付金额
        $amount = $_REQUEST['amount'];
        //金额明细
        $amtItem = $_REQUEST['amtItem'];
        //支付银行
        $bankAbbr = $_REQUEST['bankAbbr'];
        //支付手机号
        $mobile = $_REQUEST['mobile'];
        //商户订单号
        $orderId = $_REQUEST['orderId'];
        //支付时间
        $payDate = $_REQUEST['payDate'];
        //会计日期
        $accountDate = $_REQUEST['accountDate'];
        //保留字段1 （第一个#号和第二个#表示 商品和数量）
        $reserved1 = $_REQUEST['reserved1'];
        //保留字段2 (表示 地址)
        $reserved2 = $_REQUEST['reserved2'];
        //支付结果
        $status = $_REQUEST['status'];
        //订单提交日期
        $orderDate = $_REQUEST['orderDate'];
        //费用
        $fee = $_REQUEST['fee'];
		
		
		$pos = strpos($reserved1,"%23");
	
	if($pos === false){
			$reserved1_array = explode("#",$reserved1);
		}else{
			$reserved1_array = explode("%23",$reserved1);
		}




        $goods_bn = $reserved1_array[0];
        //根据业务需求数量只有可能是1
        $nums = $reserved1_array[1];
		$cmp_mobile = $reserved1_array[2];
        //检查支付回调记录是否存在于日志中，如果存在说明已经支付成功，如果不存在，则执行 支付动作（防止和包平台多次请求造成重复发送）
        $obj_cmpay_offlinenotify_log = app::get("cardcoupons")->model("cmpay_offlinenotify_log");
		$log_cmpbayapiLog = app::get("cardcoupons")->model("cmpay_log");
		$cmpapi = "OfflineNotify";
        if($payNo == ""){
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"请求字段payNo为空");
            echo "FAILURE";
            die();
        }else{	
			//获取和包平台支付记录
			$log_payno_array = $obj_cmpay_offlinenotify_log->dump(array("payno"=>$payNo),"*");
            if(!empty($log_payno_array)){
				if($log_payno_array['order_id'] && $log_payno_array['is_send'] == "N"){
					$obj_pass =  app::get("cardcoupons")->model("cards_pass");
					$pass_info = $obj_pass->dump(array("order_id"=>$log_payno_array['order_id']),"*");
					if(!empty($pass_info)){
						if($this->MkmMercSendSms($cmp_mobile,$log_payno_array['cmp_order_id'],$pass_info,$log_payno_array['order_id'])){
						//记录发送成功的 和包平台订单信息(防止和包平台多次请求造成，多次错误发送)
							$obj_cmpay_offlinenotify_log->update(array('is_send'=>'Y'),array('payno'=>$payNo));		
							echo "SUCCESS";
							die();
						}else{		
							echo "FAILURE";
							return false;
						}
					}else{
						echo "FAILURE";
						return false;
					}					 
				}			
				$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"payNo号已经记录");
                echo "FAILURE";
                die();
            }
        } 
		$obj_products = app::get("b2c")->model("products");
		if($goods_bn != ""){
			$goods_id = $obj_products->dump(array("bn"=>$goods_bn),"goods_id");
		}else{
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"检测缺失商品id");
			echo "FAILURE";
			return false;
		}
		//查看存库信息
		$db = kernel::database();
        $sql = "SELECT count(pass.card_pass_id) FROM sdb_cardcoupons_cards_pass AS pass LEFT JOIN sdb_cardcoupons_cards AS card ON pass.card_id = card.card_id WHERE card.goods_id =".$goods_id['goods_id']." AND pass.`status` ='0' AND pass.`type`='virtual' AND pass.`source`='external' AND pass.`disabled`='false' AND pass.`ex_status`='true'";        
		$card_goods = $db->select($sql);		
        if(empty($card_goods[0]['count(pass.card_pass_id)']) || $card_goods[0]['count(pass.card_pass_id)'] == "0"){
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"商品无库存");
            echo "FAILURE";
			return false;
        }
        $msg = "";
        //根据goods_id 生成一个订单
        $order_id = $this->getSfscOrder($goods_id['goods_id'],1,$cmp_mobile,$msg);
		
        if($order_id == ""){
            echo $msg;
            return false;
        }else{
            //如果订单生成以后，记录 请求log
            $cmpay_array = array(
                "payno" => $payNo,
                "returncode" => $returnCode,
                "message" => $message,
                "type" => $type,
                "amount" => $amount,
                "amtitem" => implode('|',explode("#",$amtItem)),
                "bankabbr" => $bankAbbr,
                "mobile" => $cmp_mobile,
                "cmp_order_id" => $orderId,
                "paydate" => $payDate,
                "accountdate" => $accountDate,
                "reserved1" => $reserved1,
                "reserved2" => $reserved2,
                "status" => $status,
                "orderdate" => $orderDate,
                "fee" => $fee,
                "is_send" => "N",
                "order_id" => $order_id,
                "last_modified" => time(),
            );			
            $obj_cmpay_offlinenotify_id = $obj_cmpay_offlinenotify_log->insert($cmpay_array);		
        }
       //根据订单id，发送卡卷信息
        $pass_info = $this->getCardPass($order_id,$msg);
		if(empty($pass_info)){
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"商品无库存");
			echo "FAILURE";
			die();
		}
		//本地订单处理完毕  end
        
		//商户卡券信息维护接口
        if(!$this->MkmCardManage($pass_info,$order_id,$cmp_mobile)){
			echo "FAILURE";           
			//返回错误信息
            return false;
        }
		
        //调用短信通知接口
        if($this->MkmMercSendSms($cmp_mobile,$orderId,$pass_info,$order_id)){		
			//记录发送成功的 和包平台订单信息(防止和包平台多次请求造成，多次错误发送)
            $obj_cmpay_offlinenotify_log->update(array('is_send'=>'Y'),array('payno'=>$payNo));
			echo "SUCCESS";
			die();
        }else{			
			echo "FAILURE";
			return false;
        }

	}

    /*
        商户卡券信息维护接口(主动请求短信维护接口)
    */
    private function MkmCardManage($card_info,$order_id,$mobile){
        //商户编号
        $merchantId = $this->merchantId;
        //商户请求号 --订单号码
        $requestId = $order_id;
        //签名方式
        $signType = "MD5";
        $type = "MkmCardManage";
        $version = "2.0.0";
        //卡卷号码
        $cardNo = "卡卷号码:".$card_info['card_no']."，密码:".$card_info['card_pass'];
        //商户名称
        $mercNm = "外服平台";
        //用户的手机号
        $mobileNo = $mobile;
        //卡卷名称
        $cardName = $this->GetGB2312String($card_info['card_name']);
        //1:电影
        //2:美食
        //3:购物
        //4:娱乐
        //5: (商户可与中心约)
        $cardType = "3";
        //卡卷状态
        $cardState = "A";
        //卡卷生效日期
        $effectDate = date("Ymd",$card_info['from_time']);
        //卡卷失效日期
        $expireDate = date("Ymd",$card_info['to_time']);
        //卡卷描述
        $cardDesc = "和包专享9折优惠。";
        //使用须知
        $useDesc = "1、使用本套餐必须提前5个工作日预约，未经预约直接持卡号和密码至体检中心使用无效。\n\r2、本套餐每人限用1次，预约后卡号和密码即时失效，如需修改预约内容，请致电预约热线400-889-8855。\n\r3、卡号和密码不记名、不挂失、不兑换现金、不退换，请妥善保管。\n\r4、预约有效期：兑换当天起1年有效。";
        //分享链接
        $shareUrl = "分享连接";
        //分享标题
        $shareTitle = "分享标题";
        //分享内容
        $shareContent = "分享内容";
        //操作表示
        $operateFlg = 1;
        //卡卷金额
        $cardAmt = $card_info['amount'];

        $signData =  $merchantId.$requestId.$signType.$type.$version.$cardNo.$mercNm.$mobileNo.$cardName.$cardType.$cardState.$effectDate.$expireDate.$cardDesc.$useDesc.$shareUrl.$shareTitle.$shareContent.$operateFlg.$cardAmt;
        $hmac = $this->get_sign($this->signKey,$signData);	
        $requestData = array();
        $requestData['merchantId'] = $merchantId;
        $requestData['requestId'] = $requestId;
        $requestData['signType'] = $signType;
        $requestData['type'] = $type;
        $requestData['version'] = $version;
        $requestData['cardNo'] = $cardNo;
        $requestData['mercNm'] = $mercNm;
        $requestData['mobileNo'] = $mobileNo;
        $requestData['cardName'] = $cardName;
        $requestData['cardType'] = $cardType;
		$requestData['cardState'] = $cardState;
        $requestData['effectDate'] = $effectDate;
        $requestData['expireDate'] = $expireDate;
        $requestData['cardDesc'] = $cardDesc;
        $requestData['useDesc'] = $useDesc;
        $requestData['shareUrl'] = $shareUrl;
        $requestData['shareTitle'] = $shareTitle;
        $requestData['shareContent'] = $shareContent;
        $requestData['operateFlg'] = $operateFlg;
        $requestData['cardAmt'] = $cardAmt;
        $requestData["hmac"] = $hmac;
        $sTotalString = $this->POSTDATA($this->reqUrl,$requestData);		
        $recvArray = $this->parseRecv($sTotalString['MSG']);
        $code = $recvArray["returnCode"];
		$log_cmpbayapiLog = app::get("cardcoupons")->model("cmpay_log");
		$cmpapi = "MkmCardManage";   
		//记录调用信息	
		if($code != "MCG00000"){
			$this->write_cmpapi_log($cmpapi,implode('|',$recvArray),$code,"FAILURE","Y");
            return false;
        }else{
			$this->write_cmpapi_log($cmpapi,implode('|',$recvArray),"调用卡卷维护接口成功","SUCCESS","Y");		
            return true;
        }
    }
	
    /*
        调用和包平台信息 ，发送短信接口
    */
    private function MkmMercSendSms($mobile,$order_id,$pass_info,$payno){
		//商户编号
        $merchantId = $this->merchantId;
        //商户请求号
		$requestId = $payno;
		//签名方式
        $signType = "MD5";
		//业务类型
        $type = "MkmMercSendSms";
		//版本号
        $version = "2.0.0";
		//商户订单号
        $mercordNo = $order_id;
		//手机号
        $mobileNo = $mobile;
		//短信内容
        $smsNo = "MKM9179";
		$_sjson = array(
			'METHOD'=>'encryptMode',
			'skey'=>$this->signKey,
			'sdata'=>$pass_info['card_name']."|".$pass_info['card_no'].$this->decodeGB2312(",密码为：").$pass_info['card_pass']."|".date("Y-m-d H:i:s",$pass_info['to_time'])."|".$this->decodeGB2312("感谢购买")."|400-889-8855|",
		);		
		$post_data = array('serviceNo'=>'EncryptBase64Service',"inputParam"=>json_encode($_sjson));		
        $pageContents = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);
		$pageContents_data = SFSC_HttpClient::objectToArray($pageContents);
		$smsInfFld = $pageContents_data['RESULT_DATA']['rdata'];
		$signData = $merchantId.$requestId.$signType.$type.$version.$mercordNo.$mobileNo.$smsNo.$smsInfFld;
        $hmac = $this->get_sign($this->signKey,$signData);
        $requestData = array();
        $requestData['merchantId'] = $merchantId;
        $requestData['requestId'] = $requestId;
        $requestData['signType'] = $signType;
        $requestData['type'] = $type;
        $requestData['version'] = $version;
        $requestData['mercordNo'] = $mercordNo;
        $requestData['mobileNo'] = $mobileNo;
        $requestData['smsNo'] = $smsNo;
        $requestData['smsInfFld'] = $smsInfFld;
        $requestData['hmac'] = $hmac;
        $sTotalString = $this->POSTDATA($this->reqUrl,$requestData);
        $recvArray = $this->parseRecv($sTotalString['MSG']);
        $code=$recvArray["returnCode"];
		$log_cmpbayapiLog = app::get("cardcoupons")->model("cmpay_log");
		$cmpapi = "MkmMercSendSms";		
        if($code != "MCG00000"){
			$this->write_cmpapi_log($cmpapi,implode('|',$recvArray),$code,"FAILURE","Y");		
            return false;
        }else{
			$this->write_cmpapi_log($cmpapi,implode('|',$recvArray),"调用和包短信发送接口成功","SUCCESS","Y");
			return true;
        }
    }

    /**
     * getSfscOrder-新增和包订单
     *
     * @param       GOODS_ID,NUMS
     * @return      json字符
     */
    private function getSfscOrder($goods_id,$num = 1,$mobile,&$msg){



        //参数:qiyeid，qiyename,goods_id,nums,card_type:virtual-电子卡/entity-实物卡 / 实物
        $goods_object = kernel::single("b2c_mdl_goods");
        $cards_object = kernel::single("cardcoupons_mdl_cards");
        $products_object = kernel::single("b2c_mdl_products");

        if(!$goods_id || !$num){
            $msg = "无商品id";
            return false;
        }
        $products_data = $products_object->dump(array("goods_id"=>$goods_id),'goods_id,product_id');

        if(!$products_data['product_id']){
            $msg = "无货品id";
            return false;
        }
        //todo 现在需求就是 只会下外部电子卡信息
        $goods = array(
            'goods' => Array (
                'cards_pass_type'=>'virtual',
                'num' => $num,
                'goods_id' => $products_data['goods_id'],
                'pmt_id' => "",
                'product_id' =>$products_data['product_id']
            )
        );
        $aCart = array();
        kernel::single('fastbuy_cart_fastbuy_goods')->get_sfsccreateorder_arr(
            $goods,
            array(),
            $aCart
        );		
        $post_array = array(
            'purchase'=>array(
                'member_id'=>""
            ),
            'payment'=>array(
                'currency'=>'CNY',
                'pay_app_id'=>'sfscpay',
                'dis_point'=>'0'
            ),
			'delivery'=>array(
				'ship_mobile'=>$mobile,
				'ship_name'=>$this->decodeGB2312('和包平台客户'),				
			),
            'cmpay'=>true
        );
        //cmpay
        //事物处理开始
        $db = kernel::database();
        $transaction_status = $db->beginTransaction();
        $order = kernel::single("b2c_mdl_orders");
        $post_array['order_id'] = $order_id = $order->gen_id();
        $post_array['member_id'] = 0;
        $obj_order_create = kernel::single("b2c_order_create");
        $goods_data = $goods_object->dump(array("goods_id"=>$goods_id),"store_id");
        $order_data = $obj_order_create->generate($post_array,'',$msg,$aCart);
        $order_data['store_id'] = $goods_data['store_id'];
        $result = $obj_order_create->save($order_data, $msg);
        if($msg){
            return false;
        }
        if ($result){
            foreach( kernel::servicelist('invoice_setting') as $services ) {
                if ( is_object($services) ) {
                    if ( method_exists($services, 'saveInvoiceData') ) {
                        $services->saveInvoiceData($post_array['order_id'],$post_array['payment']);
                    }
                }
            }
            $log_text = "";
            if($result){
                $log_text[] = array(
                    'txt_key'=>'订单创建成功！',
                    'data'=>array(),
                );
                $log_text = serialize($log_text);
            }else{
                $log_text[] = array(
                    'txt_key'=>'订单创建失败！',
                    'data'=>array(),
                );
                $log_text = serialize($log_text);
            }
            $orderLog =  kernel::single("b2c_mdl_order_log");
            $sdf_order_log = array(
                'rel_id' => $order_id,
                'op_id' => "",
                'op_name' => $this->decodeGB2312("后台生成订单"),
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'creates',
                'result' => 'SUCCESS',
                'log_text' => $this->decodeGB2312($log_text),
            );
            $orderLog->save($sdf_order_log);
        }else{
            $db->rollback();
        }
        //模拟支付流程支付和包订单
        if($result){
            if ($order_data){
                $objPay = kernel::single("ectools_pay");
                $sdf = array(
                    'payment_id' => $objPay->get_payment_id(),
                    'order_id' => $order_data['order_id'],
                    'rel_id' => $order_data['order_id'],
                    'op_id' => $order_data['member_id'],
                    'pay_app_id' => $order_data['payinfo']['pay_app_id'],
                    'currency' => $order_data['currency'],
                    'payinfo' => array(
                        'cost_payment' => $order_data['payinfo']['cost_payment'],
                    ),
                    'pay_object' => 'order',
                    'member_id' => $order_data['member_id'],
                    'op_name' => $mobile,
                    'status' => 'ready',
                    'cur_money' => $order_data['cur_amount'],
                    'money' => $order_data['total_amount'],
                );

                $is_payed = $objPay->gopay($sdf, $msg);
                if (!$is_payed){
                    $msg = app::get('b2c')->_('订单自动支付失败！');
                    return false;
                }

                $obj_pay_lists = kernel::servicelist("order.pay_finish");
                $is_payed = false;
                foreach ($obj_pay_lists as $order_pay_service_object)
                {
                    $is_payed = $order_pay_service_object->order_pay_finish($sdf, 'succ', 'font',$msg);
                }
            }
        }else{
            $db->rollBack();
        }
        // 更新发货日志结果
        $objorder_log = kernel::single("b2c_mdl_order_log");
        $objOrders = kernel::single("b2c_mdl_orders");
        $sdf_order_log = array(
            'rel_id' => $order_data['order_id'],
            'op_id' => '0',
            'op_name' => 'auto',
            'alttime' => time(),
            'bill_type' => 'order',
            'behavior' => 'delivery',
            'result' => 'SUCCESS',
            'log_text' => $this->decodeGB2312('和包电子订单已发货，无需物流'),
            'addon' => "",
        );

        $log_id = $objorder_log->save($sdf_order_log);

        //修改订单已发货状态
        if($log_id){
            $aUpdate['order_id'] = $order_data['order_id'];
            $aUpdate['ship_status'] = '1';
            $aUpdate['pay_status'] = '1';
            $aUpdate['consignee']['r_time'] =date("Y-m-d H:i:s",time());
            $aUpdate['payed'] = $order_data['total_amount'];
            $objOrders->save($aUpdate);
            //$obj_apiv = kernel::single('b2c_apiv_exchanges_request');
            //$req_arr['order_id']=$order_data['order_id'];
            //$obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
            $data['confirm_time'] = time()+(app::get('b2c')->getConf('member.to_finish_XU'))*86400;
            app::get('business')->model('orders')->update($data,array('order_id' => $order_data['order_id']));
        }
        //提交订单信息
        $db->commit($transaction_status);
        return $order_data['order_id'];

    }

    /**
     * getCardPass-根据订单获取卡号卡密信息（适用和包平台）
     * @param       order_id,num
     * @return      json字符
     */
    private function getCardPass($order_id,$num = 1,&$msg){
        $order_items_object = kernel::single("b2c_mdl_order_items");
        $cards_object = kernel::single("cardcoupons_mdl_cards");
        $cards_pass_object = kernel::single("cardcoupons_mdl_cards_pass");
        $orders_items_data = $order_items_object->dump(array("order_id"=>$order_id),"goods_id,item_type,nums,amount");
        if(empty($orders_items_data)){
            $msg = "没有找到该订单！";
            return false;
        }
        if($orders_items_data['item_type'] != "virtual"){
            $msg = "本接口暂时只支持电子券！";
            return false;
        }
        $cards_data = $cards_object->dump(array("goods_id"=>$orders_items_data['goods_id'],"source"=>"external"),"card_id");
        if(empty($cards_data)){
            $msg = "该卡密未找到，或者已经被删除！";
            return false;
        }


        $virtual_pass = $cards_pass_object->getList('*',array('card_id'=>$cards_data['card_id'],'disabled'=>'false','status'=>'0','ex_status'=>'true','type'=>'virtual','source'=>'external'),0,1,"card_no ASC");
        if(!empty($virtual_pass)){
            if($cards_pass_object->update(array('status'=>'1','order_id'=>$order_id),array('card_pass_id'=>$virtual_pass[0]['card_pass_id'],'status'=>'0'))){
                return $virtual_pass[0];
            }else{
                $msg="卡券电子码库存不足，请联系客服";
                return false;
            }
        }else{
            $msg="卡券电子码库存不足，请联系客服";
            return false;
        }

    }
	
	private function write_cmpapi_log($cmpapi,$string,$result_describe,$status = "FAILURE",$type = "N"){
		$log_cmpbayapiLog = app::get("cardcoupons")->model("cmpay_log");
		$log_cmpbayapi_array = array(
				"request_type" => $type,
				"returncode" => $this->decodeGB2312($string),
				"result" => $status,
				"cmpapi" => $cmpapi,
				"result_describe" => $this->decodeGB2312($result_describe),
				"last_modified"=> time(),
		);		
		$log_cmpbayapiLog->save($log_cmpbayapi_array);
	}	

    /**
     * 得到实例应用名
     * @params string - 请求的url
     * @return object - 应用实例
     */
    private function getAppName($strUrl='')
    {
        //todo.
        if (strpos($strUrl, '/') !== false)
        {
            $arrUrl = explode('/', $strUrl);
        }
        return app::get($arrUrl[0]);
    }

    /*
    功能 发送HTTP请求(和包平台专用请求函数)
    URL  请求地址
    data 请求数据数组
    */
    private function POSTDATA($url, $data)
    {
        $url = parse_url($url);
        if (!$url)
        {            
            return "couldn't parse url";
        }
        if (!isset($url['port'])) { $url['port'] = ""; }

        if (!isset($url['query'])) { $url['query'] = ""; }


        $encoded = "";

        while (list($k,$v) = each($data))
        {
            $encoded .= ($encoded ? "&" : "");
            $encoded .= rawurlencode($k)."=".rawurlencode($v);
        }
        $urlHead = null;
        $urlPort = $url['port'];
        if($url['scheme'] == "https")
        {
            $urlHead = "ssl://".$url['host'];
            if($url['port'] == null || $url['port'] == 0)
            {
                $urlPort = 443;
            }
        }
        else
        {
            $urlHead = $url['host'];
            if($url['port'] == null || $url['port'] == 0)
            {
                $urlPort = 80;
            }
        }
		
        $fp = fsockopen($urlHead, $urlPort);

        if (!$fp) return "Failed to open socket to $url[host]";

        $tmp="";
        $tmp.=sprintf("POST %s%s%s HTTP/1.0\r\n", $url['path'], $url['query'] ? "?" : "", $url['query']);
        $tmp.="Host: $url[host]\r\n";
        $tmp.="Content-type: application/x-www-form-urlencoded\r\n";
        $tmp.="Content-Length: " . strlen($encoded) . "\r\n";
        $tmp.="Connection: close\r\n\r\n";
        $tmp.="$encoded\r\n";
        fputs($fp,$tmp);

        $line = fgets($fp,1024);

        if (!preg_match("#^HTTP/1\.. 200#i", $line))
        {
            $logstr = "MSG".$line;
            //RecordLog("YGM",$logstr);
            return array("FLAG"=>0,"MSG"=>$line);
        }

        $results = ""; $inheader = 1;
        while(!feof($fp))
        {
            $line = fgets($fp,1024);
            if ($inheader && ($line == "\n" || $line == "\r\n"))
            {
                $inheader = 0;
            }
            elseif (!$inheader)
            {
                $results .= $line;
            }
        }
        fclose($fp);
        return array("FLAG"=>1,"MSG"=>$results);
    }

    /*
    功能 把http请求返回数组 格式化成数组
    */
    private function parseRecv($source)
    {
        $ret = array();
        $temp = explode("&",$source);

        foreach ($temp as $value)
        {
            $index=strpos($value,"=");
            $_key=substr($value,0,$index);
            $_value=substr($value,$index+1);
            $ret[$_key] = $_value;
        }

        return $ret;
    }

    /*
    功能 和包平台签名函数
    */
    private function get_sign($okey,$odata){
        $signdata = $this->hmac("",$odata);
        return $this->hmac($okey,$signdata);
    }

    /*
     功能 和包平台签名函数
    */
    private function hmac($key, $data){
        $key = iconv('gb2312', 'utf-8', $key);
        $data = iconv('gb2312', 'utf-8', $data);
        $b = 64;
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;
        return md5($k_opad . pack("H*",md5($k_ipad . $data)));
    }

	/*
		功能：把UTF-8 编号数据转换成 GB2312 忽略转换错误
	*/
	private function decodeUtf8($source)
	{
		$temp = urldecode($source);
		$ret = iconv("UTF-8","GB2312//IGNORE",$temp);
		return $ret;
	}

	private function decodeGB2312($source)
	{
		$temp = urldecode($source);
		$ret = iconv("GB2312","UTF-8//IGNORE",$temp);
		return $ret;
	}

	private function GetGB2312String($name) {
		$tostr = "";
		for($i=0;$i<strlen($name);$i++) {
			$curbin = ord(substr($name,$i,1));
			if($curbin < 0x80) {
				$tostr .= substr($name,$i,1);
			} elseif($curbin < bindec("11000000")) {
				$str = substr($name,$i,1);
				$tostr .= "&#".ord($str).";";
			} elseif($curbin < bindec("11100000")) {
				$str = substr($name,$i,2);
				$tostr .= "&#".$this->GetUnicodeChar($str).";";
				$i += 1;
			} elseif($curbin < bindec("11110000")) {
				$str = substr($name,$i,3);
				$gstr= iconv("UTF-8","GB2312",$str);
				if(!$gstr) {
					$tostr .= "&#".$this->GetUnicodeChar($str).";";
				} else {
					$tostr .= $gstr;
				}
				$i += 2;
			} elseif($curbin < bindec("11111000")) {
				$str = substr($name,$i,4);
				$tostr .= "&#".$this->GetUnicodeChar($str).";";
				$i += 3;
			} elseif($curbin < bindec("11111100")) {
				$str = substr($name,$i,5);
				$tostr .= "&#".$this->GetUnicodeChar($str).";";
				$i += 4;
			} else {
				$str = substr($name,$i,6);
				$tostr .= "&#".$this->GetUnicodeChar($str).";";
				$i += 5;
			}
		}
		return $tostr;
	}
 
	private function GetUnicodeChar($str) {
		$temp = "";
		for($i=0;$i<strlen($str);$i++) {
			$x = decbin(ord(substr($str,$i,1)));
			if($i == 0) {
				$s = strlen($str)+1;
				$temp .= substr($x,$s,8-$s);
			} else {
				$temp .= substr($x,2,6);
			}
		}
		return bindec($temp);
	}















}