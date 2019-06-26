<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/13
 * Time: 14:41
 */

class jdsale_checkjdorders{

    public function checkOrders($day = 30){
        //error_log("check 测试时间 ".date('Y-m-d H:i:s')."\n\r",3,ROOT_DIR.'/shaojun.txt');
        $this->cron_log('开始执行本次定时任务',true,'正常开始');
        $current_date  = time()-$day * 86400;

        //导入最新余额明细并保存到数据库
        $jdsale_account_import = kernel::single('jdsale_account_import');
        $jdsale_account_import->importBalanceDetail();

        //每天获取京东第三方订单的信息 //12点之后
        $this->getJdOrderInfo($day);

        //订单对账 //若订单有拆单，需获取子订单信息
        $this->checkAccount();

        //检查售后的退货退款
        $this->checkAfsRefund($current_date);
        $this->cron_log('结束执行本次定时任务',true,'正常结束');
    }


    function manual_checkorders($orders){
        $this->cron_log('开始执行本次手动任务',true,'正常开始');

        $mdl_jdorders =  app::get('jdsale')->model('jdorders');
        $check_jdorders = $mdl_jdorders->getList('*',array('order_id'=>$orders));
        $this->checkAccount($check_jdorders);

        $this->cron_log('结束执行本次手动任务',true,'正常结束');
    }

    //每天获取京东第三方订单的信息
    private function getJdOrderInfo($day) {


        $this->getJdOrderInfoByType('newOrder',$day);
        $this->getJdOrderInfoByType('checkDlok',$day);
        $this->getJdOrderInfoByType('checkRefuse',$day);

    }

    private function getJdOrderInfoByType($type,$day){

        for( $d =$day;$d>0;$d--){
            $current_date  = date("Y-m-d", time()-$d*86400);
            $jdsale_api_checkorder = kernel::single('jdsale_api_checkorder');
            $result = array();
            if ($type =='newOrder'){
                $result = $jdsale_api_checkorder->checkNewOrder(array('date' => $current_date,'page' =>1));
            }elseif($type =='checkDlok'){
                $result = $jdsale_api_checkorder->checkDlokOrder(array('date' => $current_date,'page' =>1));
            }elseif($type =='checkRefuse'){
                $result = $jdsale_api_checkorder->checkRefuseOrder(array('date' => $current_date,'page' =>1));
            }

            if ($result['result']){
                //save to db
                $this->updateJdOrderState($result['result']);

                $totalPage = $result['result']['totalPage'];
                if ($totalPage>1){
                    $curPage = $result['result']['curPage']+1;
                    for($i=$curPage;$i<=$totalPage;$i++){
                        if ($type =='newOrder'){
                            $result_next = $jdsale_api_checkorder->checkNewOrder(
                                array('date' => $current_date,'page' =>$i));
                        }elseif($type =='checkDlok'){
                            $result_next = $jdsale_api_checkorder->checkDlokOrder(
                                array('date' => $current_date,'page' =>$i));
                        }elseif($type =='checkRefuse'){
                            $result_next = $jdsale_api_checkorder->checkRefuseOrder(
                                array('date' => $current_date,'page' =>$i));
                        }
                        $this->updateJdOrderState($result_next['result']);
                    }
                }
            }
        }

    }

    //保存京东订单的最新状态信息
    private function updateJdOrderState($result){
        $mdl_jdorders =  app::get('jdsale')->model('jdorders');
        $mdl_jd_suborders =  app::get('jdsale')->model('jd_suborders');
        foreach($result['orders'] as $k=>$v){
            $jdOrderId = $v['jdOrderId'];
            $jd_suborders = $mdl_jd_suborders->getRow('jd_order_id',array('jd_suborder_id'=>$jdOrderId));
            if ($jd_suborders){
                $jdOrderId = $jd_suborders['jd_order_id'];
            }
            $mdl_jdorders->update(array('order_state' => $v['state']), array('jdorders_id'=>$jdOrderId));
        }
    }


    //订单对账
    private function checkAccount($check_jdorders=array()){
		$mdl_orders =  app::get('b2c')->model('orders');
        if (empty($check_jdorders)){
            //检查已经支付，并提交京东审核通过的订单
            $mdl_jdorders =  app::get('jdsale')->model('jdorders');
            $filter = array(
                'jdstatus' => 'ypaid',
                'order_kind' => 'jdorder',
                'check_status' => 0,
            );

            $check_jdorders = $mdl_jdorders->getList('*',$filter);
        }
        foreach ( $check_jdorders as $item) {
            $jdsale_api_order = kernel::single('jdsale_api_orders');
            $jdorder_info = $jdsale_api_order->getOrderJdOrder(array('jdOrderId'=>$item['jdorders_id']));
			$orderData = $mdl_orders->getRow('final_amount',array('order_id'=>$item['order_id']));
            if (empty($jdorder_info['result']['pOrder'])){
                $sub_order = false;
                $jdorder_result = $jdorder_info['result'];
            }else{
                $sub_order = true;
                $jdorder_result = $jdorder_info['result']['pOrder'];
            }
            $jd_order_price = $jdorder_result['orderPrice'];
			$freight =  $jdorder_result['freight'];
            if (empty($jd_order_price)){
                $jd_order_price = 0;
            }
            if (empty($jdorder_result)){
                $check_status = 2;
                $check_info = '京东无此订单信息';
                $this->cron_log('订单对账',false,'本地订单号：'.$item['order_id'].',京东订单号：'.$item['jdorders_id']);
            }else if($jdorder_result['orderState'] === 0 ){
                $check_status = 2;
                $check_info = '该订单已审核通过，被京东强制取消';
			}else if($orderData['final_amount']<((float)$jd_order_price+(float)$freight)){
				$check_status = 2;
                $check_info = '京东订单价格大于本地订单价格';
                $this->cron_log('订单对账',false,'本地订单号：'.$item['order_id'].',京东订单号：'.$item['jdorders_id']);
            }else if ($jdorder_result['orderPrice']>0){
                $check_status = 1;
                $check_info = '';
                $this->cron_log('订单对账',true,'本地订单号：'.$item['order_id'].',京东订单号：'.$item['jdorders_id']);
            }
            $this->saveCheckStatus($item['order_id'],(float)$jd_order_price+(float)$freight,$check_status,$check_info);

            if ($sub_order){
                $this->getSubOrderInfo($jdorder_info['result']['cOrder'],$item);
            }
        }
    }

    //保存对账状态和异常信息
    private function saveCheckStatus($order_id,$jd_order_price,$check_status,$check_info){
        $mdl_jdorders =  app::get('jdsale')->model('jdorders');
        $jdorders = array(
            'check_status' => $check_status,
            'check_info' => $check_info,
            'check_time' => time(),
            'jd_order_price' => $jd_order_price,
        );

        return $mdl_jdorders->update($jdorders,array('order_id' => $order_id));
    }

    //检查售后的退货退款
    private function checkAfsRefund($current_date){
        $mdl_balance = app::get('jdsale')->model('balance');

        //获取退款信息
        $result = $mdl_balance->getList('*',array('order_kind'=>'jdorder' ,'trade_type' =>'416' ,'create_time|than' =>$current_date));
        if(empty($result)){
            return;
        }
        //$this->log($result);
        $jd_refund = array();
        foreach($result as $k=>$v){
            $note_pub= $v['note_pub'];
            $note_pub_1 = explode(',',$note_pub);
            $temp = array();
            foreach($note_pub_1 as $k2=>$v2){
                $note_pub_2= explode(':',$v2);
                if ($note_pub_2[0] === '退货返款'){
                    $temp['jdorder_id'] = $note_pub_2[2];
                }
                if ($note_pub_2[0] === '服务单'){
                    $temp['service_no'] = $note_pub_2[1];
                }
                if ($note_pub_2[0] === '商品编号'){
                    $temp['sku'] = $note_pub_2[1];
                }
                if ($note_pub_2[0] === '退款金额'){
                    $temp['amount'] = $note_pub_2[1];
                }
            }
            $jd_refund[] = $temp;

        }
        $mdl_afs_log = app::get('jdsale')->model('afs_log');
        $mdl_goods = app::get('b2c')->model('goods');
        $mdl_jdorders =  app::get('jdsale')->model('jdorders');
        foreach($jd_refund as $k=>$v){
            $jd_order_id = $v['jdorder_id'];
            $agreed_price = $mdl_goods->getRow('agreed_price',array('bn|tequal'=>$v['sku']));
            $num = $v['amount']/$agreed_price['agreed_price'];

            $filter = array('sku'=>$v['sku'],
                            'result'=>'SUCCESS',
                            'refund_status'=>'1',
                            'apply_num'=>$num);
            $jd_order_row = $mdl_jdorders->count(array('jdorders_id' =>$jd_order_id));
            if ($jd_order_row > 0){
                $filter['jd_order_id']= $jd_order_id;
            }else{
                $filter['jd_suborder_id']= $jd_order_id;
            }

            $afs_log = $mdl_afs_log->getRow('*',$filter);
            if ($afs_log){

                if($this->doWithAfsRefund($afs_log)){
                    $this->cron_log('退货退款',true,'本地订单号：'.$afs_log['order_id'].',京东订单号：'.$afs_log['jd_order_id']
                    .' 京东服务单号'.$v['service_no']);
                    //$this->log('京东订单号'.$v['jd_order_id'].' 京东服务单号'.$v['service_no'].' '.$msg);
                    $filter2= array('log_id'=>$afs_log['log_id']);
                    $afs_log_new = array('refund_status'=>'2');
                    $mdl_afs_log->update($afs_log_new,$filter2);
                }else{
                    $this->cron_log('退货退款',false,'本地订单号：'.$afs_log['order_id'].',京东订单号：'.$afs_log['jd_order_id']
                    .' 京东服务单号'.$v['service_no']);
                }
            }
        }
    }

    private function doWithAfsRefund($afs_log){

        $retVal = false;
        $return_id = $afs_log['return_id'];
        if (empty($return_id)){
            $msg = '无退货记录流水号';
            return $retVal;
        }
        $rp = app::get('aftersales')->model('return_product');
        $obj_order = app::get('b2c')->model('orders');

        //加载数据库类
        $db = kernel::database();
        //开始一个事务处理
        $transaction_status = $db->beginTransaction();
        //根据订单id  获取订单信息
            $returns = $rp->getRow('*',array('return_id'=>$return_id));
        $obj_return_policy = kernel::single('aftersales_data_return_policy');
            $sdf = array(
                'return_id' => $return_id,
                'status' => '6',
            );

        $obj_return_policy->change_status($sdf);
        $obj_aftersales = kernel::servicelist("api.aftersales.request");
        foreach ($obj_aftersales as $obj_request)
        {
            $obj_request->send_update_request($sdf);
        }

        //生成退款单开始
        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $sdf_order = $obj_order->dump($sdf['order_id'],'*',$subsdf);

        $sdf['money'] = $returns['amount'];

        $refunds = app::get('ectools')->model('refunds');

        $sdf['op_id'] = $returns['member_id'];
        $o_account = app::get('pam')->model('account');
        $uname = $o_account->dump($returns['member_id']);
        $sdf['op_name'] = $uname['login_name'];

        unset($sdf['inContent']);

        $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');
        $sdf['payment'] = ($sdf['payment']) ? $sdf['payment'] : $sdf_order['payinfo']['pay_app_id'];

        $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);

        $time = time();
        $sdf['refund_id'] = $refund_id = $refunds->gen_id();
        $sdf['pay_app_id'] = $sdf['payment'];
        $sdf['member_id'] = $sdf_order['member_id'] ? $sdf_order['member_id'] : 0;

        $obj_members = app::get('pam')->model('account');
        $buy_name = $obj_members->getRow('login_name',array('account_id'=>$sdf['member_id']));
        $sdf['account'] = $buy_name['login_name'];

        $sdf['currency'] = $sdf_order['currency'];
        $sdf['paycost'] = 0;
        $sdf['cur_money'] = $sdf['money'];
        $sdf['t_begin'] = $time;
        $sdf['t_payed'] = $time;
        $sdf['t_confirm'] = $time;
        $sdf['pay_object'] = 'order';
        $sdf['op_id'] = $returns['member_id'];
        $sdf['status'] = 'ready';
        $sdf['app_name'] = $arrPaymentInfo['app_name'];
        $sdf['app_version'] = $arrPaymentInfo['app_version'];
        $sdf['refund_type'] = '1';
        $sdf['is_safeguard'] = $returns['is_safeguard'];
        $sdf['seller_amount'] = $returns['seller_amount'];

        $msg = '';
        if (!$obj_checkorder->check_order_refund($sdf['order_id'],$sdf,$msg))
        {
            $db->rollback();
            //return $msg;
            return $retVal;
        }
        //生成退款单end
        $obj_refunds = kernel::single("ectools_refund");

        //订单是否生成的重要依据
        $rs_buyer = $obj_refunds->generate($sdf, $this, $msg);

        $obj_bills = app::get('ectools')->model('order_bills');
        $rel_id = $obj_bills->getRow('rel_id',array('bill_id'=>$refund_id));
        $payment_id = $refunds->get_payment($rel_id['rel_id']);
        $obj_payment = app::get('ectools')->model('payments');
        $cur_money = $obj_payment->dump($payment_id['bill_id'],'*');


        //退款
        $refund_data = $refunds->getRow('*',array('refund_id'=>$sdf['refund_id']));

        //组装数据
        $score = 0;
        $time = $sdf_order['confirm_time'];
        $sdf['return_score'] = 0;
        $refund_status = array('refund_status'=>'4','confirm_time'=>$time,'score_g'=>$score);
        $Log = array('behavior'=>'agreereturn','result'=>'SUCCESS','log_text'=>'京东同意退款');

        $is_refund_before = false;
        $obj_refund_lists = kernel::servicelist("order.refund_finish");
        foreach ($obj_refund_lists as $order_refund_service_object)
        {
            $is_refund_before = $order_refund_service_object->refund_finish_before($sdf,$refund_status,$Log);
        }

        if($is_refund_before){
            //3
            if($refund_data['pay_app_id'] != 'deposit' && $refund_data['pay_app_id'] != 'sfscpay'){
                if($refund_data['cur_money'] == 0){
                    $ref_rs = $obj_refunds->generate_after($sdf);
                }else{
                    //$refund_data['payment_info'] = $cur_money;
                    $refund_data = $obj_refunds->make_data($refund_data);
                    if($refund_data){
                        foreach($refund_data as $key=>$re_data){
                            $result = $obj_refunds->dorefund($re_data,$this);
                            $ref_rs = $obj_refunds->callback($re_data,$result);
                        }
                    }
                }
            }else{
                //预存款
                $ref_rs = $obj_refunds->generate_after($sdf);
            }
        }

        $aUpdate['order_id'] = $returns['order_id'];
        $obj_order->fireEvent('returned', $aUpdate, $sdf_order['member_id']);

        if ($rs_buyer){
            //添加退款接口   10001 成功 10000 失败
            if($sdf['pay_app_id']=='sfscpay'){
                $sdf['METHOD'] = "pointCancel";
                $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
                $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
                $tmpdata = SFSC_HttpClient::objectToArray($tmpdata);
                if(empty($tmpdata) && !is_numeric($tmpdata['RESULT_CODE'])){
                    $db->rollback();
                    $msg = '退款失败';
                }elseif($tmpdata['RESULT_CODE'] == 10000){
                    $db->rollback();
                    $msg = '退款失败';
                }elseif($tmpdata['RESULT_CODE'] == 10001){
                    $db->commit($transaction_status);
                    $msg = '退款成功';
                    $retVal = true;
                }else{
                    $db->rollback();
                    $msg = '退款失败';
                }
            }else{
                if($ref_rs){
                    $db->commit($transaction_status);
                    $msg = '退款成功';
                    $retVal = true;
                }else{
                    $db->rollback();
                    $msg = '退款失败';
                }
            }
        }
        else{
            $db->rollback();
            $msg = '退款失败';
        }
        return $retVal;

    }

    //若订单有拆单，需获取子订单信息
    private function getSubOrderInfo($cOrder,$jdorders){
        $mdl_jd_suborders =  app::get('jdsale')->model('jd_suborders');
        foreach($cOrder as $k1=>$v1){

            foreach( $v1['sku'] as $k2=>$v2){
                $sub_order = array('order_id' =>$jdorders['order_id'],
                                   'jd_order_id' =>$jdorders['jdorders_id'],
                                   'jd_suborder_id' =>$v1['jdOrderId'],
                                   'sku_id' =>$v2['skuId'],
                                   'sku_num' =>$v2['num'],
                                   'order_kind' =>'jdorder',
                                   'createtime' =>time(),
                );
                $mdl_jd_suborders->save($sub_order);
            }
        }

    }

    private function log($msg){

        error_log(var_export($msg,1)."\n\r",3,ROOT_DIR.'/shaojun.txt');
    }

    private function cron_log($function_name,$success,$result=''){
        $log = array('cron_name'=>'京东订单对账定时任务',
                     'function_name'=>$function_name,
                     'success'=>$success,
                     'result'=>$result,
                     'createtime'=>time());
        $mdl_cron_log = app::get('jdsale')->model('cron_log');
        $mdl_cron_log->save($log);
    }
}