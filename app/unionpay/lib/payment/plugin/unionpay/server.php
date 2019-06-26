<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
/**
 * unionpay callback 后台通知 验证接口
 * @auther zlj
 * @version 1.0
 * @package unionpay.lib.payment.plugin
 */
class unionpay_payment_plugin_unionpay_server extends ectools_payment_app {
    
    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"156");

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id',  substr(__CLASS__, 0, strrpos(__CLASS__, '_')));
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
                $obj_unionpaylogs->inlogs($arra_data, '支付返回-后台通知', 'pay');
            }
        }

        $unionpay = kernel::single('unionpay_payment_plugin_unionpay');
        if($unionpay->verify($recv)){
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

    public function refundcallback(&$recv){
//        error_log(var_export($recv,true),3,DATA_DIR.'/logs/ConfirmData.log');
        //根据refund_id 来查找退款单信息，再调用系统方法。
        $mdl_refunds = app::get('ectools')->model('refunds');
        $refund_info = $mdl_refunds->dump(array('refund_id'=>$recv['reqReserved']),'*');

        //记录请求应答日志
        if($obj_unionpay_logs = kernel::service('unionpay_tools.log')){
            if(method_exists($obj_unionpay_logs,'inlogs')) {
                $obj_order = app::get('ectools')->model('order_bills');
                $order_info = $obj_order->dump(array('bill_id' => $recv['reqReserved']),'rel_id');

                if($recv['respCode'] == '00'){
                    $resp_result = '退款请求成功';
                }else{
                    $resp_result = '退款请求失败：'.$recv['respCode'].'['.$recv['respMsg'].']';
                }

                $back_log_params = http_build_query($recv);
                $arr_data = array(
                    'memo' => $back_log_params,
                    'bill_id' => $recv['reqReserved'],
                    'order_id' => $order_info['rel_id'],
                    'resp_result' => $resp_result
                );
                $obj_unionpay_logs->inlogs($arr_data, '退款返回-后台通知', 'refund');
            }
        }

        $obj_refunds = kernel::single("ectools_refund");
        if($recv['respCode'] == '00'){
            $result = 'success';
        }else{
            $result = $recv['respMsg'];
        }
        $res = $obj_refunds->callback($refund_info,$result,'server');

    }
}
