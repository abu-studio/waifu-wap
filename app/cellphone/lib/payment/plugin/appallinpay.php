<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

/**
 * @description : 支付
 * @version 1.0
 */
final class cellphone_payment_plugin_appallinpay extends ectools_payment_app implements ectools_interface_payment_app
{

    /**
     * @var string 支付方式名称
     */
    public $name = '通联移动快捷支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '通联移动快捷支付接口';
     /**
     * @var string 支付方式key
     */
    public $app_key = 'appallinpay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'appallinpay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '通联移动快捷支付';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.0';

    public $platform = 'isapp';

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"001");
   // public $platform = 'ismobile';
    /**
     * @description :构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/' .'malipay'. '/malipay_payment_plugin_malipay', 'callback');

        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches))
        {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        }
        else
        {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }

        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/' . 'malipay'. '/malipay_payment_plugin_malipay', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }

        $this->submit_url = 'http://pay.soopay.net/spay/pay/payservice.do';

        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * @description :后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        $msg = "通联支付网络服务股份有限公司（简称'通联支付'）是在国家有关金融主管部门的支持下，由上海国际集团、上海国际信托有限公司以及中国万向集团等机构共同出资设立的一家金融外包与综合支付服务企业，总部位于上海，注册资本金人民币13.6亿元，成立于2008年10月16日。";
        return $msg;
    }

    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting(){
        return array(
                    'pay_name'=>array(
                        'title'=>app::get('ectools')->_('支付方式名称'),
                        'type'=>'string',
						'validate_type' => 'required',
                    ),
                    'store_id'=>array(
                        'title'=>app::get('ectools')->_('商户号'),
                        'type'=>'string',
						'validate_type' => 'required',
                    ),
                    'mer_key'=>array(
                        'title'=>app::get('ectools')->_('交易安全校验码(KEY)'),
                        'type'=>'string',
						'validate_type' => 'required',
                    ),
                    'support_cur'=>array(
                        'title'=>app::get('ectools')->_('支持币种'),
                        'type'=>'text hidden cur',
						'options'=>$this->arrayCurrencyOptions,
                    ),
                    'pay_fee'=>array(
                        'title'=>app::get('ectools')->_('交易费率'),
                        'type'=>'pecentage',
						'validate_type' => 'number',
                    ),
                    'pay_desc'=>array(
                        'title'=>app::get('ectools')->_('描述'),
                        'type'=>'string',
						'includeBase' => true,
                    ),
                    'pay_type'=>array(
						 'title'=>app::get('ectools')->_('支付类型(是否在线支付)'),
						 'type'=>'radio',
						 'name' => 'pay_type',
                         'options'=>array('false'=>app::get('ectools')->_('否'),'true'=>app::get('ectools')->_('是')),
					),
					'status'=>array(
						'title'=>app::get('ectools')->_('是否开启此支付方式'),
						'type'=>'radio',
						'options'=>array('false'=>app::get('ectools')->_('否'),'true'=>app::get('ectools')->_('是')),
						'name' => 'status',
					),
                );
    }

    /**
     * @description :前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
   public function intro(){
        $msg = "通联支付网络服务股份有限公司（简称'通联支付'）是在国家有关金融主管部门的支持下，由上海国际集团、上海国际信托有限公司以及中国万向集团等机构共同出资设立的一家金融外包与综合支付服务企业，总部位于上海，注册资本金人民币13.6亿元，成立于2008年10月16日。";
        return $msg;
   }

    /**
     * @description : 表单提交
     * @param array : $payment
     * @return mixed : false or null
     */
    public function dopay($payment){
        
        $key = $this->getConf('mer_key', __CLASS__); //签名的key
		$merchantId = $this->getConf('store_id', __CLASS__); //商户号
		
		$this->add_field('inputCharset','1');//1代表UTF-8；2代表GBK；3代表GB2312；
		$this->add_field('receiveUrl',$this->callback_url);//通知商户网站支付结果的url地址
		$this->add_field('version','v1.0');//固定选择值：v1.0
		$this->add_field('signType','0');//0表示客户用MD5验签，1表示客户用证书验签
		$this->add_field('merchantId',$merchantId);//商户在通联申请开户的商户号
		
		$this->add_field('orderNo',$payment['payment_id']);//商户订单号(实际是指本系统中的支付单号)
		$this->add_field('orderAmount',ceil($payment['cur_money'] * 100));//如果是人民币，则单位是分，即10元提交时金额应为1000
		$this->add_field('orderCurrency','0');//0和156代表人民币、840代表美元、344代表港币等
		$this->add_field('orderDatetime',date('YmdHis',time()));//日期格式：yyyyMMDDhhmmss，例如：20121116020101
		$this->add_field('productName','夏商网-商品');//商品名称
		
		$this->add_field('ext1','');//英文或中文字符串，支付完成后，按照原样返回给商户
		$this->add_field('payType','0');//固定选择值：0
		
		$this->add_field('signMsg',$this->_get_sign($key));//签名字符串
      
        if($this->is_fields_valiad($key))
		{
		   if(!empty($this->fields)){
        	   return $this->fields;
           }
        }
		else
		{
            return false;
        }
   
    }
    
 /**
     * 生成签名
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return string
     */
    public function _get_sign($key) {

    	$sign = $this->fields;

    	$serverUrl=$sign["serverUrl"];
		$inputCharset=$sign["inputCharset"];
		$pickupUrl=$sign["pickupUrl"];
		$receiveUrl=$sign["receiveUrl"];
		$version=$sign["version"];
		$language=$sign["language"];
		$signType=$sign["signType"];
		$merchantId=$sign["merchantId"];
		$payerName=$sign["payerName"];
		$payerEmail=$sign["payerEmail"];
		$payerTelephone=$sign["payerTelephone"];
		$payerIDCard=$sign["payerIDCard"];
		$pid=$sign["pid"];
		$orderNo=$sign["orderNo"];
		$orderAmount=$sign["orderAmount"];
		$orderDatetime=$sign["orderDatetime"];
		$orderCurrency=$sign["orderCurrency"];
		$orderExpireDatetime=$sign["orderExpireDatetime"];
		$productName=$sign["productName"];
		$productId=$sign["productId"];
		$productPrice=$sign["productPrice"];
		$productNum=$sign["productNum"];
		$productDesc=$sign["productDesc"];
		$ext1=$sign["ext1"];
		$ext2=$sign["ext2"];
		$extTL=$sign["extTL"];
		$payType=$sign["payType"]; //payType   不能为空，必须放在表单中提交。
		$issuerId=$sign["issuerId"]; //issueId 直联时不为空，必须放在表单中提交。
		$pan=$sign["pan"];
		$tradeNature=$sign["tradeNature"];

		// 生成签名字符串。
		$bufSignSrc="";
		if($inputCharset != "")
		$bufSignSrc=$bufSignSrc."inputCharset=".$inputCharset."&";
		if($pickupUrl != "")
		$bufSignSrc=$bufSignSrc."pickupUrl=".$pickupUrl."&";
		if($receiveUrl != "")
		$bufSignSrc=$bufSignSrc."receiveUrl=".$receiveUrl."&";
		if($version != "")
		$bufSignSrc=$bufSignSrc."version=".$version."&";
		if($language != "")
		$bufSignSrc=$bufSignSrc."language=".$language."&";
		if($signType != "")
		$bufSignSrc=$bufSignSrc."signType=".$signType."&";
		if($merchantId != "")
		$bufSignSrc=$bufSignSrc."merchantId=".$merchantId."&";
		if($payerName != "")
		$bufSignSrc=$bufSignSrc."payerName=".$payerName."&";
		if($payerEmail != "")
		$bufSignSrc=$bufSignSrc."payerEmail=".$payerEmail."&";
		if($payerTelephone != "")
		$bufSignSrc=$bufSignSrc."payerTelephone=".$payerTelephone."&";
		if($payerIDCard != "")
		$bufSignSrc=$bufSignSrc."payerIDCard=".$payerIDCard."&";
		if($pid != "")
		$bufSignSrc=$bufSignSrc."pid=".$pid."&";
		if($orderNo != "")
		$bufSignSrc=$bufSignSrc."orderNo=".$orderNo."&";
		if($orderAmount != "")
		$bufSignSrc=$bufSignSrc."orderAmount=".$orderAmount."&";
		if($orderCurrency != "")
		$bufSignSrc=$bufSignSrc."orderCurrency=".$orderCurrency."&";
		if($orderDatetime != "")
		$bufSignSrc=$bufSignSrc."orderDatetime=".$orderDatetime."&";
		if($orderExpireDatetime != "")
		$bufSignSrc=$bufSignSrc."orderExpireDatetime=".$orderExpireDatetime."&";
		if($productName != "")
		$bufSignSrc=$bufSignSrc."productName=".$productName."&";
		if($productPrice != "")
		$bufSignSrc=$bufSignSrc."productPrice=".$productPrice."&";
		if($productNum != "")
		$bufSignSrc=$bufSignSrc."productNum=".$productNum."&";
		if($productId != "")
		$bufSignSrc=$bufSignSrc."productId=".$productId."&";
		if($productDesc != "")
		$bufSignSrc=$bufSignSrc."productDesc=".$productDesc."&";
		if($ext1 != "")
		$bufSignSrc=$bufSignSrc."ext1=".$ext1."&";
		if($ext2 != "")
		$bufSignSrc=$bufSignSrc."ext2=".$ext2."&";
		if($extTL != "")
		$bufSignSrc=$bufSignSrc."extTL".$extTL."&";
		if($payType != "")
		$bufSignSrc=$bufSignSrc."payType=".$payType."&";
		if($issuerId != "")
		$bufSignSrc=$bufSignSrc."issuerId=".$issuerId."&";
		if($pan != "")
		$bufSignSrc=$bufSignSrc."pan=".$pan."&";
		if($tradeNature != "")
		$bufSignSrc=$bufSignSrc."tradeNature=".$tradeNature."&";
		$bufSignSrc=$bufSignSrc."key=".$key; //key为MD5密钥，密钥是在通联支付网关商户服务网站上设置。

		//签名，设为signMsg字段值。
		$signMsg = strtoupper(md5($bufSignSrc));

		return $signMsg;
    }






    /**
     * @description :校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad()
    {
        return true;
    }



    /**
     * @description : 生成支付表单 - 自动提交
     * @params : null
     * @return : null
     */
    public function gen_form(){
          return "";
    }
    
    
/**
	 * 支付后返回后处理的事件的动作
	 * @params array - 所有返回的参数，包括POST和GET
	 * @return null
	 */
    public function callback(&$recv)
	{
        $key = $this->getConf('mer_key', __CLASS__); //签名的key
		$merchantId = $this->getConf('store_id', __CLASS__); //商户号

        if($this->is_ret_vaild($recv,$key)){

            $ret['payment_id'] = $recv['ext1'];
			$ret['account'] = $merchantId;
			$ret['bank'] = app::get('ectools')->_('通联支付');
			$ret['pay_account'] = app::get('ectools')->_('付款帐号');
			$ret['currency'] = 'CNY';
			$ret['money'] = $recv['payAmount']/100;
			$ret['paycost'] = '0.000';
			$ret['cur_money'] = $recv['payAmount']/100;
	        $ret['trade_no'] = $recv['paymentOrderId'];
			$ret['t_payed'] = strtotime($recv['payDatetime']) ? strtotime($recv['payDatetime']) : time();
			$ret['pay_app_id'] = "allinpay";
			$ret['pay_type'] = 'online';
			$ret['memo'] = "";

			if($recv['payResult'] == '1'){
			   $ret['status'] = 'succ';
			}else {
			   $message = $recv['errorCode'];
               $ret['status'] = 'failed';
			}

        }else{
            $message = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }
	}

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_ret_vaild($recv,$key){

    	$merchantId=$recv["merchantId"];
		$version=$recv['version'];
		$language=$recv['language'];
		$signType=$recv['signType'];
		$payType=$recv['payType'];
		$issuerId=$recv['issuerId'];
		$paymentOrderId=$recv['paymentOrderId'];
		$orderNo=$recv['orderNo'];
		$orderDatetime=$recv['orderDatetime'];
		$orderAmount=$recv['orderAmount'];
		$payDatetime=$recv['payDatetime'];
		$payAmount=$recv['payAmount'];
		$ext1=$recv['ext1'];
		$ext2=$recv['ext2'];
		$payResult=$recv['payResult'];
		$errorCode=$recv['errorCode'];
		$returnDatetime=$recv['returnDatetime'];
		$signMsg=$recv["signMsg"];

		$bufSignSrc="";
		if($merchantId != "")
		$bufSignSrc=$bufSignSrc."merchantId=".$merchantId."&";
		if($version != "")
		$bufSignSrc=$bufSignSrc."version=".$version."&";
		if($language != "")
		$bufSignSrc=$bufSignSrc."language=".$language."&";
		if($signType != "")
		$bufSignSrc=$bufSignSrc."signType=".$signType."&";
		if($payType != "")
		$bufSignSrc=$bufSignSrc."payType=".$payType."&";
		if($issuerId != "")
		$bufSignSrc=$bufSignSrc."issuerId=".$issuerId."&";
		if($paymentOrderId != "")
		$bufSignSrc=$bufSignSrc."paymentOrderId=".$paymentOrderId."&";
		if($orderNo != "")
		$bufSignSrc=$bufSignSrc."orderNo=".$orderNo."&";
		if($orderDatetime != "")
		$bufSignSrc=$bufSignSrc."orderDatetime=".$orderDatetime."&";
		if($orderAmount != "")
		$bufSignSrc=$bufSignSrc."orderAmount=".$orderAmount."&";
		if($payDatetime != "")
		$bufSignSrc=$bufSignSrc."payDatetime=".$payDatetime."&";
		if($payAmount != "")
		$bufSignSrc=$bufSignSrc."payAmount=".$payAmount."&";
		if($ext1 != "")
		$bufSignSrc=$bufSignSrc."ext1=".$ext1."&";
		if($ext2 != "")
		$bufSignSrc=$bufSignSrc."ext2=".$ext2."&";
		if($payResult != "")
		$bufSignSrc=$bufSignSrc."payResult=".$payResult."&";
		if($errorCode != "")
		$bufSignSrc=$bufSignSrc."errorCode=".$errorCode."&";
		if($returnDatetime != "")
		$bufSignSrc=$bufSignSrc."returnDatetime=".$returnDatetime;
		$bufSignSrc=$bufSignSrc."&key=".$key;

		if(strtoupper(md5($bufSignSrc)) == $signMsg){
			return true;
		}else{
			return false;
		}

    }


    
    /**
     * 生成RSA签名
     *@param 请求参数组
     *@param 商户私钥路径
     *@return string
     */
    public function sign($data,$private_key_path){
    	$alipay_config = array();
    	$alipayNotify = new malipay_package_alipaynotify($alipay_config);
    	$sign =  $alipayNotify->sign($data,$private_key_path);
    	return $sign;
    }

}