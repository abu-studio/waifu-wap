<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

/**
 * @description 中国银联在线支付Unionpay接口
 * @auther zlj
 * @version 1.0
 * @package unionpay.lib.payment.plugin
 * @date 2014-3-13
 */
final class unionpay_payment_plugin_unionpay extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '中国银联在线支付Unionpay';

    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '中国银联在线支付Unionpay接口';

    /**
     * @var string 支付方式key
     */
    public $app_key = 'unionpay';

    /**
     * @var string 中心化统一的key 
     */
    public $app_rpc_key = 'unionpay';

    /**
     * @var string 统一显示的名称
     */
    public $display_name = '中国银联在线支付Unionpay';

    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';

    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.0';

    /**
     * @var string 当前支付方式所支持的平台
     */
    public $platform = 'ispc';

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"156");

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);

        $this->server_callback_url = kernel::openapi_url('openapi.ectools_payment/parse/' . $this->app->app_id . '/unionpay_payment_plugin_unionpay_server', 'callback');//支付后台通知地址

        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->server_callback_url, $matches)){
            $this->server_callback_url = str_replace('http://','',$this->server_callback_url);
            $this->server_callback_url = preg_replace("|/+|","/", $this->server_callback_url);
            $this->server_callback_url = "http://" . $this->server_callback_url;
        }else{
            $this->server_callback_url = str_replace('https://','',$this->server_callback_url);
            $this->server_callback_url = preg_replace("|/+|","/", $this->server_callback_url);
            $this->server_callback_url = "https://" . $this->server_callback_url;
        }

        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/' . $this->app->app_id . '/unionpay_payment_plugin_unionpay', 'callback');//支付前台通知地址

        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)){
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        }else{
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }

        $this->server_refundcallback_url = kernel::openapi_url('openapi.ectools_payment/parse/' . $this->app->app_id . '/unionpay_payment_plugin_unionpay_server', 'refundcallback');//退款后台通知地址

        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->server_refundcallback_url, $matches)){
            $this->server_refundcallback_url = str_replace('http://','',$this->server_refundcallback_url);
            $this->server_refundcallback_url = preg_replace("|/+|","/", $this->server_refundcallback_url);
            $this->server_refundcallback_url = "http://" . $this->server_refundcallback_url;
        }else{
            $this->server_refundcallback_url = str_replace('https://','',$this->server_refundcallback_url);
            $this->server_refundcallback_url = preg_replace("|/+|","/", $this->server_refundcallback_url);
            $this->server_refundcallback_url = "https://" . $this->server_refundcallback_url;
        }

        $PubPk = $this->getConf('pub_Pk', __CLASS__);//公钥证书
        if (file_exists(DATA_DIR . '/cert/payment_plugin_unionpay/' . $PubPk)){
            $this->MPI_ENCRYPT_CERT_PATH = str_replace('\\','/',DATA_DIR) . '/cert/payment_plugin_unionpay/' . $PubPk;//加密公钥证书路径
        }

        $MerPrk = $this->getConf('mer_key', __CLASS__);//私钥证书
        if (file_exists(DATA_DIR . '/cert/payment_plugin_unionpay/' . $MerPrk)){
            $this->MPI_SIGN_CERT_PATH = str_replace('\\','/',DATA_DIR) . '/cert/payment_plugin_unionpay/' . $MerPrk;//私钥证书路径
        }

        $this->MerPwd = $this->getConf('mer_pwd', __CLASS__);//私钥密码
        $this->MPI_VERIFY_CERT_DIR = str_replace('\\','/',DATA_DIR) . '/cert/payment_plugin_unionpay/';//证书目录

//        $this->submit_url = "https://202.101.25.181/gateway/api/frontTransRequest.do";//前台交易测试环境
//        if(defined('UNIONPAY_DOPAY')){
            $this->submit_url = 'https://unionpaysecure.com/gateway/api/frontTransRequest.do';//前台交易正式环境
//        }

//        $this->submit_backurl = "https://202.101.25.181/gateway/api/backTransRequest.do";//后台交易测试地址
//        if(defined('UNIONPAY_DOREFUND')){
            $this->submit_backurl = 'https://unionpaysecure.com/gateway/api/backTransRequest.do';//后台交易正式环境
//        }

        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return app::get('unionpay')->_('中国银联在线支付(Unionpay)是由中国银联联合各商业银行共同打造的银行卡网上交易转接清算平台，涵盖多种支付方式无需开通网银，即可实现以互联网等新兴渠道为基础的在线支付。');
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
            'mer_id'=>array(
                'title'=>app::get('unionpay')->_('商户号'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pub_Pk'=>array(
                'title'=>app::get('unionpay')->_('公钥证书'),
                'type'=>'file',
            ),
            'mer_key'=>array(
                'title'=>app::get('unionpay')->_('私钥证书'),
                'type'=>'file',
            ),
            'mer_pwd'=>array(
                'title'=>app::get('unionpay')->_('私钥密码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pay_fee'=>array(
                'title'=>app::get('ectools')->_('交易费率'),
                'type'=>'pecentage',
                'validate_type' => 'number',
            ),
            'support_cur'=>array(
                'title'=>app::get('ectools')->_('支持币种'),
                'type'=>'text hidden cur',
                'options'=>$this->arrayCurrencyOptions,
            ),
            'pay_desc'=>array(
                'title'=>app::get('ectools')->_('描述'),
                'type'=>'html',
                'includeBase' => true,
            ),
            'pay_type'=>array(
                 'title'=>app::get('ectools')->_('支付类型(是否在线支付)'),
                 'type' => 'select',
                 'name' => 'pay_type',
                 'options' => array('true' => '在线支付'),
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
     * 前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function intro(){
        return app::get('unionpay')->_('中国银联在线支付(Unionpay)是由中国银联联合各商业银行共同打造的银行卡网上交易转接清算平台，涵盖多种支付方式无需开通网银，即可实现以互联网等新兴渠道为基础的在线支付。');
    }

    public function dopay($payment){
        $certId = $this->getSignCertId();                              //证书ID
        $frontUrl = $this->callback_url;                               //前台通知地址 前台返回商户结果时使用
        $backUrl = $this->server_callback_url;                         //后台通知地址 异步通知地址
        $mer_id = $this->getConf('mer_id', __CLASS__);                 //商户代码
        $orderId = $payment['rel_id'];                                 //订单id
        $txnTime = $payment['t_begin'] ? $payment['t_begin'] : time(); //订单发送时间
        $txnTime = date('Ymdhms',$txnTime);
        $txnAmt = $payment['money']*100;                               //交易金额
        $encryptCertId = $this->getEncryptCertId();                    //加密证书ID

        $params = array(
            'currencyCode' => '156',                //交易币种
            'version' => '3.0.0',                   //版本
            'encoding' => 'UTF-8',                  //编码
            'certId' => $certId,                    //证书id
            'txnType' => '01',                      //交易类型 01：消费
            'txnSubType' => '01',                   //交易子类 默认
            'bizType' => '000201',                  //业务类型 B2C网关支付
            'frontUrl' => $frontUrl,                //前台通知地址
            'backUrl' => $backUrl,                  //后台通知地址
            'accessType' => '0',                    //接入类型 0：商户直连接入 1：收单机构接入
            'merType' => '0',                       //商户类型 0：普通商户 1：平台类商户
            'merId' => $mer_id,                     //商户代码
            'orderId' => $payment['payment_id'],                  //订单号
            'txnTime' => $txnTime,                  //订单发送时间
            'txnAmt' => $txnAmt,                    //交易金额
            'reqReserved' => $orderId,              //指商户自定义保留域，交易应答时会原样返回
            'encryptCertId' => $encryptCertId,      //加密证书ID
        );

        $this->encrypt_params($params);             //检查字段是否需要加密
        $this->sign($params);                       //签名

        if(isset($params['signflag']) && ($params['signflag'] == 'signfailed')){
            header("Content-Type:text/html;charset=utf-8");
            echo '签名失败！';
            exit;
        }
        $this->add_field('currencyCode', '156');
        $this->add_field('version', '3.0.0');
        $this->add_field('encoding', 'UTF-8');
        $this->add_field('certId', $certId);
        $this->add_field('txnType', '01');
        $this->add_field('txnSubType', '01');
        $this->add_field('bizType', '000201');
        $this->add_field('frontUrl', $frontUrl);
        $this->add_field('backUrl', $backUrl);
        $this->add_field('accessType', '0');
        $this->add_field('merType', '0');
        $this->add_field('merId', $mer_id);
        $this->add_field('orderId', $payment['payment_id']);
        $this->add_field('txnTime', $txnTime);
        $this->add_field('txnAmt', $txnAmt);
        $this->add_field('reqReserved', $orderId);
        $this->add_field('encryptCertId', $encryptCertId);
        $this->add_field('signature', $params['signature']);

        //记录请求日志
        if($obj_unionpaylogs = kernel::service('unionpay_tools.log')){
            if(method_exists($obj_unionpaylogs,'inlogs')) {
                $log_params = http_build_query($this->fields);
                $arra_data =array(
                    'memo' => $log_params,
                    'bill_id' => $payment['payment_id'],
                    'order_id' => $orderId,
                    'resp_result' => '将要请求支付'
                );
                $obj_unionpaylogs->inlogs($arra_data, '支付请求', 'pay');
            }
        }

        if($this->is_fields_valiad()){
            echo $this->get_html();exit;
        }else{
            return false;
        }
    }

    public function dorefund($refund){
        $certId = $this->getSignCertId();
        $refund_back_url = $this->server_refundcallback_url;
        $merId = $this->getConf('mer_id', __CLASS__);
        $refundId = $refund['refund_id'];
        $queryId = $refund['payment_info']['trade_no'];
        $txnTime = date('YmdHis',$refund['t_begin']);
        $txnAmt = $refund['cur_money']*100;

        $params = array(
            'version' => '3.0.0',                           //版本号
            'encoding' => 'UTF-8',                          //编码方式
            'certId' => $certId,                            //证书ID
            'txnType' => '04',                              //交易类型  
            'txnSubType' => '01',                           //交易子类
            'bizType' => '000000',                          //业务类型
            'backUrl' => $refund_back_url,                  //后台通知地址                  
            'accessType' => '0',                            //接入类型
            'merType' => '0',                               //商户类型
            'merId' => $merId,                              //商户代码
            'orderId' => $refundId,                         //商户订单号        传退款单号
            'origQryId' => $queryId,                        //原始交易流水号
            'txnTime' => $txnTime,                          //订单发送时间
            'txnAmt' => $txnAmt,                            //交易金额
            'reqReserved' => $refundId                      //请求方保留域
        );

        $this->sign($params);
        if(isset($params['signflag']) && ($params['signflag'] == 'signfailed')){
            header("Content-Type:text/html;charset=utf-8");
            echo '签名失败！';
            exit;
        }

        //记录请求日志
        if($obj_unionpaylogs = kernel::service('unionpay_tools.log')){
            if(method_exists($obj_unionpaylogs,'inlogs')) {
                $mdl_order = app::get('ectools')->model('order_bills');
                $order = $mdl_order->dump(array('bill_id' => $refundId),'rel_id');
                $log_params = http_build_query($params);
                $arra_data =array(
                    'memo' => $log_params,
                    'bill_id' => $refundId,
                    'order_id' => $order['rel_id'],
                    'resp_result' => '将要请求退款'
                );
                $obj_unionpaylogs->inlogs($arra_data, '退款请求', 'refund');
            }
        }

        $result = $this->sendHttpRequest( $params, $this->submit_backurl );
        $result_arr = $this->coverStringToArray ( $result );//返回结果展示

        //记录请求应答日志
        if($obj_unionpay_logs = kernel::service('unionpay_tools.log')){
            if(method_exists($obj_unionpay_logs,'inlogs')) {
                $obj_order = app::get('ectools')->model('order_bills');
                $order_info = $obj_order->dump(array('bill_id' => $result_arr['reqReserved']),'rel_id');

                if($result_arr['respCode'] == '00'){
                    $resp_result = '退款请求成功';
                }else{
                    $resp_result = '退款请求失败：'.$result_arr['respCode'].'['.$result_arr['respMsg'].']';
                }

                $back_log_params = http_build_query($result_arr);
                $arr_data = array(
                    'memo' => $back_log_params,
                    'bill_id' => $result_arr['reqReserved'],
                    'order_id' => $order_info['rel_id'],
                    'resp_result' => $resp_result
                );
                $obj_unionpay_logs->inlogs($arr_data, '退款返回', 'refund');
            }
        }

        if($result_arr['respCode'] == '00'){
            $ret = 'success';
        }else{
            $ret = $result_arr['respMsg'];
        }

        return $ret;
    }

    /**
     * 签名证书ID
     *
     * @return unknown
     */
    function getSignCertId() {
        // 签名证书路径(私钥证书路径)
        return $this->getCertId ( $this->MPI_SIGN_CERT_PATH );
    }

    /**
     * 取证书ID(.pfx 私钥)
     *
     * @return unknown
     */
    function getCertId($cert_path) {
        $pkcs12certdata = file_get_contents ( $cert_path );
        openssl_pkcs12_read ( $pkcs12certdata, $certs, $this->MerPwd );
        $x509data = $certs ['cert'];
        openssl_x509_read ( $x509data );
        $certdata = openssl_x509_parse ( $x509data );
        $cert_id = $certdata ['serialNumber'];
        return $cert_id;
    }

    /**
     * 加密证书ID
     *
     * @return unknown
     */
    function getEncryptCertId() {
        // 签名证书路径(公钥证书路径)
        return $this->getCertIdByCerPath ( $this->MPI_ENCRYPT_CERT_PATH );
    }

    /**
     * 对卡号 | cvn2 | 密码 | cvn2有效期进行处理
     *
     * @param array $params
     */
    function encrypt_params(&$params) {
        // 卡号
        $pan = isset ( $params ['accNo'] ) ? $params ['accNo'] : '';

        if(!empty($pan)){
//            if (1 === MPI_PAN_ENC) {//规定对卡号进行加密 modified by zlj 2014-3-27 16:27:31
            $cryptPan = $this->encryptPan ( $pan );
            $params ['accNo'] = $cryptPan;
//            }
        }

        // 证件类型
        $customerInfo01 = isset ( $params ['customerInfo01'] ) ? $params ['customerInfo01'] : '';
        // 证件号码
        $customerInfo02 = isset ( $params ['customerInfo02'] ) ? $params ['customerInfo02'] : '';
        // 姓名
        $customerInfo03 = isset ( $params ['customerInfo03'] ) ? $params ['customerInfo03'] : '';
        // 手机号
        $customerInfo04 = isset ( $params ['customerInfo04'] ) ? $params ['customerInfo04'] : '';
        // 短信验证码
        $customerInfo05 = isset ( $params ['customerInfo05'] ) ? $params ['customerInfo05'] : '';
        // 持卡人密码
        $customerInfo06 = isset ( $params ['customerInfo06'] ) ? $params ['customerInfo06'] : '';
        // cvn2
        $customerInfo07 = isset ( $params ['customerInfo07'] ) ? $params ['customerInfo07'] : '';
        // 有效期
        $customerInfo08 = isset ( $params ['customerInfo08'] ) ? $params ['customerInfo08'] : '';
        
        // 去除身份信息域
        for($i = 1; $i <= 8; $i ++) {
            if (isset ( $params ['customerInfo0' . $i] )) {
                unset ( $params ['customerInfo0' . $i] );
            }
        }
        
        // 如果子域都是空则退出
        if (empty ( $customerInfo01 ) && empty ( $customerInfo02 ) && empty ( $customerInfo03 ) && empty ( $customerInfo04 ) && empty ( $customerInfo05 ) && empty ( $customerInfo06 ) && empty ( $customerInfo07 ) && isset ( $customerInfo08 )) {
            return (- 1);
        }
        
        // 持卡人身份信息 --证件类型|证件号码|姓名|手机号|短信验证码|持卡人密码|CVN2|有效期
        $customer_info = '{';
        $customer_info .= $customerInfo01 . '|';
        $customer_info .= $customerInfo02 . '|';
        $customer_info .= $customerInfo03 . '|';
        $customer_info .= $customerInfo04 . '|';
        $customer_info .= $customerInfo05 . '|';
        
        if (! empty ( $customerInfo06 )) {
            if (! empty ( $pan )) {
                $encrypt_pin = $this->encryptPin ( $pan, $customerInfo06 );
                $customer_info .= $encrypt_pin . '|';
            } else {
                $customer_info .= $customerInfo06 . '|';
            }
        } else {
            $customer_info .= '|';
        }
        
        if (! empty ( $customerInfo07 )) {
//            if (1 == MPI_CVN2_ENC) {//规定对CVN2进行加密 modified by zlj 2014-3-28 10:54:27
            $cvn2 = $this->encryptCvn2 ( $customerInfo07 );
            $customer_info .= $cvn2 . '|';
//            } else {
//                $customer_info .= $customerInfo07 . '|';
//            }
        } else {
            $customer_info .= '|';
        }
        
        if (! empty ( $customerInfo08 )) {
//            if (1 == MPI_DATE_ENC) {//规定对有效期进行加密 modified by zlj 2014-3-28 10:55:29
            $certDate = $this->encryptDate ( $customerInfo08 );
            $customer_info .= $cvn2;
//            } else {
//                $customer_info .= $customerInfo08;
//            }
        }
        
        $customer_info .= '}';
        
        $customerInfoBase64 = base64_encode ( $customer_info );
        $params ['customerInfo'] = $customerInfoBase64;
    }

    /**
     * 签名
     *
     * @param String $params_str
     */
    function sign(&$params) {
        if(isset($params['transTempUrl'])){
            unset($params['transTempUrl']);
        }

        // 转换成key=val&串
        $params_str = $this->coverParamsToString ( $params );
        $params_sha1x16 = sha1 ( $params_str, FALSE );

        // 签名证书路径
        $cert_path = $this->MPI_SIGN_CERT_PATH;
        $private_key = $this->getPrivateKey ( $cert_path );

        // 签名
        $sign_falg = openssl_sign ( $params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1 );

        if ($sign_falg) {
            $signature_base64 = base64_encode ( $signature );
            $params['signature'] = $signature_base64;
        } else {
            $params['signflag'] = 'signfailed';
        }
    }

    public function is_fields_valiad(){
        return true;
    }

    public function callback(&$recv){
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', __CLASS__);
        $ret['payment_id'] = $recv['orderId'];
        $ret['account'] = $mer_id;
        $ret['bank'] = app::get('unionpay')->_('中国银联在线支付Unionpay');
        $ret['currency'] = array_search($recv["settleCurrencyCode"], $this->supportCurrency);
        $ret['money'] = intval($recv['txnAmt'])/100;
        $ret['paycost'] = '0.000';
        $ret['cur_money'] = intval($recv['settleAmt'])/100;
        $ret['trade_no'] = $recv['queryId'];
        $ret['t_payed'] = time();
        $ret['pay_app_id'] = "unionpay";
        $ret['pay_type'] = 'online';
        $ret['memo'] = $recv['respMsg'];

        //记录请求应答日志
        if($obj_unionpaylogs = kernel::service('unionpay_tools.log')){
            if(method_exists($obj_unionpaylogs,'inlogs')) {
                if($recv['respCode'] == '00'){
                    $resp_result = '支付请求成功';
                }else{
                    $resp_result = '支付请求失败：'.$recv['respCode'].'['.$recv['respMsg'].']';
                }

                $log_params = http_build_query($recv);
                $arra_data =array(
                    'memo' => $log_params,
                    'bill_id' => $recv['orderId'],
                    'order_id' => $recv['reqReserved'],
                    'resp_result' => $resp_result
                );
                $obj_unionpaylogs->inlogs($arra_data, '支付返回-前台通知', 'pay');
            }
        }

        if($this->verify($recv)){
            if ($recv['respCode'] == "00"){
                $message = "支付成功！";
                $ret['status'] = 'succ';
            }else{
                $message = "支付失败！";
                $ret['status'] = 'failed';
            }
        }else{
            $message = "验证签名错误！";
            $ret['status'] = 'invalid';
        }

        return $ret;
    }

    /**
     * 字符串转换为 数组
     *
     * @param unknown_type $str         
     * @return multitype:unknown
     */
    function coverStringToArray($str) {
        $result = array ();
        if (! empty ( $str )) {
            $temp = preg_split ( '/&/', $str );
            if (! empty ( $temp )) {
                foreach ( $temp as $key => $val ) {
                    $arr = preg_split ( '/=/', $val, 2 );
                    if (! empty ( $arr )) {
                        $k = $arr ['0'];
                        $v = $arr ['1'];
                        $result [$k] = $v;
                    }
                }
            }
        }
        return $result;
    }


    /**
     * 后台交易 HttpClient通信
     * @param unknown_type $params
     * @param unknown_type $url
     * @return mixed
     */
    function sendHttpRequest($params, $url) {
        $opts = $this->getRequestParamString ( $params );
        
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false);//不验证证书
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false);//不验证HOST
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
                'Content-type:application/x-www-form-urlencoded;charset=UTF-8' 
        ) );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $opts );
        
        /**
         * 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
         */
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        
        // 运行cURL，请求网页
        $html = curl_exec ( $ch );
        // close cURL resource, and free up system resources
        curl_close ( $ch );
        return $html;
    }

    /**
     * 组装报文
     *
     * @param unknown_type $params          
     * @return string
     */
    function getRequestParamString($params) {
        $params_str = '';
        foreach ( $params as $key => $value ) {
            $params_str .= ($key . '=' . (!isset ( $value ) ? '' : urlencode( $value )) . '&');
        }
        return substr ( $params_str, 0, strlen ( $params_str ) - 1 );
    }

    /**
     * 验签
     *
     * @param String $params_str            
     * @param String $signature_str         
     */
    function verify($params) {
        // 公钥
        $public_key = $this->getPulbicKeyByCertId( $params ['certId'] );
        // 签名串
        $signature_str = $params ['signature'];
        unset ( $params ['signature'] );

        $params_str = $this->coverParamsToString ( $params );
        $signature = base64_decode( $signature_str );

        $params_sha1x16 = sha1( $params_str, FALSE );
        $isSuccess = openssl_verify( $params_sha1x16, $signature, $public_key, OPENSSL_ALGO_SHA1 );
        return $isSuccess;
    }

    /**
     * 根据证书ID 加载 证书
     *
     * @param unknown_type $certId          
     * @return string NULL
     */
    function getPulbicKeyByCertId($certId) {
        // 证书目录
        $cert_dir = $this->MPI_ENCRYPT_CERT_PATH;
        $cert_id = $this->getCertIdByCerPath ( $cert_dir );

        if ($cert_id == $certId) {
            return $this->getPublicKey ( $cert_dir );
        }

        return null;
    }

    public function gen_form(){}

    /**
     * 截取相应长度和本身字符串长度的差额对应的字符串
     * @param string 被截取字符串
     * @param int 长度
     */
    private function intString($intvalue,$len){
        $intstr = strval($intvalue);
        for ($i = 1; $i <= $len-strlen($intstr); $i++){
            $tmpstr .= "0";
        }

        return $tmpstr . $intstr;
    }

    /**
     * 取证书ID(.cer 公钥)
     *
     * @param unknown_type $cert_path
     */
    function getCertIdByCerPath($cert_path) {
        $x509data = file_get_contents ( $cert_path );
        openssl_x509_read ( $x509data );
        $certdata = openssl_x509_parse ( $x509data );

        $cert_id = $certdata ['serialNumber'];
        return $cert_id;
    }

    /**
     * pin 加密
     *
     * @param unknown_type $pan         
     * @param unknown_type $pwd         
     * @return Ambigous <number, string>
     */
    function encryptPin($pan, $pwd) {
        $cert_path = $this->MPI_ENCRYPT_CERT_PATH;
        $public_key = $this->getPublicKey ( $cert_path );
        
        return $this->EncryptedPin ( $pwd, $pan, $public_key );
    }

    function EncryptedPin($sPin, $sCardNo ,$sPubKeyURL){
        $sPubKeyURL = trim($this->MPI_ENCRYPT_CERT_PATH," ");
        $fp = fopen($sPubKeyURL, "r");

        if ($fp != NULL){
            $sCrt = fread($fp, 8192);
            fclose($fp);
        }

        $sPubCrt = openssl_x509_read($sCrt);
        if ($sPubCrt === FALSE){
            print("openssl_x509_read in false!");
            return (-1);
        }

        $sPubKeyId = openssl_x509_parse($sCrt);
        $sPubKey = openssl_x509_parse($sPubCrt);
        openssl_x509_free($sPubCrt);
//      print_r(openssl_get_publickey($sCrt));
    
        $sInput = $this->Pin2PinBlockWithCardNO($sPin, $sCardNo);
        if ($sInput == 1){
            print("Pin2PinBlockWithCardNO Error ! : " . $sInput);
            return (1);
        }

        $iRet = openssl_public_encrypt($sInput, $sOutData, $sCrt, OPENSSL_PKCS1_PADDING);
        if ($iRet === TRUE){
            $sBase64EncodeOutData = base64_encode($sOutData);       
            //print("PayerPin : " . $sBase64EncodeOutData);
            return $sBase64EncodeOutData;
        }else{
            print("openssl_public_encrypt Error !");
            return (-1);
        }
    }

    function Pin2PinBlockWithCardNO(&$sPin, &$sCardNO){
        $sPinBuf = $this->Pin2PinBlock($sPin);
        $iCardLen = strlen($sCardNO);

        if ($iCardLen <= 10){
            return (1);
        }elseif ($iCardLen==11){
            $sCardNO = "00" . $sCardNO;
        }elseif ($iCardLen==12){
            $sCardNO = "0" . $sCardNO;
        }

        $sPanBuf = $this->FormatPan($sCardNO);
        $sBuf = array();
        
        for ($i=0; $i<8; $i++){
            $sBuf[$i] = vsprintf("%c", ($sPinBuf[$i] ^ $sPanBuf[$i]));
        }

        unset($sPinBuf);
        unset($sPanBuf);

        $sOutput = implode("", $sBuf);  //数组转换为字符串
        return $sOutput;
    }

    /**
     * Author: gu_yongkang 
     * data: 20110510
     * 密码转PIN 
     * Enter description here ...
     * @param $spin
     */
    function Pin2PinBlock( &$sPin ){
    //  $sPin = "123456";
        $iTemp = 1;
        $sPinLen = strlen($sPin);
        $sBuf = array();

        //密码域大于10位
        $sBuf[0]=intval($sPinLen, 10);
    
        if($sPinLen % 2 ==0){
            for ($i=0; $i<$sPinLen;){
                $tBuf = substr($sPin, $i, 2);
                $sBuf[$iTemp] = intval($tBuf, 16);
                unset($tBuf);

                if ($i == ($sPinLen - 2)){
                    if ($iTemp < 7){
                        $t = 0;
                        for ($t=($iTemp+1); $t<8; $t++){
                            $sBuf[$t] = 0xff;
                        }
                    }
                }

                $iTemp++;
                $i = $i + 2;    //linshi
            }
        }else{
            for ($i=0; $i<$sPinLen;){
                if ($i ==($sPinLen-1)){
                    $mBuf = substr($sPin, $i, 1) . "f";
                    $sBuf[$iTemp] = intval($mBuf, 16);
                    unset($mBuf);

                    if (($iTemp)<7){
                        $t = 0;
                        for ($t=($iTemp+1); $t<8; $t++){
                            $sBuf[$t] = 0xff;
                        }
                    }
                }else {
                    $tBuf = substr($sPin, $i, 2);
                    $sBuf[$iTemp] = intval($tBuf, 16);
                    unset($tBuf);
                }

                $iTemp++;
                $i = $i + 2;
            }
        }

        return $sBuf;
    }

    /**
     * Author: gu_yongkang
     * data: 20110510
     * Enter description here ...
     * @param $sPan
     */
    function FormatPan(&$sPan){
        $iPanLen = strlen($sPan);
        $iTemp = $iPanLen - 13;

        $sBuf = array();
        $sBuf[0] = 0x00;
        $sBuf[1] = 0x00;

        for ($i=2; $i<8; $i++){
            $tBuf = substr($sPan, $iTemp, 2);
            $sBuf[$i] = intval($tBuf, 16);
            $iTemp = $iTemp + 2;        
        }

        return $sBuf;
    }

    /**
     * 加密 卡号
     *
     * @param String $pan
     *          卡号
     * @return String
     */
    function encryptPan($pan) {
        $cert_path = $this->MPI_ENCRYPT_CERT_PATH;
        $public_key = $this->getPublicKey ( $cert_path );
        
        openssl_public_encrypt ( $pan, $cryptPan, $public_key );
        return base64_encode ( $cryptPan );
    }

    /**
     * cvn2 加密
     *
     * @param unknown_type $cvn2            
     * @return unknown
     */
    function encryptCvn2($cvn2) {
        $cert_path = $this->MPI_ENCRYPT_CERT_PATH;
        $public_key = $this->getPublicKey ( $cert_path );
        
        openssl_public_encrypt ( $cvn2, $crypted, $public_key );
        
        return base64_encode ( $crypted );
    }

    /**
     * 加密 有效期
     *
     * @param unknown_type $certDate            
     * @return unknown
     */
    function encryptDate($certDate) {
        $cert_path = $this->MPI_ENCRYPT_CERT_PATH;
        $public_key = $this->getPublicKey ( $cert_path );
        
        openssl_public_encrypt ( $certDate, $crypted, $public_key );
        
        return base64_encode ( $crypted );
    }

    /**
     * 取证书公钥 -验签
     *
     * @return string
     */
    function getPublicKey($cert_path) {
        return file_get_contents ( $cert_path );
    }

    /**
     * 数组 排序后转化为字符串
     *
     * @param array $params         
     * @return string
     */
    function coverParamsToString($params) {
        $sign_str = '';
        // 排序
        ksort ( $params );
        foreach ( $params as $key => $val ) {
            if ($key == 'signature') {
                continue;
            }
            $sign_str .= sprintf ( "%s=%s&", $key, $val );
            // $sign_str .= $key . '=' . $val . '&';
        }
        return substr ( $sign_str, 0, strlen ( $sign_str ) - 1 );
    }

    /**
     * 返回(签名)证书私钥 -
     *
     * @return unknown
     */
    function getPrivateKey($cert_path) {
        $pkcs12 = file_get_contents ( $cert_path );
        openssl_pkcs12_read ( $pkcs12, $certs, $this->MerPwd );
        return $certs ['pkey'];
    }


}

?>