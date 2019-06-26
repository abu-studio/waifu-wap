<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/10
 * Time: 22:29
 */
class b2c_fuyuanwai_orders{

    function __construct($app){
        $this->mdl_orders = app::get('b2c')->model('orders_fyw');
        $this->mdl_order_item = app::get('b2c')->model('order_fyw_items');
        $this->mdl_members = app::get('b2c')->model('members');
    }

    /**
     * 创建订单
     * @param $order_data
     * @return array
     *
     */
    function create_order($order_data){
        $retVal = array();
        $member_no = $order_data['memberNo'];
        if (empty($member_no) || empty($order_data['outTradeNo']) || empty($order_data['subject'])
                ||empty($order_data['totalPointAmt']) || empty($order_data['payType']) || empty($order_data['goodsList'])){
            $retVal['success'] = false;
            $retVal['code'] = '1001';
            $retVal['msg'] = app::get('b2c')->_('参数错误');
            return $retVal;
        }
        $member_id = $this->mdl_members->get_id_by_uname($member_no);

        if(empty($member_id)){
            $member_id = 0;
        // 2017-07-10 需求调整
        //$retVal['success'] = false;
        //$retVal['code'] = '1001';
        //$retVal['msg'] = app::get('b2c')->_('参数错误');
        //return $retVal;
        }
        $fyw_fee = $this->getFywFee($member_no);
        if(empty($fyw_fee)){
            $fyw_fee =1;
        }
        $objMath = kernel::single("ectools_math");
        $total_amount = $objMath->number_multiple(array($order_data['totalPointAmt'],$fyw_fee));
        
        if (empty($total_amount)){
            $total_amount = 0;
        }

        $order_object =  app::get('b2c')->model('orders');
        $order_id = $order_object->gen_id();
        $order_sdf = array('outTradeNo'=>$order_data['outTradeNo'],
                           'subject'=>$order_data['subject'],
                           'totalPointAmt'=>$order_data['totalPointAmt'],
                           'final_amount'=>$total_amount,
                           'payType'=>$order_data['payType'],
                           'order_id'=>$order_id,
                           'fyw_fee'=>$fyw_fee,
                           'member_no'=>$member_no,
                           'member_id'=>$member_id,
                           'order_status'=>1,
                           'createtime'=>time(),
                           );
        //$this->log_rsa(var_export($order_sdf,1));

        foreach($order_data['goodsList'] as $k=>$item){
            $order_item = array(
                'order_id' => $order_id,
                'goodsId' => $item['goodsId'],
                'goodsName' => $item['goodsName'],
                'goodsPointPrice' => $item['goodsPointPrice'],
                'goodsNum' => $item['goodsNum'],
                'goodsImgUrl' => $item['goodsImgUrl'],
                'purchasePrice'=>$item['purchasePrice'],
                'total_amount' => floatval($item['goodsPointPrice']) * intval($item['goodsNum']),
                'fyw_fee'=>$fyw_fee,
                'price'=>ceil ($item['goodsPointPrice']*$fyw_fee),
            );

            $this->mdl_order_item->insert($order_item);
        }

        if(!$this->mdl_orders->save($order_sdf)){

            $retVal['success'] = false;
            $retVal['code'] = '9999';
            $retVal['msg'] = app::get('b2c')->_('系统错误');
            return $retVal;
        }

        $sub_order = array(0=> array('total_amount'=>$total_amount,
                                     'order_id'=>$order_id,
                                     'store_id'=>'福员外',),
                           );
        $pay_order_sdf = array('order_id' => $order_id,
                               'sub_orders' => $sub_order);

        //$this->log_rsa(var_export($pay_order_sdf,1));
        $retVal['success'] = '0';
        if ($this->sfscPay($errMsg,$member_no,$pay_order_sdf  )){
            //支付成功
            //tradeStatus =SUCC

            $pay_callback_sdf = array (
                'order_id' => $order_id,
                'store_id' => '福员外',
                'payment' => 'sfscpay',
                'cur_money' => strval($total_amount),
                'pay_status' => '1',
            );
            if ($this->sfscpaycallback($errMsg,$member_no,$pay_callback_sdf)){
                $retVal['success'] = '1';
                $retVal['result']['tradeStatus'] = 'SUCC';
                $retVal['msg'] = $errMsg;
                //$this->log_rsa(var_export($retVal,1));
                $this->mdl_orders->update(array('tradeStatus'=>'SUCC'),array('order_id'=>$order_id));

            }else{
                $retVal['code'] = '9999';
                $retVal['result']['tradeStatus'] = 'ING';
                $retVal['msg'] = $errMsg;
            }

        }else{
            $retVal['code'] = '9999';
            $retVal['result']['tradeStatus'] = 'FAIL';
            $retVal['msg'] = $errMsg;
            $this->mdl_orders->update(array('tradeStatus'=>'FAIL'),array('order_id'=>$order_id));
        }

        $retVal['result']['outTradeNo'] = $order_data['outTradeNo'];
        $retVal['result']['tradeNo'] = $order_id;

        return $retVal;
    }


    /**
     * 创建订单冲正/退款
     * @param $order_data
     * @return array
     *
     */
    function create_refund($order_data){
        $retVal = array();
        $retVal['success'] = '0';
        $retVal['result']['outTradeNo'] = $order_data['outTradeNo'];
        if (empty($order_data['oriTradeNo']) || empty($order_data['outTradeNo']) || empty($order_data['subject'])
            ||empty($order_data['tradePointAmt']) || empty($order_data['oriTradePointAmt'])){
            $retVal['code'] = '1001';
            $retVal['msg'] = app::get('b2c')->_('参数错误');
            $retVal['result']['tradeStatus'] = 'FAIL';
            return $retVal;
        }
        $oriOrder =  $this->mdl_orders->getRow('*',array('order_id' => $order_data['oriTradeNo']));
        //$this->log_rsa('原来订单',$oriOrder);
        if(empty($oriOrder)){
            $retVal['code'] = '1001';
            $retVal['msg'] = app::get('b2c')->_('参数错误');
            $retVal['result']['tradeStatus'] = 'FAIL';
            return $retVal;
        }
        if ($order_data['tradePointAmt']<$order_data['oriTradePointAmt']){
            //部分退款
            $refund_status = '2';
        }else{
            $refund_status = '1';
        }
        $objMath = kernel::single("ectools_math");
        $total_amount = $objMath->number_multiple(array($order_data['tradePointAmt'],$oriOrder['fyw_fee']));
        
        //$this->log_rsa('实际退款金额 ='.$total_amount);
        if (empty($total_amount)){
            $total_amount = 0;
        }
        $order_object =  app::get('b2c')->model('orders');
        $order_id = $order_object->gen_id();
        $order_sdf = array('order_id'=>$order_id,
                           'outTradeNo'=>$order_data['outTradeNo'],
                           'subject'=>$order_data['subject'],
                           'totalPointAmt'=>$order_data['tradePointAmt'],
                           'final_amount'=>$total_amount,
                           'oriTradeNo'=>$order_data['oriTradeNo'],
                           'oriTradePointAmt'=>$order_data['oriTradePointAmt'],
                           'payType'=>$order_data['payType'],
                           'fyw_fee'=>$oriOrder['fyw_fee'],
                           'member_no'=>$oriOrder['member_no'],
                           'member_id'=>$oriOrder['member_id'],
                           'refund_status'=>$refund_status,
                           'order_status'=>2,
                           'createtime'=>time(),
        );

        foreach($order_data['goodsList'] as $k=>$item){

            $order_item = array(
                'order_id' => $order_id,
                'goodsId' => $item['goodsId'],
                'goodsName' => $item['goodsName'],
                'goodsPointPrice' => $item['goodsPointPrice'],
                'goodsNum' => $item['goodsNum'],
                'fyw_fee'=>$oriOrder['fyw_fee'],
                'price'=>ceil($item['goodsPointPrice']*floatval($oriOrder['fyw_fee'])),
                'goodsImgUrl' => $item['goodsImgUrl'],
                'purchasePrice'=>$item['purchasePrice'],
                'total_amount' => floatval($item['goodsPointPrice']) * intval($item['goodsNum']),
            );
            $this->mdl_order_item->insert($order_item);
        }
        if(!$this->mdl_orders->save($order_sdf)){

            $retVal['success'] = '0';
            $retVal['code'] = '9999';
            $retVal['msg'] = app::get('b2c')->_('系统错误');
            return $retVal;
        }
        $refund_sdf = array('order_id'=>$order_data['oriTradeNo'],
                            'cur_money'=>$total_amount);
        $retVal['success'] = '0';
        //$this->log_rsa('before sfscpayRefund',$refund_sdf);
        if ($this->sfscpayRefund($errMsg,$refund_sdf)){
            $retVal['success'] = '1';
            $retVal['code'] = '0000';
            $retVal['result']['tradeNo'] = $order_id;
            $retVal['result']['tradeStatus'] = 'SUCC';
            $retVal['msg'] = $errMsg;
            //$this->log_rsa('sfscpayRefund true',$retVal);
            $this->mdl_orders->update(array('tradeStatus'=>'SUCC'),array('order_id'=>$order_id));
        }else{
            $retVal['code'] = '9999';
            $retVal['result']['tradeStatus'] = 'FAIL';
            $retVal['msg'] = $errMsg;
            //$this->log_rsa('sfscpayRefund false',$retVal);
            $this->mdl_orders->update(array('tradeStatus'=>'FAIL',),array('order_id'=>$order_id));
        }
        $this->mdl_orders->update(array('local_createtime'=>time()),array('order_id'=>$order_id));

        return $retVal;

    }

    function getFywFee($member_no){

        $mdl_members = app::get('b2c')->model('members');
        $member_id = $mdl_members->get_id_by_uname($member_no);
        if (empty($member_id)){
            return 1;
        }
        $member_lv = $mdl_members->getRow('member_lv_id',array('member_id'=>$member_id));
        $member_lv_id = $member_lv['member_lv_id'];
        $member_lv_object = app::get('b2c')->model('member_lv');
        $member_lv_data = $member_lv_object->dump(array('member_lv_id'=>$member_lv_id));

        return floatval($member_lv_data['fyw_fee']);
    }

    /**
     * 支付
     * @param $errMsg
     * @param $member_no
     * @param $sdf
     * @return bool
     */
    function sfscPay(&$errMsg,$member_no,$sdf){
        $sdf['RELATION_ID'] = $member_no;
        $sdf['METHOD'] = "singlePay";
        if(!$member_no){
            $errMsg .= app::get('b2c')->_('帐号不存在!');
            return false;
        }
        //$this->log_rsa(json_encode($sdf),var_export($sdf,1));
        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
        $arr = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        $arr = SFSC_HttpClient::objectToArray($arr);
        //$this->log_rsa(var_export($arr,1));

        $this->mdl_orders->update(array('local_createtime'=>time()),array('order_id'=>$sdf['order_id']));

        // 10000  失败    10003   余额不足    10001  成功
        if(empty($arr) && !is_numeric($arr['RESULT_CODE'])){
            $errMsg .= app::get('b2c')->_('未知错误，请重新支付');
            return false;
        }elseif($arr['RESULT_CODE'] == 10003){
            $errMsg .= app::get('b2c')->_('福点账户余额不足');
            return false;
        }elseif($arr['RESULT_CODE'] == 10000){
            $errMsg .= app::get('b2c')->_('未知错误，请重新支付');
            return false;
        }elseif($arr['RESULT_CODE'] == 10008){
            $errMsg .= app::get('b2c')->_('重复支付');
            return false;
        }elseif($arr['RESULT_CODE'] == 10001){
            return true;
        }
        $errMsg .= app::get('b2c')->_('未知错误，请重新支付');
        return false;
    }

    /**
     * 支付回调
     * @param $errMsg
     * @param $member_no
     * @param $sdf
     * @return bool
     */
    function sfscpaycallback(&$errMsg,$member_no,$sdf){
        //$this->log_rsa('sfscpaycallback');

        $sdf['RELATION_ID'] = $member_no;
        $sdf['METHOD'] = "singlePayCallBack";
        //$this->log_rsa(var_export($sdf,1));
        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
        $arr = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        $arr = SFSC_HttpClient::objectToArray($arr);

        //$this->log_rsa(var_export($arr,1));
        // 10000  失败   10001  成功
        if(empty($arr) && !is_numeric($arr['RESULT_CODE'])){
            $errMsg = app::get('b2c')->_('未知错误，请重新支付');
            return false;
        }elseif($arr['RESULT_CODE'] == 10000){
            if(!$errMsg) {$errMsg= app::get('b2c')->_('支付失败，请重新支付');}
            return false;
        }elseif($arr['RESULT_CODE'] == 10001){
            return true;
        }
        $errMsg = app::get('b2c')->_('未知错误，请重新支付');
        return false;
    }

    /**
     * 退款
     * @param $errMsg
     * @param $sdf
     * @return bool
     */
    function sfscpayRefund(&$errMsg,$sdf){
        $retVal = false;
        //更换接口  $sdf['METHOD'] = "pointCancel";
        $sdf['METHOD'] = "sectionPointCancel";

        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
        $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        $tmpdata = SFSC_HttpClient::objectToArray($tmpdata);
        //$this->log_rsa('sfsc refund',$tmpdata);

        if(empty($tmpdata) && !is_numeric($tmpdata['RESULT_CODE'])){
            $errMsg = '退款失败';
        }elseif($tmpdata['RESULT_CODE'] == 10000){
            $errMsg = '退款失败';
        }elseif($tmpdata['RESULT_CODE'] == 10001){
            $errMsg = '退款成功';
            $retVal = true;
        }else{
            $errMsg = '退款失败';
        }
        return $retVal;
    }

    /**
     * 获取未结算的订单（普通或者退款）
     * @param $filter
     * @param $type
     */
    function get_bills($filter,$type='balance'){
        $bills_filter = array('is_balance'=>'2',
                              'tradeStatus'=>'SUCC',
                              'order_status|noequal'=>'0');
        if ($filter['start']){
            $bills_filter['createtime|bthan']=$filter['start'];
        }
        if($filter['end']){
            $bills_filter['createtime|sthan']=$filter['end'];
        }
        if ($type ='balance'){
            $bills_filter['order_status']='1';
        }else{
            $bills_filter['order_status']='2';
        }
        $retVal = $this->mdl_orders->getList('*',$bills_filter);
        return $retVal;
    }


    /**
     * 获取该订单对应的未结算退款订单
     * @param $order_id
     */
    function get_refunds($order_id){
        $filter = array('is_balance'=>'2',
                        'order_status'=>'2',
                        'tradeStatus'=>'SUCC',
                        'oriTradeNo'=>$order_id,
                        );
        $retVal = $this->mdl_orders->getList('*',$filter);
        return $retVal;
    }

    /**
     * 获取往期未结算的退款订单
     */
    function get_all_refunds($time_filter){
        $filter = array('is_balance'=>'2',
                        'order_status'=>'2',
                        'tradeStatus'=>'SUCC',
        );
        if($filter['end']){
            $filter['createtime|sthan']=$time_filter['end'];
        }
        $retVal = $this->mdl_orders->getList('*',$filter);
        return $retVal;
    }

    /**
     * 对已结算的订单进行结算状态更新
     * @param $filter
     * @return mixed
     */
    function update_balance($order_id){
        $bills_filter = array('order_id'=>$order_id);
        $retVal = $this->mdl_orders->update(array('is_balance'=>1),$bills_filter);
        return $retVal;
    }


    /**
     * 计算退款单的实际成本
     * @param $refund_id
     * @return array
     */
    function calRefundAccount($refund_id){
        $retVal = array();
        if(empty($refund_id)){
            return $retVal;
        }
        $arr_list= $this->mdl_order_item->getList('*',array('order_id'=>$refund_id));
        $refund_cost = 0;
        foreach($arr_list as $k=>$v){
            if ($v['purchasePrice']){
                $refund_cost += floatval($v['purchasePrice'])* $v['goodsNum'];
            }
        }
        $retVal['refund_cost'] = $refund_cost;
        return $retVal;
    }

    function log_rsa($message,$arrInfo=null) {
        file_put_contents(DATA_DIR . '/api_rsa.log', date('Y-m-d H:i:s',time())."\n\r", FILE_APPEND);
        file_put_contents(DATA_DIR . '/api_rsa.log', $message."\n\r", FILE_APPEND);
        if ($arrInfo){
            file_put_contents(DATA_DIR . '/api_rsa.log', var_export($arrInfo,1)."\n\r", FILE_APPEND);
        }
    }
}