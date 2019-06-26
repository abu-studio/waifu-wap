<?php
/**
 * 取消订单
 */

class jdsale_canceljdorders{

    function cancelJdorder($order_id){
        // $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        // if (!$obj_checkorder->check_order_cancel($order_id,'',$message))
        // {
        //    echo json_encode($message);
        //    exit;
        // }
        $db = kernel::database();
        $transaction_status = $db->beginTransaction();
        $sdf['order_id'] = $order_id;
        $orderMdl= app::get('b2c')->model('orders');
        $sdf['opname'] = 'system';
        $b2c_order_cancel = kernel::single("b2c_order_cancel");
        if ($b2c_order_cancel->generate($sdf,$orderMdl,$msg))
        {
            //ajx crm
            $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
            $req_arr['order_id']=$order_id;
            $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');

            $orderObj = app::get('b2c')->model('orders');
            $orderItemObj = app::get('b2c')->model('order_items');
            $order_info = $orderObj->dump(array('order_id'=>$order_id),'act_id,order_type,itemnum');
            switch($order_info['order_type']){
                case 'group':
                    $buyMod = app::get('groupbuy')->model('memberbuy');
                    $applyObj = app::get('groupbuy')->model('groupapply');
                    $apply = $applyObj->dump(array('id'=>$order_info['act_id']),'aid,gid,remainnums,nums');
                    if($apply){
                      $buyMod->update(array('effective'=>'false'),array('order_id'=>$order_id));
                    }
                    break;
                case 'spike':
                    $buyMod = app::get('spike')->model('memberbuy');
                    $applyObj = app::get('spike')->model('spikeapply');
                    $apply = $applyObj->dump(array('id'=>$order_info['act_id']),'aid,gid,remainnums,nums');
                    if($apply){
                      $buyMod->update(array('effective'=>'false'),array('order_id'=>$order_id));
                    }
                    break;
                case 'score':
                    $buyMod = app::get('scorebuy')->model('memberbuy');
                    $applyObj = app::get('scorebuy')->model('scoreapply');
                    $apply = $applyObj->dump(array('id'=>$order_info['act_id']),'aid,gid,remainnums,nums');
                    if($apply){
                      $buyMod->update(array('effective'=>'false'),array('order_id'=>$order_id));
                    }
                    break;
                case 'timedbuy':
                    $buyMod = app::get('timedbuy')->model('memberbuy');
                    $businessMod = app::get('timedbuy')->model('businessactivity');
                    $buys = $buyMod->getList('*',array('order_id'=>$order_id));
                    if($buys){
                      $business = $businessMod->getList('*',array('gid'=>$buys[0]['gid'],'aid'=>$buys[0]['aid']));
                      $buyMod->update(array('disable'=>'true'),array('order_id'=>$order_id));
                      if($business[0]['nums']){
                          $arr['remainnums'] = intval($business[0]['remainnums'])+intval($buys[0]['nums']);
                          $businessMod->update($arr,array('id'=>$business[0]['id']));
                      }
                    }
                    break;
            }
            //组合支付调用
            $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
            $order_saved = $orderObj->dump($order_id, '*', $subsdf);
            if(strpos($order_saved['payinfo']['pay_app_id'],'sfscpay')!==false){
                $objPay = kernel::single('ectools_pay');
                $check = $objPay->get_payment_by_order($order_id,'sfscpay','sfsc_freeze');
                //组合支付订单取消回调接口-hy
                $arr_callback = array(
                    'order_id' => $order_id,
                    'store_id' => $order_saved['store_id'],
                    'payment' => $order_saved['payinfo']['pay_app_id'],
                    'cur_money' => $check['cur_money'],
                    'pay_status' => $order_saved['pay_status'],
                );
                //调用JAVA端
                $obj_mem = app::get('b2c')->model('members');
                $tmp = $obj_mem->get_member_info($order_saved['member_id']);
                $arr_callback['RELATION_ID'] = $tmp['uname'];
                $arr_callback['METHOD'] = "singlePayCallBack";
                if(!$tmp['uname']){
                    error_log("=-=-=-=-=帐号不存在-=-=-=-".PHP_EOL.var_export($order_saved['member_id'],1).'帐号不存在'.PHP_EOL,3,DATA_DIR."/cancel_order_for_jdbalance.log");
                    return false;
                }
                $inputParam = json_encode($arr_callback);
                $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>$inputParam);
                if(! empty($arr_callback['cur_money'])){
                    $arr = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
                    $arr = SFSC_HttpClient::objectToArray($arr);
                    if($arr['RESULT_CODE'] != 10000){
                        $db->rollback();
                        error_log("=-=-=-=-=退回福点失败-=-=-=-".PHP_EOL.var_export($post_data,1).'退回福点失败'.PHP_EOL,3,DATA_DIR."/cancel_order_for_jdbalance.log");
                        exit;
                    }
                }

                if(isset($check['payment_id'])){
                    kernel::single('ectools_mdl_payments')->update(array('status'=>'cancel'),array('payment_id'=>$check['payment_id']));
                }
            }
            //end
            $db->commit($transaction_status);
            error_log("=-=-=-=-=订单取消成功-=-=-=-".PHP_EOL.var_export($order_id,1).PHP_EOL,3,DATA_DIR."/cancel_order_for_jdbalance.log");
        }
    }
}