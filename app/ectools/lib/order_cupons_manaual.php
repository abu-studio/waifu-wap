<?php

class order_cupons_manaual
{
    static function boot()
    {
        if (!self::register_autoload()) {
            require(ROOT_DIR . '/app/base/autoload.php');
        }

        return true;

        $ordersList = self::get_payed_orders();
        $orderIds = self::array_column($ordersList, 'order_id');

        $goodsListKeyOrderId = self::get_goods_by_order($orderIds);
        $cardPassKeyOrderId = self::get_card_pass_list($orderIds);

        foreach ($orderIds as $orderId) {
            $goods = $goodsListKeyOrderId[$orderId];
            $cardPass = $cardPassKeyOrderId[$orderId];

            self::send_card($goods, $orderId, $cardPass);
        }
    }

    static function test_jdapi()
    {
        $apiLogsList = self::get_jdapi_log();
        foreach ($apiLogsList as $item) {
            $apiLog = unserialize($item['result']);

            foreach ($apiLog as $logItem) {
                self::change_jdPrice($logItem);
            }
        }

        return false;
    }

    /**
     * 自动发货虚拟商品
     * @return null
     */
    static function send_card($goodsItems, $orderId, $total_pass)
    {
        $objOrders = kernel::single('b2c_mdl_orders');
        $objOrder_items = kernel::single('b2c_mdl_order_items');
        $objGoods = kernel::single('b2c_mdl_goods');
        $objProducts = kernel::single('b2c_mdl_products');
        $objMath = kernel::single('ectools_math');
        $tag = true;
        foreach ($goodsItems as $key => $goodsItem) {
            $goodsItem['send_nums'] = $goodsItem['nums'];
            $goods_info = $objGoods->getRow('store', array('goods_id' => $goodsItem['goods_id']));
            $goods_store = $objMath->number_minus(array($goods_info['store'], $goodsItem['send_nums']));
//            $objGoods->update(array('store' => $goods_store), array('goods_id' => $goodsItem['goods_id']));

            $product_info = $objProducts->getRow('store', array('product_id' => $goodsItem['product_id']));
            $product_store = $objMath->number_minus(array($product_info['store'], $goodsItem['send_nums']));
            $objProducts->update(array('store' => $product_store), array('product_id' => $goodsItem['product_id']));

            $update_data['sendnum'] = $objMath->number_plus(array($goodsItem['sendnum'], $goodsItem['send_nums']));
            $objOrder_items->update($update_data, array('item_id' => $goodsItem['item_id']));
        }

        //走自动发货流程
        // 更新发货日志结果
        $objorder_log = kernel::single('b2c_mdl_order_log');
        if ($tag) {
            $log = '系统已发货，无需物流';
            $sdf_order_log = array(
                'rel_id' => $orderId,
                'op_id' => '0',
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'delivery',
                'result' => 'SUCCESS',
                'log_text' => $log,
                'addon' => $log_addon,
            );
        } else {
            $sdf_order_log = array(
                'rel_id' => $orderId,
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
        if ($log_id) {
            //ajx crm
            //修改订单状态
            $aUpdate['order_id'] = $orderId;
            $aUpdate['ship_status'] = '1';
            $objOrders->save($aUpdate);

            $debugPriceDir = ROOT_DIR . "/data/log/jd_image_bug/" . date('Ymd');
            error_log($orderId . "\r\n", 3, $debugPriceDir . '/order_ids.txt');
            $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
            $req_arr['order_id'] = $orderId;
            $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
            $data['confirm_time'] = time() + (app::get('b2c')->getConf('member.to_finish_XU')) * 86400;
            $arr = app::get('business')->model('orders')->update($data, array('order_id' => $orderId));
            $order = kernel::single("b2c_mdl_orders")->getList("*", array('order_id' => $orderId));
            self::send_sms($total_pass, $order);
        }
    }

    //发送短信
    static function send_sms($pass, $order)
    {
        $order_id = $pass[0]['order_id'];
        $cards_pass_object = kernel::single('cardcoupons_mdl_cards_pass');
        $arrMember = array();
        $arrMemberInfo = kernel::single("pam_mdl_account")->getList("*", array("account_id" => $order[0]['member_id']));
        if ($arrMemberInfo[0]['account_type'] == 'card') {
            $RELATION_ID = '0000936810';
        }

        $content = "尊敬的客户，您订购的:";
        $content_log = "尊敬的客户，您订购的:";
        foreach ($pass as $pkey => $pval) {
            //获取电子码短信模版start
            $systmpl = kernel::single("b2c_mdl_member_systmpl");
            $card_data = array();
            $card_data = array(
                'userName' => $arrMemberInfo[0]['login_name'],
                'product_Name' => $pval['card_name'] . '(' . $pval['card_no'] . ')',
                //'product_no'=>$pval['card_no'],
                'product_password' => $pval['card_pass']
            );

            //$content = $systmpl->fetch("messenger:b2c_messenger_sms/cardcoupons_orders",$card_data);

            $content .= $pval['card_name'] . "兑换码：" . $pval['card_no'] . "/密码：" . $pval['card_pass'] . "有效期起止：" . date('Y年m月d日', $pval['from_time']) . "-" . date('Y年m月d日', $pval['to_time']) . "，";
            $content_log .=$pval['card_name'] . "兑换码：" . $pval['card_no'] . "/密码：" . $pval['card_pass'] . "有效期起止：" . date('Y年m月d日', $pval['from_time']) . "-" . date('Y年m月d日', $pval['to_time']) . ";";
            //获取电子码短信模版end
            //外部卡自定义短信模板
            //if($pval['source']=='external'){}
            $objcards = app::get('cardcoupons')->model('cards');
            $arrCards = $objcards->getList("*", array("card_id" => $pval['card_id']));
            $msg_templet = $arrCards[0]['msg_templet'];
            $msg_templet = str_replace('<{$user_name}>', $arrMemberInfo[0]['login_name'], $msg_templet);
            $msg_templet = str_replace('<{$card_name}>', $pval['card_name'], $msg_templet);
            $msg_templet = str_replace('<{$card_no}>', $pval['card_no'], $msg_templet);
            $pval['from_time'] = date('Y年m月d日', $pval['from_time']);
            $pval['to_time'] = date('Y年m月d日', $pval['to_time']);
            $msg_templet = str_replace('<{$date_start}>', $pval['from_time'], $msg_templet);
            $msg_templet = str_replace('<{$date_end}>', $pval['to_time'], $msg_templet);
            $msg_templet = str_replace('<{$card_password}>', $pval['card_pass'], $msg_templet);
            $msg_templets .= $msg_templet . '。';

        }
        $content .= "提取地址：http://www.yoofuu.com/index.php/buycard.html 【悠福网】";
        if ($msg_templet) {
            $content = $msg_templets;
        }
        //在订单详情页显示卡密等信息-hy
        $objorder_log = kernel::single('b2c_mdl_order_log');
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
            'METHOD' => 'sendMessage',
            'PHONENO' => $order[0]['ship_mobile'],
            'MESSAGE' => $content,
            'SENDUSER_TYPE' => 'HUMBAS_NO',
            'RELATION_ID' => $RELATION_ID ? $RELATION_ID : $arrMemberInfo[0]['login_name']
        );

        $post_data = array('serviceNo' => 'SendMessageService', "inputParam" => json_encode($_sjson));

        $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);
        foreach ($pass as $pkey => $pval) {
            if ($tmpdata != null && gettype($tmpdata) == "object") {
                $getSubActList = SFSC_HttpClient::objectToArray($tmpdata);
                if ($getSubActList['RESULT_CODE'] == "10001") {
                    //记录发送成功日志
                    self::write_card_log(true, $pval['card_no'], $order_id, $arrMember, $pval['card_name']);
                } else {
                    //记录发送失败
                    self::write_card_log(false, $pval['card_no'], $order_id, $arrMember, $pval['card_name']);
                }
            } else {
                //记录相关错误日志
                self::write_card_log(false, $pval['card_no'], $order_id, $arrMember, $pval['card_name']);
            }
            //外部卡考虑无卡号情况
            if ($pval['source'] == 'external') {
                $cards_pass_object->update(array('status' => '1'), array('card_pass_id' => $pval['card_pass_id']));
            } else {
                $cards_pass_object->update(array('status' => '1'), array('card_no' => $pval['card_no'], 'source' => 'internal'));
            }
        }
    }

    static function write_card_log($status = false, $card_no, $order_id, $arrMember, $goods_name)
    {
        $orderLog = kernel::single("b2c_mdl_order_log");
        if ($status) {
            $order_log = array(
                'rel_id' => $order_id,
                'op_id' => 0,
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'cms',
                'result' => 'SUCCESS',
                'log_text' => '电子券<span class="siteparttitle-orage">' . $goods_name . '</span>短信发送' . $card_no . '成功！',
            );
        } else {
            $order_log = array(
                'rel_id' => $order_id,
                'op_id' => 0,
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'cms',
                'result' => 'FAILURE',
                'log_text' => '电子券<span class="siteparttitle-orage">' . $goods_name . '</span>短信发送' . $card_no . '失败',
            );
        }
        $re = $orderLog->save($order_log);
    }

    static function get_jdapi_log()
    {
        $sql = "select log_id,result from  sdb_jdsale_api_log where  log_id in('7868891','7868888','7868875','7868798','7868793','7868790','7868787','7868784','7868781') order by log_id asc ";
        $db = kernel::database();
        $apiLogsList = $db->select($sql);

        return $apiLogsList;
    }

    static function get_goods_by_order($orderIds)
    {
        $orderIdsStr = implode(",", $orderIds);
        $sql = "select item_id,order_id,product_id,goods_id,bn,`name`,nums,sendnum from sdb_b2c_order_items where order_id in ($orderIdsStr)";
        $db = kernel::database();
        $goodsList = $db->select($sql);

        $goodsReturn = array();
        foreach ($goodsList as $item) {
            $orderId = $item['order_id'];
            $goodsReturn[$orderId] = $goodsReturn[$orderId] ? null : array();
            array_push($goodsReturn[$orderId], $item);
        }

        return $goodsReturn;
    }

    static function get_incorrect_orders()
    {
        $sql = "select order_id,final_amount,pay_status,last_modified,from_unixtime(last_modified) time2,member_id,ship_name,ship_mobile,payed,store_id,order_type,order_kind from sdb_b2c_orders where shipping_id = '98' and pay_status = '1' and ship_status = '0' and order_kind='b2c_card' and `status`='active' and last_modified > '1532399000' order by order_id desc";

        $db = kernel::database();
        $ordersList = $db->select($sql);
        if (empty($ordersList)) {
            echo "暂无异常订单需要纠正\n";
            exit;
        }

        return $ordersList;
    }

    static function get_refund_orders($ordersList)
    {
        $orderIds = self::array_column($ordersList, 'order_id');
        $orderIdsStr = implode(",", $orderIds);
        $sql = "select bill.rel_id,bill.money,bill.bill_type,bill.bill_id,payments.status,payments.pay_app_id,refund_status from sdb_ectools_order_bills bill left join sdb_ectools_payments payments on bill.bill_id = payments.payment_id where bill.rel_id in($orderIdsStr)  and bill.bill_type = 'blances'";

        $db = kernel::database();
        $refundManual = array('rel_id' => '2018072514777307');
        $refundOrders = $db->select($sql);
        array_push($refundOrders, $refundManual);
        if (empty($refundOrders)) {
            echo "暂无退款订单返回\n";
            return array();
        }

        return $refundOrders;
    }

    static function get_payed_orders()
    {
        $ordersList = self::get_incorrect_orders();
        $refundList = self::get_refund_orders($ordersList);
        if (empty($refundList)) {
            return $ordersList;
        }

        $orderIdArr = array();
        foreach ($refundList as $item) {
            $orderId = $item['rel_id'];
            array_push($orderIdArr, $orderId);
        }

        foreach ($ordersList as $index => $item) {
            $orderId = $item['order_id'];
            if (in_array($orderId, $orderIdArr)) {
                unset($ordersList[$index]);
            }
        }

        return $ordersList;
    }

//    static function get_payments_list($orderIds)
//    {
//        $orderIdsStr = implode(",", $orderIds);
//        $sql = "select bill.rel_id,bill.money,bill.bill_type,bill.bill_id,payments.status,payments.pay_app_id,refund_status from sdb_ectools_order_bills bill left join sdb_ectools_payments payments on bill.bill_id = payments.payment_id where bill.rel_id in($orderIdsStr)  and bill.bill_type = 'blances'";
//
//        $db = kernel::database();
//        $refundOrders = $db->select($sql);
//        if (empty($refundOrders)) {
//            echo "暂无异常订单需要纠正\n";
//            exit;
//        }
//    }

    static function get_card_pass_list($orderIds)
    {
        $orderIdsStr = implode(",", $orderIds);
        $sql = "select card_pass_id,card_id,batch,card_name,card_no,card_pass,status,order_id,exchange_num,from_time,to_time,source from sdb_cardcoupons_cards_pass where order_id in($orderIdsStr)";
        $db = kernel::database();
        $cardPassOrders = $db->select($sql);

        $cardPassReturn = array();
        foreach ($cardPassOrders as $item) {
            $orderId = $item['order_id'];
            //card_pass解密
            $item['card_pass'] = kernel::single('cardcoupons_mysqlkey')->dePwByKey($item['card_pass']);
            $cardPassReturn[$orderId] = $cardPassReturn[$orderId] ? null : array();
            array_push($cardPassReturn[$orderId], $item);
        }

        return $cardPassReturn;
    }

    static function register_autoload($load = array('kernel', 'autoload'))
    {
        if (function_exists('spl_autoload_register')) {
            return spl_autoload_register($load);
        } else {
            return false;
        }
    }

    static function unregister_autoload($load = array('kernel', 'autoload'))
    {
        if (function_exists('spl_autoload_register')) {
            return spl_autoload_unregister($load);
        } else {
            return false;
        }
    }

    //手工 退款
    static function refundByOrderIds()
    {
        if (!self::register_autoload()) {
            require(ROOT_DIR . '/app/base/autoload.php');
        }

        //订单支付处理超时，给会员退款
        $orderList = array('2018020519671479', '2018020614739750', '2018020607032126');
        foreach ($orderList as $orderId) {
            if (self::sfsccallback($orderId, true)) {
                self::order_set_failure($orderId);
            }
        }


        echo "all done ...\n";
    }

    static function array_column($array, $colKey)
    {
        $newArray = array();
        foreach ($array as $subArray) {
            array_push($newArray, $subArray[$colKey]);
        }

        return $newArray;
    }

    static function change_jdPrice($data)
    {
        if (empty($data)) return false;
        if ($data['result']['skuId'] == "" || $data['result']['skuId'] === null) {
//            kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
            return false;
        }
        if (!is_int($data['result']['skuId'])) {
//            kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
            return false;
        }
        $array = array(
            'sku' => $data['result']['skuId'],
        );
        $jddata_tmp = self::get_jdprice($data['result']['skuId']);
        if (empty($jddata_tmp['result']) || $jddata_tmp['result_code'] != '0000') {
//            kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
            return false;
        }
        $jddata = unserialize($jddata_tmp['result']);

        if (!empty($jddata)) {
            $goods_obj = app::get("b2c")->model("goods");
            $products_obj = app::get("b2c")->model("products");

            //查询价格接口，获取到的值如果为空，null 或者小于0是未上架商品
            if ($jddata[0]['jdPrice'] == "" || $jddata[0]['price'] == "" || $jddata[0]['jdPrice'] === null || $jddata[0]['price'] === null || $jddata[0]['jdPrice'] <= 0) {
//                kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
                return false;
            }

            $goods_array = $goods_obj->dump(array('bn' => (string)$jddata[0]['skuId'], 'goods_kind' => 'jdgoods'), "goods_id,bn,price,agreed_price");

            //处理mysql查询结果出错问题
            if ($goods_array['bn'] != (string)$jddata[0]['skuId']) {
//                kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
                return false;
            }

            if (!empty($goods_array)) {
                //由于本地价格储存按照2.000 做法所以 简单的乘以1000判断价格是否相等
                if ($jddata[0]['jdPrice'] * 1000 == $goods_array['price'] * 1000 && $jddata[0]['price'] * 1000 == $goods_array['agreed_price'] * 1000) {
                    //两个价格都没有什么变化的话，直接调用京东的删除消息接口把该信息删除掉
//                    kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
                } else {
                    $tmp_data = array(
                        'jdsku' => $data['result']['skuId'],
                        'sku' => (string)$jddata[0]['skuId'],
                        'bn' => $goods_array['bn'],
                        'bendi_price' => $goods_array['price'],
                        'jdPrice' => $jddata[0]['jdPrice'],
                        'price' => $jddata[0]['price'],
                        'time' => date("Y-d-m H:i:s", time()),
                    );

                    if ($jddata[0]['jdPrice'] < $jddata[0]['price']) {
//                        kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
                        return false;
                    }

                    $jdgoods = array(
                        'price' => $jddata[0]['jdPrice'],
                        'agreed_price' => $jddata[0]['price'],
                    );
                    //接口获取的价格和本地价格有出入，需要修改
                    $goods_obj->update($jdgoods, array('goods_id' => $goods_array['goods_id']));
                    $jdproducts = array(
                        'price' => $jddata[0]['jdPrice'],
                    );
                    $products_obj->update($jdproducts, array('goods_id' => $goods_array['goods_id']));
                    //difference
                    if ($goods_array['goods_id'] != "" && $goods_array['price'] != "" && $jddata[0]['jdPrice'] != "") {
                        $diff_array = array(
                            "goods_id" => $goods_array['goods_id'],
                            "old_price" => $goods_array['price'],
                            "new_price" => $jddata[0]['jdPrice'],
                            "goods_kind" => 'jdgoods'
                        );
                        $obj_diffprice = app::get("jdsale")->model("diffprice");
                        $obj_diffprice->save($diff_array);
                    }
//                    kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
                }
            } else {
                //查询该商品在本地没有信息
//                kernel::single('jdsale_api_area')->delMessage(array('id' => $data['id']), 'book');
            }
        }
    }

    static function get_jdprice($jdSkuId)
    {
        $params = 'a:1:{s:3:"sku";i:' . $jdSkuId . ';}';
        $sql = "select * from sdb_jdsale_api_log where params = '$params' order by log_id desc limit 1";

        $db = kernel::database();
        $price = $db->select($sql);

        return array_pop($price);
    }

}