<?php
	class cardcoupons_sfscorder_create{

        public $app;
		function __construct($app){
            set_error_handler(array('cardcoupons_sfscorder_create', 'error_handler'));
            $this->app = $app;
            header("cache-control: no-store, no-cache, must-revalidate");
            header("Content-type:text/html;charset=utf-8");
        }

        static function error_handler($errno, $errstr, $errfile, $errline ){
            switch ($errno) {
                case E_ERROR:
                case E_USER_ERROR:
                    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
                    break;

                case E_STRICT:
                case E_USER_WARNING:
                case E_USER_NOTICE:
                default:
                    break;
            }
        }

        private function log($message) {
            error_log($message, 0);
        }

        /**
         * send_user_succ-返回成功信息
         *
         * @param      $result
         * @return     json数组
         */
        private function send_user_succ($result){
            $result_json = array(
                'rsp'=>'succ',
                'data'=>$result,
            );
            ob_clean();
            echo json_encode($result_json);
            exit;
        }

        /**
         * send_user_error-返回失败信息
         *
         * @param      $code,$data
         * @return     json数组
         */
        private function send_user_error($code, $data){
            $res = array(
                'rsp'   =>  'fail',
                'res'   =>  $code,
                'data'  =>  $data,
            );
            echo json_encode($res);
            exit;
        }

        private function get_sign($params,$token){
            return strtoupper(md5(strtoupper(md5($params)).$token));
        }

        private function assemble($params){
            if(!is_array($params))  return null;
            ksort($params,SORT_STRING);
            $sign = '';
            foreach($params AS $key=>$val){
                $sign .= $key . (is_array($val) ? assemble($val) : $val);
            }
            return $sign;
        }

        private function verify($params) {
        //验证是否合法
        }



        /**
         * getSfscOrder-获取大客户订单
         *
         * @param       GOODS_ID,NUMS
         * @return      json字符
         */
        public function getSfscOrder(){
            //参数:qiyeid，qiyename,goods_id,nums,card_type:virtual-电子卡/entity-实物卡 / 实物
            $goods_object = kernel::single("b2c_mdl_goods");
            $cards_object = kernel::single("cardcoupons_mdl_cards");
            $products_object = kernel::single("b2c_mdl_products");

            $this->verify("");
            if(!$_GET['GOODS_ID'] || !$_GET['NUMS']){
                $this->send_user_error("","参数错误！");
            }

            $products_data = $products_object->dump(array("goods_id"=>$_GET['GOODS_ID']),'goods_id,product_id');

            if(!$products_data['product_id']){
                $this->send_user_error("","参数错误！");
            }
            //todo 现在需求就是 只会下内部虚拟卡信息
            $goods = array(
                'goods' => Array (
                    'cards_pass_type'=>'virtual',
                    'num' => trim($_GET['NUMS']),
                    'goods_id' => $products_data['goods_id'],
                    'pmt_id' => "",
                    'product_id' =>$products_data['product_id']
                )
            );

            $aCart = array();
            kernel::single('fastbuy_cart_fastbuy_goods')->get_sfsccreateorder_arr(
                $goods,
                array(),
                $aCart
            );

            $msg = "";
            $post_array = array(
                'purchase'=>array(
                    'member_id'=>""
                ),
                'payment'=>array(
                    'currency'=>'CNY',
                    'pay_app_id'=>'sfscpay',
                    'dis_point'=>'0'
                ),
                'largeCustomer'=>true
            );
            //largeCustomer
            //事物处理开始
            $db = kernel::database();
            $transaction_status = $db->beginTransaction();

            $order = kernel::single("b2c_mdl_orders");
            $post_array['order_id'] = $order_id = $order->gen_id();
            $post_array['member_id'] = $this->member['member_id'] ? $this->member['member_id'] : 0;
            $obj_order_create = kernel::single("b2c_order_create");
            $goods_data = $goods_object->dump(array("goods_id"=>$_GET['GOODS_ID']),"store_id");
            $order_data = $obj_order_create->generate($post_array,'',$msg,$aCart);
            $order_data['store_id'] = $goods_data['store_id'];
            $result = $obj_order_create->save($order_data, $msg);

            if($msg){
                $this->send_user_error("",$msg);
            }

            if ($result){
                foreach( kernel::servicelist('invoice_setting') as $services ) {
                    if ( is_object($services) ) {
                        if ( method_exists($services, 'saveInvoiceData') ) {
                            $services->saveInvoiceData($post_array['order_id'],$post_array['payment']);
                        }
                    }
                }

                $log_text = "";
                if($result){
                    $log_text[] = array(
                        'txt_key'=>'订单创建成功！',
                        'data'=>array(),
                    );
                    $log_text = serialize($log_text);
                }else{
                    $log_text[] = array(
                        'txt_key'=>'订单创建失败！',
                        'data'=>array(),
                    );
                    $log_text = serialize($log_text);
                }

                $orderLog =  kernel::single("b2c_mdl_order_log");
                $sdf_order_log = array(
                    'rel_id' => $order_id,
                    'op_id' => "",
                    'op_name' => "后台生成订单",
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'creates',
                    'result' => 'SUCCESS',
                    'log_text' => $log_text,
                );

                $orderLog->save($sdf_order_log);

            }else{
                $db->rollback();
            }
            //模拟支付流程支付大订单
            if($result){
                if ($order_data){
                    $objPay = kernel::single("ectools_pay");
                    $sdf = array(
                        'payment_id' => $objPay->get_payment_id(),
                        'order_id' => $order_data['order_id'],
                        'rel_id' => $order_data['order_id'],
                        'op_id' => $order_data['member_id'],
                        'pay_app_id' => $order_data['payinfo']['pay_app_id'],
                        'currency' => $order_data['currency'],
                        'payinfo' => array(
                            'cost_payment' => $order_data['payinfo']['cost_payment'],
                        ),
                        'pay_object' => 'order',
                        'member_id' => $order_data['member_id'],
                        'op_name' => $this->user->user_data['account']['login_name'],
                        'status' => 'ready',
                        'cur_money' => $order_data['cur_amount'],
                        'money' => $order_data['total_amount'],
                    );

                    $is_payed = $objPay->gopay($sdf, $msg);
                    if (!$is_payed){
                        $msg = app::get('b2c')->_('订单自动支付失败！');
                        $this->send_user_error("",$msg);
                    }


                    $obj_pay_lists = kernel::servicelist("order.pay_finish");
                    $is_payed = false;
                    foreach ($obj_pay_lists as $order_pay_service_object)
                    {
                        $is_payed = $order_pay_service_object->order_pay_finish($sdf, 'succ', 'font',$msg);
                    }
                }
            }else{
                $db->rollBack();
            }

            // 更新发货日志结果
            $objorder_log = kernel::single("b2c_mdl_order_log");
            $objOrders = kernel::single("b2c_mdl_orders");
            $sdf_order_log = array(
                'rel_id' => $order_data['order_id'],
                'op_id' => '0',
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'delivery',
                'result' => 'SUCCESS',
                'log_text' => '大客户订单已发货，无需物流',
                'addon' => "",
            );
            $log_id = $objorder_log->save($sdf_order_log);
            //修改订单已发货状态
            if($log_id){
                $aUpdate['order_id'] = $order_data['order_id'];
                $aUpdate['ship_status'] = '1';
                $objOrders->save($aUpdate);
                $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
                $req_arr['order_id']=$order_data['order_id'];
                $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
                $data['confirm_time'] = time()+(app::get('b2c')->getConf('member.to_finish_XU'))*86400;
                app::get('business')->model('orders')->update($data,array('order_id' => $order_data['order_id']));
            }
            //todo 增加java客户信息 没做
            $db->commit($transaction_status);

            $this->send_user_succ(array('order_id'=>$order_data['order_id']));

        }


        /**
         * getCardInfoList-获取卡券列表信息
         *
         * @param       无
         * @return      json字符
         */
        public function getCardInfoList(){
            $this->verify("");
            //$pagelimit=10;
            //$nPage = 1;
            $db = kernel::database();
            //卡券编号-card_id 产品ID-goods_id 卡券名称-name 销售价格-price 成本价格-cost
            $data = $db->select("select goods.store_id,cards.card_id,cards.goods_id,cards.name,goods.price,goods.cost from sdb_cardcoupons_cards as cards left join sdb_b2c_goods as goods on cards.goods_id = goods.goods_id where cards.source='internal' AND goods.marketable='true'");       
			//error_log(var_export($data,1)."\n",3,ROOT_DIR.'/wuyanxi.txt');
			$this->send_user_succ($data);
        }

        /**
         * changeCardInfo-更新卡密兑换信息
         *
         * @param       card_pass_id,status
         * @return      json字符
         */
        public function changeCardInfo(){
            //card_pass_id,status
            $this->verify("");
            if(trim($_POST['card_pass_id']) == ""){
                $this->send_user_error("","卡号为空！");
            }
            if(trim($_POST['status']) == ""){
                $this->send_user_error("","状态为空！");
            }
            $card_pass_object = kernel::single("cardcoupons_mdl_cards_pass");
            $flag = $card_pass_object->update(array('status'=>$_GET['status']), array("card_pass_id"=>$_GET['card_pass_id']));
            if($flag){
                $this->send_user_succ("操作成功!");
            }
            $this->send_user_error("","操作失败！");
        }

        /**
         * getCardPass-根据订单获取卡号卡密信息
         *
         * @param       order_id,num
         * @return      json字符
         */
        public function getCardPass(){

            if($_GET['ORDER_ID'] == "" || $_GET['NUM'] == ""){
                $this->send_user_error("","参数错误！");
            }

            //order_id,nums
            $order_items_object = kernel::single("b2c_mdl_order_items");
            $orders_object = kernel::single("b2c_mdl_orders");
            $cards_object = kernel::single("cardcoupons_mdl_cards");
            $cards_pass_object = kernel::single("cardcoupons_mdl_cards_pass");
            $db = kernel::database();
            $msg = "";
            $orders_items_data = $order_items_object->dump(array("order_id"=>$_GET['ORDER_ID']),"goods_id,item_type,nums");
            if(empty($orders_items_data)){
                $this->send_user_error("","没有找到该订单！");
            }
            if($orders_items_data['item_type'] != "virtual"){
                $this->send_user_error("","本接口暂时只支持电子券！");
            }
            $cards_data = $cards_object->dump(array("goods_id"=>$orders_items_data['goods_id'],"source"=>"internal"),"card_id");
            if(empty($cards_data)){
                $this->send_user_error("","该卡密未找到，或者已经被删除！");
            }

            $pass = $cards_pass_object->sfscauto_pass(array('order_id'=>$_GET['ORDER_ID'],'card_id'=>$cards_data['card_id'],'num'=>$_GET['NUM']),$msg);
            if($msg){
                $this->send_user_error("",$msg);
            }

            if($pass){
				foreach($pass as $key=>$val){
					foreach($val as $k=>$value){
                        $pass_tmp[$key][$k] = '\''.$value.'\'';
                        if ($k == 'card_pass'){
                            $pass_tmp[$key][$k] = '\''.kernel::single('cardcoupons_mysqlkey')->enPwByCurrentKey($value).'\'';
                        }
                    }
				}

                $pass_string = "";
                foreach($pass_tmp as $v){
                    $pass_string .= "(".implode(',',$v)."),";
                }
				$db->exec('insert into sdb_cardcoupons_cards_pass (from_time,to_time,card_no,card_pass,passset,source,createtime,batch,status,ex_status,disabled,is_send,lasttime,order_id,type,card_id,card_name) value '.substr($pass_string,0,strlen($pass_string)-1));
			}

            $this->send_user_succ($pass);

        }


        /**
         * sendInsideletter-发送站内信接口
         *
         * @param       无
         * @return      json字符
         */
        function sendInsideletter(){
            if(empty($_GET['HUMBAS_NO']) || empty($_GET['MESSAGE'])){
                $this->send_user_error("","参数错误！");
            }
            $this->send_user_succ($_GET['HUMBAS_NO']);
        }
		
        /**
         * getOrderList-获取订单信息
         *
         * @from_time 开始时间
         * @return      json字符
         */
        public function getOrderList(){
			$from_time = $_GET['from_time'];
			$to_time = $_GET['to_time'];
            $db = kernel::database();
			$sql = "SELECT o.order_id,o.total_amount,(case o.pay_status when '0' then '未支付(0)' when '1' then '已支付(1)' when '2' then '已付款至到担保方(2)' when '3' then '部分付款(3)' when '4' then '部分退款(4)' when '5' then '全额退款(5)' end)as pay_status,o.ship_status,(case o.ship_status when '0' then '未发货' when '1' then '已发货' when '2' then '部分发货' when '3' then '部分退货' when '4' then '已退货' end)as ship_status_name,o.createtime,o.last_modified,o.payment,o.shipping,a.login_name,s.store_name,o.refund_status,(case o.refund_status when '0' then '未申请退款' when '1' then '退款申请中,等待卖家审核' when '2' then '卖家拒绝退款' when '3' then '卖家同意退款,等待买家退货' when '4' then '卖家已退款' when '5' then '买家已退货,等待卖家确认收货' when '6' then '卖家不同意协议,等待买家修改' when '7' then '买家已退货,卖家不同意协议,等待买家修改' when '8' then '平台介入,等待卖家举证' when '9' then '平台介入,等待平台处理' when '10' then '平台介入已处理' when '11' then '卖家同意退款，等待卖家打款至平台' when '12' then '卖家已退款，等待系统结算' end)as refund_status_name,(case log.result when 'SUCCESS' then '1' else '0' end)as merge_pay FROM sdb_b2c_orders o LEFT JOIN sdb_pam_account a ON o.member_id=a.account_id LEFT JOIN sdb_business_storemanger s ON o.store_id=s.store_id LEFT JOIN (SELECT * FROM sdb_b2c_order_log WHERE log_text LIKE '%合并支付成功%' GROUP BY rel_id)AS log ON o.order_id=log.rel_id WHERE o.pay_status in ('1','3','4','5') AND o.payment='sfscpay' AND o.status!='dead' AND o.order_kind='virtual'";
			if($from_time)$sql." AND o.last_modified>=".$from_time;
			if($to_time)$sql." AND o.last_modified<".$to_time;
			$data = $db->select($sql);
			//error_log(var_export($data,1)."\n",3,ROOT_DIR.'/wuyanxi.txt');
			$this->send_user_succ($data);
        }

        /**
         * getOrderList-获取退款单信息
         *
         * @from_time 开始时间
         * @return      json字符
         */
        public function getRefundList(){
			$from_time = $_GET['from_time'];
			$to_time = $_GET['to_time'];
            $db = kernel::database();
			$sql = "SELECT r.refund_id,o.order_id,r.cur_money as total_amount,(case r.`status` when 'succ' then '支付成功' when 'failed' then '支付失败' when 'cancel' then '未支付' when 'error' then '处理异常' when 'invalid' then '非法参数' when 'progress' then '处理中' when 'timeout' then '超时' when 'ready' then '准备中' end)as pay_status,r.t_begin as createtime,r.pay_app_id as payment,a.login_name,s.store_name FROM sdb_ectools_refunds r LEFT JOIN sdb_ectools_order_bills ob ON r.refund_id=ob.bill_id LEFT JOIN sdb_b2c_orders o ON ob.rel_id=o.order_id LEFT JOIN sdb_pam_account a ON r.member_id = a.account_id LEFT JOIN sdb_business_storemanger s ON r.store_id = s.store_id WHERE r.refund_type='1' AND r.`status`!='ready' AND o.pay_status in ('1','3','4','5') AND o.status!='dead' AND o.order_kind='virtual'";
			if($from_time)$sql." AND o.last_modified>=".$from_time;
			if($to_time)$sql." AND o.last_modified<".$to_time;
			$data = $db->select($sql);
			//error_log(var_export($data,1)."\n",3,ROOT_DIR.'/wuyanxi.txt');
			$this->send_user_succ($data);
        }
		/**
         * getOrderDetails-获取订单详细
         *
         */
		public function getOrderDetails(){
			$order_id=$_GET['ORDER_ID'];
			$pay_status=$_GET['PAY_STATUS'];
			if($order_id&&$pay_status){
				$mdl_orders = app::get('b2c')->model('orders');
				$data = $mdl_orders->getRow('*',array('order_id'=>$order_id));
				if($data){
					$mdl_account=app::get('pam')->model('account');
					$member_info = $mdl_account->getRow('*',array('account_id'=>$data['member_id']));
					$result=array(
						'ORDER_ID'=>$data['order_id'],
						'PRICE'=>$data['cur_money'],
						'SHOPNAME'=>$data['store_id'],
						'PAY_STATUS'=>$data['pay_status'],
						'HUMBAS_NO'=>$member_info['login_name']
					);
					if($pay_status!='dead'){
						if($data['pay_status']!=$pay_status){
							$flag=$mdl_orders->update(array('pay_status'=>$pay_status,'status'=>'active','last_modified'=>time()),array('order_id'=>$order_id));
							if($flag){
								$result['PAY_STATUS']=$pay_status;
							}
							$this->send_user_succ($result);
						}else{
							$this->send_user_succ($result);
						}
					}else{
						$mdl_orders->update(array('status'=>'dead','last_modified'=>time()),array('order_id'=>$order_id));
						$this->send_user_succ($result);
					}
				}else{
					echo '';
					exit;
				}
			}else{
				echo '';
				exit;
			}
		}
    }
?>