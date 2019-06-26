<?php
/**
 * �ⲿ���ýӿ�ͳһ���õ�api��
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
     * ���췽��
     * @param object ��ǰӦ�õ�app
     * @return null
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /*  �Ͱ�ƽ̨�����ܻ�
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
        �̻��������ѯ�ӿ�(�������Ͱ�ƽ̨)
    */
    private function MercProduceQry(){
        //�������-ǩ���ֶ�: merchantId|jrnNo|signType|type|version|prodNo|requestTime|reserved
        //���ز���-ǩ���ֶ�: merchantId|merchantNo|signType|type|version|returnCode|message|prodNo|prodNum|reserved|
        //�ҷ�ƽ̨���̻������Ψһ��ʶ
		//$this->write_cmpapi_log($Type,"�����������","��ʱ��¼��־��Ϣ");
        $merchantId = $_REQUEST['merchantId'];
        //ƽ̨�����̻��Ľ�����ˮ��
        $jrnNo = $_REQUEST['jrnNo'];
        //ֻ����MD5��RSA
        $signType = $_REQUEST['signType'];
        //MercProduceQry
        $Type = 'MercProduceQry';
        //1.0.0
        $version = $_REQUEST['version'];
        //��Ʒ���
        $prodNo = $_REQUEST['prodNo'];
        //ƽ̨�����̻���ʱ��
        $requestTime = $_REQUEST['requestTime'];
        //�����ֶ�
        $reserved = $_REQUEST['reserved'];
        //ǩ���ֶ�
        $hmac = $_REQUEST['hmac'];
        //������ˮ��
        $merchantNo = date("Ymd").time();
        $prodNum = '0';
        $returnCode = "SUCCESS";
        $message = "SUCCESS";
		$obj_products = app::get("b2c")->model("products");
		$cmpapi = "MercProduceQry";
		if($prodNo != ""){
			$goods_id = $obj_products->dump(array("bn"=>$prodNo),"goods_id");
		}else{
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"�޶�Ӧ��Ʒ��Ϣ");	
			echo "FAILURE";
			return false;
		}
		
        //��ȡ��ƽ̨�Ļ�Ʒ���� start
        $db = kernel::database();
        $sql = "SELECT count(pass.card_pass_id) FROM sdb_cardcoupons_cards_pass AS pass LEFT JOIN sdb_cardcoupons_cards AS card ON pass.card_id = card.card_id WHERE card.goods_id =".$goods_id['goods_id']." AND pass.`status` ='0' AND pass.`type`='virtual' AND pass.`source`='external' AND pass.`disabled`='false' AND pass.`ex_status`='true'";        
		$card_goods = $db->select($sql);
        if($card_goods[0]['count(pass.card_pass_id)']){
            $prodNum = $card_goods[0]['count(pass.card_pass_id)'];
        }
		
		
		if($prodNum == "0"){
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"����Ʒ�޿��");
			echo "FAILURE";
			return false;
		}

        //��ȡ��ƽ̨�Ļ�Ʒ���� end

        $signData = $merchantId.$merchantNo.$signType.$Type.$version.$returnCode.$message.$prodNo.$prodNum.$reserved;
        $hmacl = $this->get_sign($this->signKey,$signData);
        //���ز���-ǩ���ֶ�: merchantId|merchantNo|signType|Type|version|returnCode|message|prodNo|prodNum|reserved|
        echo $hmacl."|".$merchantId."|".$merchantNo."|".$signType."|".$Type."|".$version."|".$returnCode."|".$message."|".$prodNo."|".$prodNum."|".$reserved;

    }


    /*
        ����֧���ӿ���Ϣ(�Ͱ�ƽ̨֧���ɹ��󣬵��øýӿ�--����)
    */
    private function OfflineNotify(){		
        //ǩ���ֶ�
        $hmac = $_REQUEST['hmac'];
        //�̻����
        $merchantId = $_REQUEST['merchantId'];
        //��ˮ��
        $payNo = $_REQUEST['payNo'];
        //������
        $returnCode = $_REQUEST['returnCode'];
        //����������
        $message = $_REQUEST['message'];
        //ǩ����ʽ
        $signType = $_REQUEST['signType'];
        //�ӿ�����
        $type = 'OfflineNotify';
        //�汾��
        $version = $_REQUEST['version'];
        //֧�����
        $amount = $_REQUEST['amount'];
        //�����ϸ
        $amtItem = $_REQUEST['amtItem'];
        //֧������
        $bankAbbr = $_REQUEST['bankAbbr'];
        //֧���ֻ���
        $mobile = $_REQUEST['mobile'];
        //�̻�������
        $orderId = $_REQUEST['orderId'];
        //֧��ʱ��
        $payDate = $_REQUEST['payDate'];
        //�������
        $accountDate = $_REQUEST['accountDate'];
        //�����ֶ�1 ����һ��#�ź͵ڶ���#��ʾ ��Ʒ��������
        $reserved1 = $_REQUEST['reserved1'];
        //�����ֶ�2 (��ʾ ��ַ)
        $reserved2 = $_REQUEST['reserved2'];
        //֧�����
        $status = $_REQUEST['status'];
        //�����ύ����
        $orderDate = $_REQUEST['orderDate'];
        //����
        $fee = $_REQUEST['fee'];
		
		
		$pos = strpos($reserved1,"%23");
	
	if($pos === false){
			$reserved1_array = explode("#",$reserved1);
		}else{
			$reserved1_array = explode("%23",$reserved1);
		}




        $goods_bn = $reserved1_array[0];
        //����ҵ����������ֻ�п�����1
        $nums = $reserved1_array[1];
		$cmp_mobile = $reserved1_array[2];
        //���֧���ص���¼�Ƿ��������־�У��������˵���Ѿ�֧���ɹ�����������ڣ���ִ�� ֧����������ֹ�Ͱ�ƽ̨�����������ظ����ͣ�
        $obj_cmpay_offlinenotify_log = app::get("cardcoupons")->model("cmpay_offlinenotify_log");
		$log_cmpbayapiLog = app::get("cardcoupons")->model("cmpay_log");
		$cmpapi = "OfflineNotify";
        if($payNo == ""){
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"�����ֶ�payNoΪ��");
            echo "FAILURE";
            die();
        }else{	
			//��ȡ�Ͱ�ƽ̨֧����¼
			$log_payno_array = $obj_cmpay_offlinenotify_log->dump(array("payno"=>$payNo),"*");
            if(!empty($log_payno_array)){
				if($log_payno_array['order_id'] && $log_payno_array['is_send'] == "N"){
					$obj_pass =  app::get("cardcoupons")->model("cards_pass");
					$pass_info = $obj_pass->dump(array("order_id"=>$log_payno_array['order_id']),"*");
					if(!empty($pass_info)){
						if($this->MkmMercSendSms($cmp_mobile,$log_payno_array['cmp_order_id'],$pass_info,$log_payno_array['order_id'])){
						//��¼���ͳɹ��� �Ͱ�ƽ̨������Ϣ(��ֹ�Ͱ�ƽ̨���������ɣ���δ�����)
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
				$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"payNo���Ѿ���¼");
                echo "FAILURE";
                die();
            }
        } 
		$obj_products = app::get("b2c")->model("products");
		if($goods_bn != ""){
			$goods_id = $obj_products->dump(array("bn"=>$goods_bn),"goods_id");
		}else{
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"���ȱʧ��Ʒid");
			echo "FAILURE";
			return false;
		}
		//�鿴�����Ϣ
		$db = kernel::database();
        $sql = "SELECT count(pass.card_pass_id) FROM sdb_cardcoupons_cards_pass AS pass LEFT JOIN sdb_cardcoupons_cards AS card ON pass.card_id = card.card_id WHERE card.goods_id =".$goods_id['goods_id']." AND pass.`status` ='0' AND pass.`type`='virtual' AND pass.`source`='external' AND pass.`disabled`='false' AND pass.`ex_status`='true'";        
		$card_goods = $db->select($sql);		
        if(empty($card_goods[0]['count(pass.card_pass_id)']) || $card_goods[0]['count(pass.card_pass_id)'] == "0"){
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"��Ʒ�޿��");
            echo "FAILURE";
			return false;
        }
        $msg = "";
        //����goods_id ����һ������
        $order_id = $this->getSfscOrder($goods_id['goods_id'],1,$cmp_mobile,$msg);
		
        if($order_id == ""){
            echo $msg;
            return false;
        }else{
            //������������Ժ󣬼�¼ ����log
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
       //���ݶ���id�����Ϳ�����Ϣ
        $pass_info = $this->getCardPass($order_id,$msg);
		if(empty($pass_info)){
			$this->write_cmpapi_log($cmpapi,implode('|',$_REQUEST),"��Ʒ�޿��");
			echo "FAILURE";
			die();
		}
		//���ض����������  end
        
		//�̻���ȯ��Ϣά���ӿ�
        if(!$this->MkmCardManage($pass_info,$order_id,$cmp_mobile)){
			echo "FAILURE";           
			//���ش�����Ϣ
            return false;
        }
		
        //���ö���֪ͨ�ӿ�
        if($this->MkmMercSendSms($cmp_mobile,$orderId,$pass_info,$order_id)){		
			//��¼���ͳɹ��� �Ͱ�ƽ̨������Ϣ(��ֹ�Ͱ�ƽ̨���������ɣ���δ�����)
            $obj_cmpay_offlinenotify_log->update(array('is_send'=>'Y'),array('payno'=>$payNo));
			echo "SUCCESS";
			die();
        }else{			
			echo "FAILURE";
			return false;
        }

	}

    /*
        �̻���ȯ��Ϣά���ӿ�(�����������ά���ӿ�)
    */
    private function MkmCardManage($card_info,$order_id,$mobile){
        //�̻����
        $merchantId = $this->merchantId;
        //�̻������ --��������
        $requestId = $order_id;
        //ǩ����ʽ
        $signType = "MD5";
        $type = "MkmCardManage";
        $version = "2.0.0";
        //�������
        $cardNo = "�������:".$card_info['card_no']."������:".$card_info['card_pass'];
        //�̻�����
        $mercNm = "���ƽ̨";
        //�û����ֻ���
        $mobileNo = $mobile;
        //��������
        $cardName = $this->GetGB2312String($card_info['card_name']);
        //1:��Ӱ
        //2:��ʳ
        //3:����
        //4:����
        //5: (�̻���������Լ)
        $cardType = "3";
        //����״̬
        $cardState = "A";
        //������Ч����
        $effectDate = date("Ymd",$card_info['from_time']);
        //����ʧЧ����
        $expireDate = date("Ymd",$card_info['to_time']);
        //��������
        $cardDesc = "�Ͱ�ר��9���Żݡ�";
        //ʹ����֪
        $useDesc = "1��ʹ�ñ��ײͱ�����ǰ5��������ԤԼ��δ��ԤԼֱ�ӳֿ��ź��������������ʹ����Ч��\n\r2�����ײ�ÿ������1�Σ�ԤԼ�󿨺ź����뼴ʱʧЧ�������޸�ԤԼ���ݣ����µ�ԤԼ����400-889-8855��\n\r3�����ź����벻����������ʧ�����һ��ֽ𡢲��˻��������Ʊ��ܡ�\n\r4��ԤԼ��Ч�ڣ��һ�������1����Ч��";
        //��������
        $shareUrl = "��������";
        //�������
        $shareTitle = "�������";
        //��������
        $shareContent = "��������";
        //������ʾ
        $operateFlg = 1;
        //������
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
		//��¼������Ϣ	
		if($code != "MCG00000"){
			$this->write_cmpapi_log($cmpapi,implode('|',$recvArray),$code,"FAILURE","Y");
            return false;
        }else{
			$this->write_cmpapi_log($cmpapi,implode('|',$recvArray),"���ÿ���ά���ӿڳɹ�","SUCCESS","Y");		
            return true;
        }
    }
	
    /*
        ���úͰ�ƽ̨��Ϣ �����Ͷ��Žӿ�
    */
    private function MkmMercSendSms($mobile,$order_id,$pass_info,$payno){
		//�̻����
        $merchantId = $this->merchantId;
        //�̻������
		$requestId = $payno;
		//ǩ����ʽ
        $signType = "MD5";
		//ҵ������
        $type = "MkmMercSendSms";
		//�汾��
        $version = "2.0.0";
		//�̻�������
        $mercordNo = $order_id;
		//�ֻ���
        $mobileNo = $mobile;
		//��������
        $smsNo = "MKM9179";
		$_sjson = array(
			'METHOD'=>'encryptMode',
			'skey'=>$this->signKey,
			'sdata'=>$pass_info['card_name']."|".$pass_info['card_no'].$this->decodeGB2312(",����Ϊ��").$pass_info['card_pass']."|".date("Y-m-d H:i:s",$pass_info['to_time'])."|".$this->decodeGB2312("��л����")."|400-889-8855|",
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
			$this->write_cmpapi_log($cmpapi,implode('|',$recvArray),"���úͰ����ŷ��ͽӿڳɹ�","SUCCESS","Y");
			return true;
        }
    }

    /**
     * getSfscOrder-�����Ͱ�����
     *
     * @param       GOODS_ID,NUMS
     * @return      json�ַ�
     */
    private function getSfscOrder($goods_id,$num = 1,$mobile,&$msg){



        //����:qiyeid��qiyename,goods_id,nums,card_type:virtual-���ӿ�/entity-ʵ�￨ / ʵ��
        $goods_object = kernel::single("b2c_mdl_goods");
        $cards_object = kernel::single("cardcoupons_mdl_cards");
        $products_object = kernel::single("b2c_mdl_products");

        if(!$goods_id || !$num){
            $msg = "����Ʒid";
            return false;
        }
        $products_data = $products_object->dump(array("goods_id"=>$goods_id),'goods_id,product_id');

        if(!$products_data['product_id']){
            $msg = "�޻�Ʒid";
            return false;
        }
        //todo ����������� ֻ�����ⲿ���ӿ���Ϣ
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
				'ship_name'=>$this->decodeGB2312('�Ͱ�ƽ̨�ͻ�'),				
			),
            'cmpay'=>true
        );
        //cmpay
        //���ﴦ��ʼ
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
                    'txt_key'=>'���������ɹ���',
                    'data'=>array(),
                );
                $log_text = serialize($log_text);
            }else{
                $log_text[] = array(
                    'txt_key'=>'��������ʧ�ܣ�',
                    'data'=>array(),
                );
                $log_text = serialize($log_text);
            }
            $orderLog =  kernel::single("b2c_mdl_order_log");
            $sdf_order_log = array(
                'rel_id' => $order_id,
                'op_id' => "",
                'op_name' => $this->decodeGB2312("��̨���ɶ���"),
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
        //ģ��֧������֧���Ͱ�����
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
                    $msg = app::get('b2c')->_('�����Զ�֧��ʧ�ܣ�');
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
        // ���·�����־���
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
            'log_text' => $this->decodeGB2312('�Ͱ����Ӷ����ѷ�������������'),
            'addon' => "",
        );

        $log_id = $objorder_log->save($sdf_order_log);

        //�޸Ķ����ѷ���״̬
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
        //�ύ������Ϣ
        $db->commit($transaction_status);
        return $order_data['order_id'];

    }

    /**
     * getCardPass-���ݶ�����ȡ���ſ�����Ϣ�����úͰ�ƽ̨��
     * @param       order_id,num
     * @return      json�ַ�
     */
    private function getCardPass($order_id,$num = 1,&$msg){
        $order_items_object = kernel::single("b2c_mdl_order_items");
        $cards_object = kernel::single("cardcoupons_mdl_cards");
        $cards_pass_object = kernel::single("cardcoupons_mdl_cards_pass");
        $orders_items_data = $order_items_object->dump(array("order_id"=>$order_id),"goods_id,item_type,nums,amount");
        if(empty($orders_items_data)){
            $msg = "û���ҵ��ö�����";
            return false;
        }
        if($orders_items_data['item_type'] != "virtual"){
            $msg = "���ӿ���ʱֻ֧�ֵ���ȯ��";
            return false;
        }
        $cards_data = $cards_object->dump(array("goods_id"=>$orders_items_data['goods_id'],"source"=>"external"),"card_id");
        if(empty($cards_data)){
            $msg = "�ÿ���δ�ҵ��������Ѿ���ɾ����";
            return false;
        }


        $virtual_pass = $cards_pass_object->getList('*',array('card_id'=>$cards_data['card_id'],'disabled'=>'false','status'=>'0','ex_status'=>'true','type'=>'virtual','source'=>'external'),0,1,"card_no ASC");
        if(!empty($virtual_pass)){
            if($cards_pass_object->update(array('status'=>'1','order_id'=>$order_id),array('card_pass_id'=>$virtual_pass[0]['card_pass_id'],'status'=>'0'))){
                return $virtual_pass[0];
            }else{
                $msg="��ȯ�������治�㣬����ϵ�ͷ�";
                return false;
            }
        }else{
            $msg="��ȯ�������治�㣬����ϵ�ͷ�";
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
     * �õ�ʵ��Ӧ����
     * @params string - �����url
     * @return object - Ӧ��ʵ��
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
    ���� ����HTTP����(�Ͱ�ƽ̨ר��������)
    URL  �����ַ
    data ������������
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
    ���� ��http���󷵻����� ��ʽ��������
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
    ���� �Ͱ�ƽ̨ǩ������
    */
    private function get_sign($okey,$odata){
        $signdata = $this->hmac("",$odata);
        return $this->hmac($okey,$signdata);
    }

    /*
     ���� �Ͱ�ƽ̨ǩ������
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
		���ܣ���UTF-8 �������ת���� GB2312 ����ת������
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