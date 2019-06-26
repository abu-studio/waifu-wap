<?php
class order_status_cycle{
    static function boot(){
        if(!self::register_autoload()){
            require(ROOT_DIR . '/app/base/autoload.php');
        }

        $cycleList = self::get_cycle_orders();
        foreach ($cycleList as $cycleItem) {
            $merge_payment_id = trim($cycleItem['merge_payment_id']);
            if(empty($merge_payment_id)){
                self::single_order_action($cycleItem);
            }else{
                self::mutil_order_action($cycleItem);
            }
        }
    }

    static function single_order_action($cycleItem){
        echo "single_order_action\n";
        $paymentId = $cycleItem['payment_id'];
        $orderId = $cycleItem['order_id'];
        $paymentType = $cycleItem['payment'];
        $time_begin = $cycleItem['t_begin_pay'];

        $rdpStatus =  self::order_status_from_rdp($orderId);
        if(false !== $rdpStatus){
            $rdpStatus = $rdpStatus['RESULT_DATA'][0]['PAY_STATUS'];
        }

        if('I03003' == $rdpStatus){
            //支付完成
            self::set_cycle_status($paymentId);
            self::order_set_success($orderId , $paymentType);
            return true;
        }else if('I03004' == $rdpStatus){
            //支付失败
            self::set_cycle_status($paymentId);
            return true;
        }else if('I03002' == $rdpStatus){
            //冻结成功 扣款
            self::sfsccallback($orderId , false);
            return true;
        }
        //未支付情况下， 不能处理，可能是会员用户，没有点击支付按钮

        return false;
    }

    static function mutil_order_action($cycleItem){
        echo "mutil_order_action\n";
        $paymentId = $cycleItem['payment_id'];
        $paymentType = $cycleItem['payment'];
        $time_begin = $cycleItem['t_begin_pay'];
        $merge_payment_id = trim($cycleItem['merge_payment_id']);
        $orderIdsList =  self::get_sub_orderids($merge_payment_id);

        $rdpStatus =  self::entirety_order_status_from_rdp($orderIdsList);
        if('I03003' == $rdpStatus){
            //支付完成
            self::set_cycle_status($paymentId);
            self::entirety_order_set_success($orderIdsList , $paymentType);
            return true;
        }else if('I03004' == $rdpStatus){
            //支付失败
            self::set_cycle_status($paymentId);
            return true;
        }else if('I03002' == $rdpStatus){
            //冻结成功 扣款
            self::entirety_sfsccallback($orderIdsList , false);
            return true;
        }
        //未支付情况下， 不能处理，可能是会员用户，没有点击支付按钮

        return false;
    }

    //拉取所有 待处理订单
    static function get_cycle_orders(){
        $db = kernel::database();
        // select bill_type , bill_id as payment_id, money from sdb_ectools_order_bills  where rel_id = '2015041022491570' and bill_type='payments' and pay_object = 'order' limit 3;
        // select  payment_id , merge_payment_id from sdb_ectools_payments where merge_payment_id limit 30;
        $sql = "update sdb_ectools_order_status_cycle cycle,sdb_b2c_orders orders,sdb_ectools_payments payments set cycle.cycle_status='2' where cycle.cycle_status='0' and cycle.order_id=orders.order_id and (orders.pay_status='1' or orders.status not in('active' , 'ready')) and cycle.payment_id = payments.payment_id and payments.merge_payment_id=''";
        echo "get_cycle_orders:\t".$sql."\n";
        $db->exec($sql);

        $sql = "select distinct cycle.payment_id,cycle.order_id,cycle.t_begin_pay,orders.payment,payments.merge_payment_id from sdb_ectools_order_status_cycle cycle inner join sdb_b2c_orders orders on cycle.order_id = orders.order_id left join sdb_ectools_payments payments on cycle.payment_id=payments.merge_payment_id  where cycle.cycle_status='0' and cycle.payment_id";
        echo "get_cycle_orders:\t".$sql."\n";
        $cycleList = $db->select($sql);
        if(empty($cycleList)){
            echo "暂无异常订单需要轮循\n";
            exit;
        }

        return $cycleList;
    }

    //设置待处理状态
    static function set_cycle_status($paymentId){
        $db = kernel::database();

        $sql = "update sdb_ectools_order_status_cycle set cycle_status = '2' where payment_id='$paymentId'";
        echo "set_cycle_status\t".$sql."\n";
        $db->exec($sql);
        $db->commit(true);
    }

    //判断 是否超时
    static function is_timeout($timeStart){
        echo "is_timeout\n";
        $timeout = 10*60;
        $timeNow = time();
        $timeDiff = $timeNow - $timeStart;

        return ($timeDiff>$timeout);
    }

    //将订单更新为成功状态
    // final_amount=payed and refund_status='0'
    static function order_set_success($orderId , $paymentType='sfscpay'){
        $db = kernel::database();
        // select order_id,final_amount,pay_status,status,payed from sdb_b2c_orders limit 50; //

        $rs = false;
        if('sfscpay' == $paymentType){
            $sql = "update sdb_b2c_orders set pay_status = '1',status='active',payed=final_amount where order_id='$orderId' and refund_status='0'";
        }else{
            //对于 联合方式 支付 的 这里更新为 部分支付成功
            $sql = "update sdb_b2c_orders set pay_status = '3',status='active' where order_id='$orderId' and refund_status='0' and pay_status='0'";
            $rs = $db->exec($sql);

            $sql = "update sdb_b2c_orders set pay_status = '1',status='active' where order_id='$orderId' and final_amount=payed and refund_status='0'";

        }
        echo "order_set_success\t".$sql."\n";
        $rs = $rs || $db->exec($sql);

        if(false == $rs){
            return false;
        }

        $sql="select bill.bill_id,bill.money,pay.payment_id,pay.money,pay.member_id,pay.`status` from sdb_ectools_order_bills bill  inner join sdb_ectools_payments pay on bill.bill_id = pay.payment_id where bill.rel_id = '$orderId' and pay.pay_name=' 福点支付 Yoofuu Pay' order by pay.t_begin desc";
        $paymentsItem = $db->selectrow($sql);
        if(empty($paymentsItem['payment_id'])){
            return false;
        }

        $sql="update sdb_ectools_payments set status='succ' where payment_id='{$paymentsItem['payment_id']}'";
        $db->exec($sql);

        $db->commit(true);
    }

    //批量更新订单状态为 成功
    static function entirety_order_set_success($orderIdsList , $paymentType='sfscpay'){
        $db = kernel::database();
        // select order_id,final_amount,pay_status,status,payed from sdb_b2c_orders limit 50; //

        $orderIdStr = implode("," , $orderIdsList);
        $rs = false;
        if('sfscpay' == $paymentType){
            $sql = "update sdb_b2c_orders set pay_status = '1',status='active',payed=final_amount where order_id in ($orderIdStr) and refund_status='0'";
        }else{
            //对于 联合方式 支付 的 这里更新为 部分支付成功
            $sql = "update sdb_b2c_orders set pay_status = '3',status='active' where order_id in ($orderIdStr) and refund_status='0' and pay_status='0'";
            $rs = $db->exec($sql);

            $sql = "update sdb_b2c_orders set pay_status = '1',status='active' where order_id in ($orderIdStr) and final_amount=payed and refund_status='0'";
        }
        $rs = $rs || $db->exec($sql);
        if(false == $rs){
            return false;
        }

        $sql="select max(pay.payment_id) payment_id from sdb_ectools_order_bills bill  inner join sdb_ectools_payments pay on bill.bill_id = pay.payment_id where bill.rel_id in ($orderIdStr) and pay.pay_name=' 福点支付 Yoofuu Pay' group by bill.rel_id";
        $paymentsList = $db->select($sql);
        if(empty($paymentsList)){
            return false;
        }

        $newPaymentIdList = self::array_column($paymentsList , 'payment_id');
        $newPaymentIdStr = implode("," , $newPaymentIdList);
        $sql="update sdb_ectools_payments set status='succ' where payment_id in ($newPaymentIdStr)";
        $db->exec($sql);

        echo "entirety_order_set_success\t".$sql."\n";
        $db->commit(true);
    }

    //更新订单为失败状态
    static function order_set_failure($orderId){
        $db = kernel::database();
        // select order_id,final_amount,pay_status,status,payed from sdb_b2c_orders limit 50; //

        $sql = "update sdb_b2c_orders set pay_status = '0',status='dead',payed='0' where order_id='$orderId' and pay_status = '0'";
        echo "order_set_failure\t".$sql."\n";
        $db->exec($sql);
        $db->commit(true);
    }

    //读取完整订单数据
    static function get_current_orders($orderIdsList){
        // select order_id,member_id,store_id,payment,final_amount cur_money,pay_status from sdb_b2c_orders
        // select account_id,account_type,login_name from sdb_pam_account order by account_id desc limit 50;
        $db = kernel::database();
        if(! is_array($orderIdsList)){
            $sql = "select orders.order_id,orders.member_id,orders.store_id,orders.payment,orders.final_amount cur_money,orders.pay_status,account.login_name RELATION_ID from sdb_b2c_orders orders inner join sdb_pam_account account on orders.member_id=account.account_id where orders.order_id = '$orderIdsList'";
        }else{
            $orderIdsList = implode("," , $orderIdsList);
            $sql = "select orders.order_id,orders.member_id,orders.store_id,orders.payment,orders.final_amount cur_money,orders.pay_status,account.login_name RELATION_ID from sdb_b2c_orders orders inner join sdb_pam_account account on orders.member_id=account.account_id where orders.order_id in ($orderIdsList)";
        }

        echo "get_current_orders\t" . $sql . "\n";
        $ordersList = $db->select($sql);
        if(empty($ordersList)){
            return false;
        }

        return $ordersList;
    }

    //读取 子 订单
    static function get_sub_orderids($merge_payment_id){
        $db = kernel::database();
        $sql="select payments.payment_id,bills.rel_id from sdb_ectools_payments payments inner join sdb_ectools_order_bills bills on payments.payment_id=bills.bill_id where payments.merge_payment_id='$merge_payment_id' and bills.pay_object = 'order'";
        echo "get_sub_orderids\t" . $sql . "\n";
        $orderIdList = $db->select($sql);
        if(empty($orderIdList)){
            return false;
        }

        $newOrderIdList = self::array_column($orderIdList , 'rel_id');
        return $newOrderIdList;
    }

    static function array_column($array,$colKey){
        $newArray = array();
        foreach($array as $subArray){
            array_push($newArray , $subArray[$colKey]);
        }

        return $newArray;
    }

    //获取 rdp 订单的最新状态
    static function order_status_from_rdp($subOrderIdsList){
        $sdf = array();
        $sdf['METHOD'] = "findOrderPayStatusByOrderId";
        if(! is_array($subOrderIdsList)){
            $subOrderIdsList = array($subOrderIdsList);
        }

        $newSubOrderList = array();
        foreach($subOrderIdsList as $subOrderId){
            $newSubOrderList[] = array('order_id' => $subOrderId);
        }

        $sdf['sub_orders'] = $newSubOrderList;
        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
        $arr = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        $arr = SFSC_HttpClient::objectToArray($arr);
        echo "order_status_from_rdp\t" . var_export($arr, true). "\n";
        if(empty($arr['RESULT_CODE']) || '10001' != $arr['RESULT_CODE']){
            return false;
        }

        return $arr;
    }

    static function entirety_order_status_from_rdp($subOrderIdsList){
        echo "entirety_order_status_from_rdp\n";
        $rdpStatus = self::order_status_from_rdp($subOrderIdsList);
        $statusList = array();
        if(false === $rdpStatus){
            return false;
        }

        foreach ($rdpStatus['RESULT_DATA'] as $index => $subStatus){
            $payStatus = strtoupper($subStatus['PAY_STATUS']);
            $statusList[$payStatus] = 1;
        }

        //如果 状态一致，返回状态
        if(1 == count($statusList)){
            return $payStatus;
        }else if($statusList['I03001']){
            return 'I03001';
        }else if($statusList['I03004']){
            return 'I03004';
        }else if($statusList['I03002']){
            return 'I03002';
        }

        return false;
    }

    //超时后 通知RDP 退款 ，冻结后通知RDP扣款
    // {"order_id":"2018012622565972","store_id":"177","payment":"sfscpay","cur_money":"107","pay_status":"1","RELATION_ID":"0000134168","METHOD":"singlePayCallBack"}
    // order_id,store_id,payment,cur_money,pay_status
    static function sfsccallback($orderId , $is_refund){
        $orderItem = self::get_current_orders($orderId);
        if(empty($orderItem)){
            echo "sfsccallback no this order in database ...\n";
            return false;
        }

        $orderItem = array_pop($orderItem);
        if($is_refund){
            $orderItem['pay_status'] = '0';
        }else{
            $orderItem['pay_status'] = '1';
        }
        $sdf = $orderItem;
        $sdf['METHOD'] = 'singlePayCallBack';
        $post_data = array('serviceNo'=>'SubAccountService',"inputParam"=>json_encode($sdf));
        $arr = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        $arr = SFSC_HttpClient::objectToArray($arr);
        echo "sfsccallback\t".var_export($arr, true)."\n";
        if(empty($arr['RESULT_CODE'])){
            return false;
        }else if('10001' == $arr['RESULT_CODE']){
            return true;
        }

        return false;
    }

    //超时后 通知RDP 退款 ，冻结后通知RDP扣款
    static function entirety_sfsccallback($orderIdsList , $is_refund){
        echo "entirety_sfsccallback\n";
        $result = true;
        foreach ($orderIdsList as $orderId) {
            $result = $result && self::sfsccallback($orderId , $is_refund);
        }

        return $result;
    }

    static function register_autoload($load=array('kernel', 'autoload'))
    {
        if(function_exists('spl_autoload_register')){
            return spl_autoload_register($load);
        }else{
            return false;
        }
    }

    static function unregister_autoload($load=array('kernel', 'autoload'))
    {
        if(function_exists('spl_autoload_register')){
            return spl_autoload_unregister($load);
        }else{
            return false;
        }
    }

    //手工 退款
    static function refundByOrderIds(){
        if(!self::register_autoload()){
            require(ROOT_DIR . '/app/base/autoload.php');
        }

        //订单支付处理超时，给会员退款
        $orderList = array('2018020519671479','2018020614739750','2018020607032126');
        foreach($orderList as $orderId){
            if(self::sfsccallback($orderId , true)){
                self::order_set_failure($orderId);
            }
        }


        echo "all done ...\n";
    }


}





/*
 *
 {
 "METHOD":"findOrderPayStatusByOrderId",
 "sub_orders":[{
  "order_id":"2017122812288081"
 },
 {
  "order_id":"2017122812288081"
 },{
  "order_id":"2017122812288081"
 }]
}



返回值
{
 result_code = "10001"   查找成功
 RESULT_DATA:[{
  "ORDER_ID":"2017122812288081",
  "PAY_STATUS":"I03001"   // 未支付
  },
  {
  "ORDER_ID":"2017122812288081",
  "PAY_STATUS":"I03002"   // 冻结成功
  },
  {
  "ORDER_ID":"2017122812288081",
  "PAY_STATUS":"I03003"   // 支付完成
  },
  {
  "ORDER_ID":"2017122812288081",
  "PAY_STATUS":"I03004"   // 支付失败
  }]
}
 * */

