<?php
/**
 * Created by PhpStorm.
 * User: shaojun
 * Date: 2016/12/8
 * Time: 10:57
 */

class jdsale_autofinshjdbookorders{

    function orders(){
        error_log("测试时间 ".date('Y-m-d H:i:s')."\n\r",3,ROOT_DIR.'/jdxiadan.txt');
        $this->cron_log('开始执行本次定时任务',true,'正常开始');
        //1、检索出今天支付完成本地支付的订单信息

        $no_payed_jdorders = $this->getSubmitedOrders();

        //2、调用京东下单接口;3.调用支付接口
        $payed_jdorders = $this->confirmOccupyStock($no_payed_jdorders);

        //4.修改本地订单状态，同步订单信息到平台管理端和用户端(用户端以发送站内信为主，也考虑短信）
        $this->updateLocalOrder($payed_jdorders);
        $this->cron_log('结束执行本次定时任务',true,'正常结束');
    }

    function manual_submit_orders($orders){
        $this->cron_log('开始执行本次手动任务',true,'正常开始');

        $mdl_jdorders =  app::get('jdsale')->model('jdorders');
        $no_payed_jdorders = $mdl_jdorders->getList('*',
            array('jdstatus' =>'npaid','order_id|in' => $orders));
        $payed_jdorders = $this->confirmOccupyStock($no_payed_jdorders);
        $this->updateLocalOrder($payed_jdorders);
        $this->cron_log('结束执行本次手动任务',true,'正常结束');
    }

    //1、检索出今天支付完成本地支付的订单信息
    private function getSubmitedOrders(){

        $mdl_orders = app::get('b2c')->model('orders');
        $filter = array(
            'pay_status' => '1',//本地已经支付
            'status'=>'active',
            'ship_status'=> '0',//待发货的订单
            'order_kind' => 'jdbook',
        );

        $payed_orders =  $mdl_orders->getList('order_id',$filter);

        $payed_orders_2 = array();
        $mdl_jdorders = app::get('jdsale')->model('jdorders');
        foreach($payed_orders as $item){
            $payed_orders_2[]=$item['order_id'];
        }
        $no_payed_jdorders = $mdl_jdorders->getList('*',
            array('jdstatus' =>'npaid','order_id|in' => $payed_orders_2));
        return $no_payed_jdorders;

    }

    //2、调用京东的确定预占库存订单接口，确认支付下单操作
    private function confirmOccupyStock($no_payed_jdorders){
        $payed_jdorders = array();
        if (empty($no_payed_jdorders)){
            return $payed_jdorders;
        }
        $jdsale_api_orders = kernel::single('jdsale_api_orders');
        $mdl_jdorders =  app::get('jdsale')->model('jdorders');
        foreach($no_payed_jdorders as $k => $v){

            //确认下单，在正式测试环境下才使用
            $result = $jdsale_api_orders->confirmOccupyStock(array('jdOrderId'=>$v['jdorders_id']) , 'book');
            //$result['result'] = true;

            if ($result['result']){
                $jdorders = array(
                    'jdstatus' => 'ypaid',
                    'paidtime' => time(),
                    'order_state' => 0,
                    'check_status' => 0,
                    'check_info' => '',
                );
                $mdl_jdorders->update($jdorders,array('order_id'=>$v['order_id']));
                $payed_jdorders[] = array('order_id' => $v['order_id'],
                                        'jdorder_id' => $v['jdorders_id']);
                $this->cron_log('确认下单操作',true,'本地订单号：'.$v['order_id'].',京东订单号：'.$v['jdorders_id']);
            }else{

                $jdorders = array(
                    'check_status' => 2,
                    'check_info' => '京东未审核通过',
                    'check_time' => time(),
                );
                error_log("=-=-=-=-=确认预占库存失败-=-=-=-".PHP_EOL.var_export($v['order_id'],1).PHP_EOL,3,DATA_DIR."/cancel_order_for_jdbalance.log");
                if (strpos($result['resultMessage'],'余额不足') !== false){
                    kernel::single('jdsale_canceljdorders')->cancelJdorder($v['order_id']);
                    $jdorders = array(
                        'check_status' => 2,
                        'check_info' => '京东账户余额不足',
                        'check_time' => time(),
                    );
                }
                $mdl_jdorders->update($jdorders,array('order_id'=>$v['order_id']));
                $this->cron_log('确认下单操作',false,'本地订单号：'.$v['order_id'].',京东订单号：'.$v['jdorders_id']);

            }
        }
        return $payed_jdorders;
    }

    //4.修改本地订单状态，同步订单信息到平台管理端和用户端(用户端以发送站内信为主)
    private function updateLocalOrder($payed_jdorders){
        if (empty($payed_jdorders)){
            return false;
        }
        $mdl_orders =  app::get('b2c')->model('orders');
        foreach($payed_jdorders as $k=>$v){
            //定义该订单的确认收货时间
            $confirm_time = time()+(app::get('b2c')->getConf('member.to_finish'))*86400;
            $filter = array('order_id'=>$v['order_id']);
            $mdl_orders->update(array('ship_status'=>'1','confirm_time' =>$confirm_time),$filter);
            $member_id = $mdl_orders->getRow('member_id',$filter);
            $this->add_order_log($v['order_id'],$v['jdorder_id']);
            $this->notifyCustomer($v['order_id'],$v['jdorder_id'],$member_id['member_id']);
        }
    }

    //发送短信和站内信给客户
    private function notifyCustomer($order_id,$jdorder_id,$member_id){
        //站内信通知客户
        $msg = '感谢您的购物，您的京东商品订单已经审核通过，订单号：'.$order_id.' 京东订单号为：'.$jdorder_id
            .'，根据您的订货信息，京东即将发出您购买的商品，请注意查收！';
        $title = '悠福网-企业弹性福利服务商';
        $messenger_msgbox = kernel::single('b2c_messenger_msgbox');
        $result = $messenger_msgbox->send($member_id,$title,$msg,'');

        //短信通知客户，调用java接口
        $mdl_member = app::get('b2c')->model('members');
        $sdf = $mdl_member->dump($member_id);
        $phone_no = $sdf['contact']['phone']['mobile'];
        $member_info = $mdl_member->get_member_info($member_id);
        $sms_arr = array(
            'METHOD'=>'sendMessage',
            'PHONENO'=>$phone_no,
            'MESSAGE'=>$msg,
            'SENDUSER_TYPE'=>'HUMBAS_NO',
            'RELATION_ID'=>$member_info['uname'],
        );
        $post_data = array('serviceNo'=>'SendMessageService',"inputParam"=>json_encode($sms_arr));

        //$tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);

        return $result;

    }

    private function add_order_log($order_id,$jdorder_id){
        //$log_text = "";
        $log_text[] = array(
            'txt_key'=>stripslashes('京东审核已经通过，订单<a href="javascript:void(0)" onclick=\'show_delivery_item(this,"%s",%s)\' title="点击查看详细" style="color: rgb(0, 51, 102); font-weight: bolder; text-decoration: underline;">全部商品</a>发货完成'),
            'data'=>array(
                0=>$order_id,
                1=>htmlentities(json_encode($this->get_order_items($order_id)), ENT_QUOTES),
            ),
        );
        $log_text[] = array(
            'txt_key'=>stripslashes('，物流公司：<a href="javascript:void(0)" onclick=\'show_jdorder_track(this,"%s")\' title="点击查看物流追踪信息" style="color: rgb(0, 51, 102); font-weight: bolder; text-decoration: underline;">京东物流</a>'),
            'data'=>array(
                0=>$jdorder_id,
            ),
        );
        $log_addon="";

        $log_text = serialize($log_text);

        $store_info = $this->get_store_info();
        // 更新发货日志结果
        $objorder_log = app::get('b2c')->model('order_log');
        $sdf_order_log = array(
            'rel_id' => $order_id,
            'op_id' => $store_info['account_id'],
            'op_name' => $store_info['shop_name'],
            'alttime' => time(),
            'bill_type' => 'order',
            'behavior' => 'delivery',
            'result' => 'SUCCESS',
            'log_text' => $log_text,
            'addon' => $log_addon,
        );
        $log_id = $objorder_log->save($sdf_order_log);
        return $log_id;
    }

    private function get_store_info(){
        $store_id = app::get('site')->getConf('jdsale.shopId');
        $mdl_store = app::get('business')->model('storemanger');
        $store_info = $mdl_store->getRow('*',array('store_id'=>$store_id));
        return $store_info;
    }

    private function get_order_items($order_id){
        $mdl_order_items  = app::get('b2c')->model('order_items');
        $order_items =  $mdl_order_items->getList('name, nums as number',array('order_id' => $order_id));
        return $order_items;
    }

    private function log($msg){

        error_log(var_export($msg,1)."\n\r",3,ROOT_DIR.'/shaojun.txt');
    }

    private function cron_log($function_name,$success,$result=''){
        $log = array('cron_name'=>'京东下单定时任务',
                     'function_name'=>$function_name,
                     'success'=>$success,
                     'result'=>$result,
                     'cron_kind'=>'jdbook',
                     'createtime'=>time());
        $mdl_cron_log = app::get('jdsale')->model('cron_log');
        $mdl_cron_log->save($log);
    }
}