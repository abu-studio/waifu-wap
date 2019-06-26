<?php
class cardcoupons_order_pay extends b2c_api_rpc_request
{
    /**
     * 公开构造方法
     * @params app object
     * @return null
     */
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * 最终的克隆方法，禁止克隆本类实例，克隆是抛出异常。
     * @params null
     * @return null
     */
    final public function __clone()
    {
        trigger_error(app::get('b2c')->_("此类对象不能被克隆！"), E_USER_ERROR);
    }
    /**
     *卡券订单付款成功
     *
     **/
    public function order_pay_finish(&$sdf,$status='succ',$from='Back',&$msg,&$refund_status=false){
        $arrOrderbillls = $sdf['orders'];
        foreach ($arrOrderbillls as $rel_id=>$objOrderbills){
            switch ($objOrderbills['bill_type'])
            {
                case 'payments':
                    switch ($objOrderbills['pay_object'])
                    {
                        case 'order':
                            return $this->__order_payment($objOrderbills['rel_id'],$sdf,$status,$msg);
                            break;
                        default:
                            return true;
                            break;
                    }
                default:
                    return true;
                    break;
            }
        }
    }
    //付款后卡券销售订单后续处理
    private function __order_payment($rel_id, &$sdf, &$status='succ',&$msg='',&$refund_status=false,&$refund_type='0')
    {
        $objMath = kernel::single('ectools_math');
        $obj_orders = kernel::single('b2c_mdl_orders');
        $obj_order_items = kernel::single('b2c_mdl_order_items');
        $obj_cards_pass = kernel::single('cardcoupons_mdl_cards_pass');
        $obj_cards = kernel::single('cardcoupons_mdl_cards');

        $sdf_order = $obj_orders->dump($rel_id, '*', $subsdf);
        $order_items = array();
        $pass_array  =array();
        $total_pass=array();
        $total_nums=0;
        $total_sendnums=0;
        if ($sdf_order)
        {
            //判断是否是填写预约单后，生成的销售订单。如果是直接返回true
            $obj_exchange = kernel::single('physical_mdl_exchange');
            $exchange_info = $obj_exchange->dump(array("order_id"=>$rel_id));
            if($exchange_info){
                return true;
            }
            if($sdf_order['pay_status']!='1'){//判断是否支付完成，暂不支持部分付款
                return true;
            }
            if($sdf_order['order_type'] =='largeCustomer' || $sdf_order['order_type'] =='cmpay'){ //如果是大客户订单 暂时不需要考虑发货情况
                return true;
            }

            $order_item_count=$obj_order_items->count(array('order_id'=>$rel_id));
            $order_items=$obj_order_items->getList('*',array('order_id'=>$rel_id,'item_type'=>array('virtual','entity')));
            if($order_items){
                foreach($order_items as $oikey=>$oivalue){
                    $total_nums=$total_nums+$oivalue['nums'];
                    $cards_info=$obj_cards->getList('card_id,goods_id,source',array('goods_id'=>$oivalue['goods_id']));
                    if($cards_info){
                        $cards_info[0]['product_id']=$oivalue['product_id'];
                        $cards_info[0]['order_id']=$oivalue['order_id'];
                        $send_nums=$oivalue['nums']-$oivalue['sendnum'];
                        //内部卡处理
                        if($cards_info[0]['source']=='internal'){
                            //电子码处理，直接短信发送卡密
                            if($oivalue['item_type']=='virtual'){
                                $db = kernel::database();
                                $sqlWhere = " `card_id`='{$cards_info[0]['card_id']}' and `disabled`='false' and `status`='0' and `ex_status`='true' and `type`='virtual' and `source`='internal'";
                                $sql = "update sdb_cardcoupons_cards_pass set `status`='1',`order_id`='". $oivalue['order_id'] ."'  where ". $sqlWhere ." order by batch asc, card_no asc limit ".$send_nums;
                                $db->exec($sql);$db->commit();

                                $sqlArr = array('card_id' => $cards_info[0]['card_id'] , 'order_id' => $oivalue['order_id']);
                                $virtual_pass=$obj_cards_pass->getList('*',$sqlArr,0,$send_nums,"card_no asc");
                                //$pass_array['internal']=$obj_cards_pass->usePassByOrder($cards_info,$send_nums,$msg);
                                if($virtual_pass && count($virtual_pass)==$send_nums){
                                    $pass_array['internal'] = $virtual_pass;
                                }else{
                                    $msg="卡券库存不足，请联系客服";
                                    return false;
                                }
                            }else if ($oivalue['item_type']=='entity'){
                                //实体卡直接挑选出卡密不作发货操作。
                                $db = kernel::database();
                                $sqlWhere = " `card_id`='{$cards_info[0]['card_id']}' and `disabled`='false' and `status`='0' and `ex_status`='true' and `type`='entity' and `source`='internal'";
                                $sql = "update sdb_cardcoupons_cards_pass set `status`='-1',`order_id`='". $oivalue['order_id'] ."'  where ". $sqlWhere ." order by batch asc, card_no asc limit ".$send_nums;
                                $rs = $db->exec($sql);$db->commit();

                                $sqlArr = array('card_id' => $cards_info[0]['card_id'] , 'order_id' => $oivalue['order_id']);
                                $entity_pass=$obj_cards_pass->getList('*',$sqlArr,0,$send_nums,"card_no asc");
                                if(! ($entity_pass && count()==$send_nums)){
                                    $msg="卡券实体卡库存不足，请联系客服";
                                    return false;
                                }
                                $send_nums=0;
                            }

                        }
                        //外部卡处理
                        if($cards_info[0]['source']=='external'){
                            //电子码处理，直接短信发送卡密
                            if($oivalue['item_type']=='virtual'){
                                $db = kernel::database();
                                $sqlWhere = " `card_id`='{$cards_info[0]['card_id']}' and `disabled`='false' and `status`='0' and `ex_status`='true' and `type`='virtual' and `source`='external'";
                                $sql = "update sdb_cardcoupons_cards_pass set `status`='1',`order_id`='". $oivalue['order_id'] ."'  where ". $sqlWhere ." order by batch asc, card_no asc limit ".$send_nums;
                                $db->exec($sql);$db->commit();

                                $sqlArr = array('card_id' => $cards_info[0]['card_id'] , 'order_id' => $oivalue['order_id']);
                                $virtual_pass=$obj_cards_pass->getList('*',$sqlArr,0,$send_nums,"card_no asc");

                                if($virtual_pass && count($virtual_pass)==$send_nums){
                                    $pass_array['external'] = $virtual_pass;
                                }else{
                                    $msg="卡券电子码库存不足，请联系客服";
                                    return false;
                                }
                            }else if ($oivalue['item_type']=='entity'){
                                //实体卡直接挑选出卡密不作发货操作。
                                $db = kernel::database();
                                $sqlWhere = " `card_id`='{$cards_info[0]['card_id']}' and `disabled`='false' and `status`='0' and `ex_status`='true' and `type`='entity' and `source`='external'";
                                $sql = "update sdb_cardcoupons_cards_pass set `status`='-1',`order_id`='". $oivalue['order_id'] ."'  where ". $sqlWhere ." order by batch asc, card_no asc limit ".$send_nums;
                                $rs = $db->exec($sql);$db->commit();

                                $sqlArr = array('card_id' => $cards_info[0]['card_id'] , 'order_id' => $oivalue['order_id']);
                                $entity_pass=$obj_cards_pass->getList('*',$sqlArr,0,$send_nums,"card_no asc");
                                if(! ($entity_pass && count()==$send_nums)){
                                    $msg="卡券实体卡库存不足，请联系客服";
                                    return false;
                                }
                                
                                $send_nums=0;
                            }
                        }
                        $order_items[$oikey]['send_nums']=$send_nums;
                        $total_sendnums=$total_sendnums+$send_nums;
                    }
                    //循环卡密
                    foreach($pass_array as $pakey=>$pavalue){
                        foreach($pavalue as $key=>$value){
                            $total_pass[]=array('card_pass_id'=>$value['card_pass_id'],'card_no'=>$value['card_no'],'card_pass'=>$value['card_pass'],'card_pass_ori'=>$value['card_pass_ori'],'order_id'=>$rel_id,'card_name'=>$value['card_name'],'from_time'=>$value['from_time'],'to_time'=>$value['to_time'],'card_id'=>$cards_info[0]['card_id'],'source'=>$cards_info[0]['source']);
                        }
                    }

                }
                $ship_info=$sdf_order['consignee'];


                $ship_status=1;
                if($order_item_count!=count($order_items) ||$total_nums!=$total_sendnums){
                    $ship_status=2;
                }
                $ship_info['ship_status']=$ship_status;
                if($send_nums){
                    $this->send_card($order_items,$sdf,array($rel_id),$total_pass,$ship_info);
                }
                return true;

            }
            else{
                return true;
            }
        }
        else
        {
            //合并支付
            $objMath = kernel::single('ectools_math');
            $objOrders = app::get('b2c')->model('orders');
            $obj_payments = app::get('ectools')->model('payments');
            $obj_order_bills = app::get('ectools')->model('order_bills');
            $payment_ids = $obj_payments->getList('payment_id',array('merge_payment_id'=>$rel_id));
            if($payment_ids){
                foreach($payment_ids as $key=>$val){
                    $order_id = $obj_order_bills->getRow('*',array('bill_id'=>$val['payment_id']));

                    $sdf = $obj_payments->getRow('*',array('payment_id'=>$val['payment_id']));
                    //防止而已修改支付信息
                    $orders = $objOrders->dump($order_id['rel_id']);

                    $sdf['cur_amount'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                    $orders['total_amount'] = $objMath->number_div(array($orders['cur_amount'], $orders['cur_rate']));
                    $sdf['money'] = floatval($orders['total_amount'] - $orders['payed']);
                    $sdf['currency']=$orders['currency'];
                    $sdf['cur_money'] = $objMath->number_minus(array($orders['cur_amount'], $orders['payed']));
                    $sdf['cur_rate'] = $orders['cur_rate'];
                    $sdf['orders']['0'] = $order_id;
                    $this->__order_payment($order_id['rel_id'],$sdf);
                }

            }else{
                $msg = app::get('b2c')->_('需要支付的订单号不存在！');
                $status = 'failed';
                return false;
            }
        }
    }
    /**
     * 自动发货虚拟商品
     * @return null
     */
    public function send_card($goods_ids,$sdf_payment,$order_ids,$total_pass,$ship_info,$is_merge=false){

        $objOrders =kernel::single('b2c_mdl_orders');
        $objOrder_items =kernel::single('b2c_mdl_order_items');
        $objGoods =kernel::single('b2c_mdl_goods');
        $objProducts = kernel::single('b2c_mdl_products');
        $objMath = kernel::single('ectools_math');
        $tag = true;
        foreach($goods_ids as $key=>$goods_id){
            $goods_info = $objGoods->getRow('store',array('goods_id'=>$goods_id['goods_id']));
            $goods_store = $objMath->number_minus(array($goods_info['store'], $goods_id['send_nums']));
            $objGoods->update(array('store'=>$goods_store),array('goods_id'=>$goods_id['goods_id']));

            $product_info = $objProducts->getRow('store',array('product_id'=>$goods_id['product_id']));
            $product_store = $objMath->number_minus(array($product_info['store'], $goods_id['send_nums']));
            $objProducts->update(array('store'=>$product_store),array('product_id'=>$goods_id['product_id']));

            $update_data['sendnum'] = $objMath->number_plus(array($goods_id['sendnum'], $goods_id['send_nums']));
            $objOrder_items->update($update_data,array('item_id'=>$goods_id['item_id']));
        }

        //走自动发货流程
        // 更新发货日志结果
        foreach($order_ids as $key=>$val){
            $objorder_log =kernel::single('b2c_mdl_order_log');
            if($tag){
                $log='';
                if($ship_info['ship_status']=='1'){
                    $log='系统已发货，无需物流';
                }
                if($ship_info['ship_status']=='2'){
                    $log='系统已部分发货，无需物流';
                }
                $sdf_order_log = array(
                    'rel_id' => $val,
                    'op_id' => '0',
                    'op_name' => 'auto',
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'delivery',
                    'result' => 'SUCCESS',
                    'log_text' =>$log,
                    'addon' => $log_addon,
                );
            }else{
                $sdf_order_log = array(
                    'rel_id' => $val,
                    'op_id' => '0',
                    'op_name' => 'auto',
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'delivery',
                    'result' => 'FAILURE',
                    'log_text' => '发货出错',
                    'addon' => $log_addon,
                );
            }
            $log_id = $objorder_log->save($sdf_order_log);
            if($log_id){
                //ajx crm
                //修改订单状态
                $aUpdate['order_id'] = $val;
                $aUpdate['ship_status'] = $ship_info['ship_status'];
                $objOrders->save($aUpdate);

                $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
                $req_arr['order_id']=$val;
                $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
                $data['confirm_time'] = time()+(app::get('b2c')->getConf('member.to_finish_XU'))*86400;
                $arr = app::get('business')->model('orders')->update($data,array('order_id' => $val));
                $order = kernel::single("b2c_mdl_orders")->getList("*",array('order_id' => $val));
                $this->send_sms($total_pass,$order);
            }
        }
    }
    //发送短信
    function send_sms($pass,$order){
        $order_id=$pass[0]['order_id'];
        $cards_pass_object=kernel::single('cardcoupons_mdl_cards_pass');
        $arrMember=array();
        $arrMemberInfo = kernel::single("pam_mdl_account")->getList("*",array("account_id"=>$order[0]['member_id']));
        if($arrMemberInfo[0]['account_type'] == 'card'){
            $RELATION_ID = '0000936810';
        }

        $content = "尊敬的客户，您订购的:";
        $content_log = "尊敬的客户，您订购的:";
        foreach($pass as $pkey=>$pval){
            //获取电子码短信模版start
            $systmpl = kernel::single("b2c_mdl_member_systmpl");
            $card_data = array();
            $card_data = array(
                'userName'=>$arrMemberInfo[0]['login_name'],
                'product_Name'=>$pval['card_name'].'('.$pval['card_no'].')',
                //'product_no'=>$pval['card_no'],
                'product_password'=>$pval['card_pass']
            );

            //$content = $systmpl->fetch("messenger:b2c_messenger_sms/cardcoupons_orders",$card_data);

            $content .= $pval['card_name']."兑换码：".$pval['card_no']."/密码：".$pval['card_pass']."有效期起止：".date('Y年m月d日',$pval['from_time'])."-".date('Y年m月d日',$pval['to_time'])."，";
            $content_log .= $pval['card_name']."兑换码：".$pval['card_no']."/密码：".'<span class="show_card_pass">'.$pval['card_pass_ori']."</span> 有效期起止：".date('Y年m月d日',$pval['from_time'])."-".date('Y年m月d日',$pval['to_time']).";";

            //获取电子码短信模版end
            //外部卡自定义短信模板
            //if($pval['source']=='external'){}
            $objcards = app::get('cardcoupons')->model('cards');
            $arrCards = $objcards->getList("*",array("card_id"=>$pval['card_id']));
            $msg_templet = $arrCards[0]['msg_templet'];
            $msg_templet = str_replace('<{$user_name}>',$arrMemberInfo[0]['login_name'],$msg_templet);
            $msg_templet = str_replace('<{$card_name}>',$pval['card_name'],$msg_templet);
            $msg_templet = str_replace('<{$card_no}>',$pval['card_no'],$msg_templet);
            $pval['from_time'] = date('Y年m月d日',$pval['from_time']);
            $pval['to_time'] = date('Y年m月d日',$pval['to_time']);
            $msg_templet = str_replace('<{$date_start}>',$pval['from_time'],$msg_templet);
            $msg_templet = str_replace('<{$date_end}>',$pval['to_time'],$msg_templet);
            $msg_templet = str_replace('<{$card_password}>',$pval['card_pass'],$msg_templet);
            $msg_templets .= $msg_templet.'。';

        }
        $content .= "提取地址：http://www.yoofuu.com/index.php/buycard.html 【悠福网】";
        if($msg_templet){
            $content = $msg_templets;
        }

        //在订单详情页显示卡密等信息-hy
        $objorder_log =kernel::single('b2c_mdl_order_log');
        $sdf_order_log = array(
            'rel_id' => $order[0]['order_id'],
            'op_id' => '0',
            'op_name' => 'auto',
            'alttime' => time(),
            'bill_type' => 'order',
            'behavior' => 'delivery',
            'result' => 'SUCCESS',
            'log_text' => $content_log,
            'addon' => $log_addon,
        );
        $objorder_log->save($sdf_order_log);

        $_sjson = array(
            'METHOD'=>'sendMessage',
            'PHONENO'=>$order[0]['ship_mobile'],
            'MESSAGE'=>$content,
            'SENDUSER_TYPE'=>'HUMBAS_NO',
            'RELATION_ID'=>$RELATION_ID ? $RELATION_ID : $arrMemberInfo[0]['login_name']
        );

        $post_data = array('serviceNo'=>'SendMessageService',"inputParam"=>json_encode($_sjson));

        $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
        foreach($pass as $pkey=>$pval){
            if($tmpdata != null && gettype($tmpdata) == "object"){
                $getSubActList = SFSC_HttpClient::objectToArray($tmpdata);
                if($getSubActList['RESULT_CODE'] == "10001"){
                    //记录发送成功日志
                    $this->write_card_log(true,$pval['card_no'],$order_id,$arrMember,$pval['card_name']);
                }else{
                    //记录发送失败
                    $this->write_card_log(false,$pval['card_no'],$order_id,$arrMember,$pval['card_name']);
                }
            }else{
                //记录相关错误日志
                $this->write_card_log(false,$pval['card_no'],$order_id,$arrMember,$pval['card_name']);
            }
            //外部卡考虑无卡号情况
            if($pval['source']=='external'){
                $cards_pass_object->update(array('status'=>'1'),array('card_pass_id'=>$pval['card_pass_id']));
            }else{
                $cards_pass_object->update(array('status'=>'1'),array('card_no'=>$pval['card_no'],'source'=>'internal'));
            }
        }



    }




    function write_card_log($status = false,$card_no,$order_id,$arrMember,$goods_name){
        $orderLog = kernel::single("b2c_mdl_order_log");
        if($status){
            $order_log = array(
                'rel_id' => $order_id,
                'op_id' => 0,
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'cms',
                'result' => 'SUCCESS',
                'log_text' =>'电子券<span class="siteparttitle-orage">'.$goods_name.'</span>短信发送'.$card_no.'成功！',
            );
        }else{
            $order_log = array(
                'rel_id' => $order_id,
                'op_id' => 0,
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'cms',
                'result' => 'FAILURE',
                'log_text' =>'电子券<span class="siteparttitle-orage">'.$goods_name.'</span>短信发送'.$card_no.'失败',
            );
        }
        $re=$orderLog->save($order_log);
    }

}