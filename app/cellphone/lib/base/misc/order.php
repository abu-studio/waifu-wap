<?php
  
class cellphone_base_misc_order extends cellphone_cellphone
{
    public function __construct($app){
        parent::__construct();
        $this->app = $app;
        
        header("cache-control: no-store, no-cache, must-revalidate");
        $this->objMath = kernel::single("ectools_math");   
        $this->mCart = app::get('b2c')->model('cart');
        $this->mCart->unset_data();
        $this->member_status = kernel::single('b2c_frontpage')->check_login();
        $this->obj_order = &app::get('b2c')->model('orders');
        $this->cart_model = &app::get('b2c')->model('cart_objects');
    }
    
    function store_total(&$aCart){
        foreach($aCart['object']['goods'] as $key=>$value){
            $total[$value['store_id']][$value['obj_ident']] = $value['subtotal_price'];
            if($value['freight_bear']=='business'){
                $store_free_shipping[$value['store_id']][] = 0;
                $total_weight[$value['store_id']][] = '0';
            }else{
                $store_free_shipping[$value['store_id']][] = 1;
                $total_weight[$value['store_id']][] = $value['subtotal_weight'];
            }
        }
        if($total){
            foreach($total as $key=>$value){
                $total[$key] = array_sum($value);
                $total_weight[$key] = array_sum($total_weight[$key]);
                $store_free_shipping[$key] = array_sum($store_free_shipping[$key]);
            }
        }
        $aCart['subtotal_store_good_price'] = $total;
        $aCart['store_free_shipping'] = $store_free_shipping;
        $aCart['subtotal_weight_store'] = $total_weight;
    }
    /**
   ** 创建订单**
    @params payment      Array('currency'='CNY','pay_app_id'=>'alipay','dis_point'=>100,'is_tax'=>true,'tax_company'=>'个人')
    @params shipping 
      Array('67'=>Array('800'=>Array('memo'=>'','shipping_id'=>15,'shop_id'=>3)),
          '72'=>Array('800'=>Array('memo'=>'','shipping_id'=>14),
                      '799'=>Array('memo'=>'','shipping_id'=>12),
                      '798'=>Array('memo'=>'','shipping_id'=>13)
                      )
          )
    @params delivery
      Array(        [addr_id]=>22
                    [ship_area] => mainland:北京/北京市/西城区:4
                    [ship_addr] => 23243243
                    [ship_zip] => 214004
                    [ship_name] => cuiqiwen
                    [ship_mobile] => 18601579874
                    [area_id] => 4
                    [member_id] => 998
                    [ship_addr_area] => 北京市西城区
                )
    **/
    public function create(){
        $params = $this->params;
        $must_params = array(
            'session'=>'会员ID',
            'delivery'=>'收货地址信息',
            'payment'=>'税金抵扣等',
            'shipping'=>'运费模板',
            'md5_cart_info'=>'订单标志'
        );
        $this->check_params($must_params);
      //json_decode( string $json,bool true/false)解码 返回数组/对象
        $params['delivery'] = json_decode($params['delivery'],1);
        $params['payment'] = json_decode($params['payment'],1);
        $params['shipping'] = json_decode($params['shipping'],1);

      //检查会员是否登录 
        $arrMember = $this->get_current_member();
        if(empty($arrMember['member_id'])){
        $this->send(false,null,app::get('b2c')->_('session失效或您还未登录'));
        }
      //end
      //检查订单的收货地址必填信息
        $msg = array();
        if(!$params['delivery']['ship_area'] || !$params['delivery']['ship_addr_area'] || !$params['delivery']['ship_addr']){
            $msg[] = app::get('b2c')->_("收货地区不能为空！")."<br />";
        }
        if(!$params['delivery']['area_id']){
            $msg[] = app::get('b2c')->_("地区ID不能为空！")."<br />";
        }
        if(!$params['delivery']['ship_name']){
            $msg[] = app::get('b2c')->_("收货人姓名不能为空！")."<br />";
        }
       
        if(!$params['delivery']['ship_mobile'] && !$params['delivery']['ship_tel']){
            $msg[] = app::get('b2c')->_("手机或电话必填其一！")."<br />";
        }
        if(!empty($msg)){
            $this->send(false, null, implode('<br/>',$msg));exit;
        }
      // end
      //检查支付信息是否完整
       if(empty($params['payment']['pay_app_id'])){
       
          $this->send(false, null, '支付方式不能为空!');exit;
       }
       if(empty($params['payment']['currency'])){
       
          $this->send(false, null, '支付货币不能为空!');exit;
       }


      //
      //检查会员身份是否是店家
        $checkSeller = kernel::service('business_check_goods_isMy');
        if($checkSeller && !$checkSeller->check_isSeller($msg)){
            $this->send(false, null, $msg);exit;
        }

        $db = kernel::database();

        $transaction_status = $db->beginTransaction();
        
        if(isset($params['status']) && $params['status'] == 'fastbuy'){
            if(isset($params['type']) && $params['type'] == 'spike'){
                kernel::single('spike_cart_fastbuy_goods')->get_fastbuy_arr(
                    $_SESSION['S[Cart_Fastbuy]']['goods'],
                    $_SESSION['S[Cart_Fastbuy]']['coupon'],
                    $allCart);//立即购买  
                $this->store_total($allCart);

                //检查每人限购 by ql 2013-7-31
                
                $applyObj = app::get('spike')->model('spikeapply');
                $gid = $allCart['object']['goods'][0]['obj_items']['products'][0]['goods_id'];
                $num = $allCart['object']['goods'][0]['quantity'];
                $aid = $applyObj->getOnActIdByGoodsId($gid);
                if($aid){
                    $obj_business_activity = kernel::single('spike_business_activity');
                    if (!$obj_business_activity->checkPersonLimit($num,$arrMember['member_id'],$aid,&$msg)){
                        $this->send(false, null, $msg);exit;
                    }
                }else{
                    $this->send(false, null, '不存在此活动，或活动已经结束');exit;
                }

                //当有活动时，在生成订单前做一个当前购买数量与实际库存的判断
                if( isset($allCart['cart_status'] )){
                    $this->send(false, null, app::get('b2c')->_($allCart['cart_error_html']));exit;
                }
                $obj_order_temp = kernel::single("spike_order_create");
            }elseif(isset($params['type']) && $params['type'] == 'group'){
                kernel::single('groupbuy_cart_fastbuy_goods')->get_fastbuy_arr(
                    $_SESSION['S[Cart_Fastbuy]']['goods'],
                    $_SESSION['S[Cart_Fastbuy]']['coupon'],
                    $allCart);//立即购买  by ql
                $this->store_total($allCart);

                //检查每人限购 by ql 2013-7-31
                //$member_id = app::get('b2c')->member_id;
                $applyObj = app::get('groupbuy')->model('groupapply');
                $gid = $allCart['object']['goods'][0]['obj_items']['products'][0]['goods_id'];
                $num = $allCart['object']['goods'][0]['quantity'];
                $aid = $applyObj->getOnActIdByGoodsId($gid);
                if($aid){
                    $obj_business_activity = kernel::single('groupbuy_business_activity');
                    if (!$obj_business_activity->checkPersonLimit($num,$arrMember['member_id'],$aid,&$msg)){
                       $this->send(false, null, $msg);exit;
                    }
                }else{
                    $this->send(false, null, '不存在此活动，或活动已经结束');exit;
                }

                //当有活动时，在生成订单前做一个当前购买数量与实际库存的判断
                if( isset($allCart['cart_status'] )){
                    $this->send(false, null, app::get('b2c')->_($allCart['cart_error_html']));exit;
                }
                $obj_order_temp = kernel::single("groupbuy_order_create");
            }elseif(isset($params['type']) && $params['type'] == 'score'){
                kernel::single('scorebuy_cart_fastbuy_goods')->get_fastbuy_arr(
                    $_SESSION['S[Cart_Fastbuy]']['goods'],
                    $_SESSION['S[Cart_Fastbuy]']['coupon'],
                    $allCart);//立即购买  by ql
                $this->store_total($allCart);
                $applyObj = app::get('scorebuy')->model('scoreapply');
                $gid = $allCart['object']['goods'][0]['obj_items']['products'][0]['goods_id'];
                $num = $allCart['object']['goods'][0]['quantity'];
                $aid = $applyObj->getOnActIdByGoodsId($gid);
                if($aid){
                    $obj_business_activity = kernel::single('scorebuy_business_activity');
                    if(!$obj_business_activity->checkPersonLimit($num,$arrMember['member_id'],$aid,&$msg)){
                       $this->send(false, null, $msg);exit;
                    }
                }else{
                    $this->send(false, null, '不存在此活动，或活动已经结束');exit;
                }

                //当有活动时，在生成订单前做一个当前购买数量与实际库存的判断
                if( isset($allCart['cart_status'] )){
                    $this->send(false, null, app::get('b2c')->_($allCart['cart_error_html']));exit;
                }
                $obj_order_temp = kernel::single("scorebuy_order_create");
            }else{
                kernel::single('fastbuy_cart_fastbuy_goods')->get_fastbuy_arr(
                    $_SESSION['S[Cart_Fastbuy]']['goods'],
                    $_SESSION['S[Cart_Fastbuy]']['coupon'],
                    $allCart
                );
                $this->store_total($allCart);

                //当有活动时，在生成订单前做一个当前购买数量与实际库存的判断
                if( isset($allCart['cart_status'] )){
                    $this->send(false, null, app::get('b2c')->_($allCart['cart_error_html']));exit;
                }
                $obj_order_temp = kernel::single("b2c_order_create");
            }
        }else{
           
             // 正常购买时取到购物车数据
            $allCart = $this->mCart->get_objects();
            $obj_order_temp = kernel::single("b2c_order_create");
            
        }
    
        //检查购物车是否为空
        if($this->mCart->is_empty($allCart)){
            $this->send(false, null, app::get('b2c')->_('操作失败，购物车为空！'));exit;
        }
        //检查购物车是否发生变化 比对md5_cart_info 信息是否一致
        if($params['md5_cart_info'] != kernel::single("b2c_cart_objects")->md5_cart_objects()){
            $this->send(false, null, app::get('b2c')->_('购物车内容发生变化，请重新结算！'));exit;
        }
       
      
        //抵扣积分情况处理
       
        $this->dis_point=0;
        $this->dis_point_money=0;
        //积分抵扣处理 add by ql 2013-6-24 start
        $this->point_usage=app::get('b2c')->getConf('site.point_usage');//积分用途：2：用于抵扣
        $this->get_policy_method=app::get('b2c')->getConf('site.get_policy.method');//是否启用积分：1：不启用。
        $this->site_point_deductible_value = app::get('b2c')->getConf('site.point_deductible_value');
        if($this->point_usage==2&& $this->get_policy_method!=1){
            $this->dis_point=$params['payment']['dis_point']?$params['payment']['dis_point']:0;
            $this->dis_point_money=$this->dis_point*$this->site_point_deductible_value;//抵扣的总金额
        }
        
        //获得分单数据$temp_split_order
        $area_id =  $params['delivery']['area_id']; // 得到收货地址的区域ID
		$controller   = kernel::single('b2c_ctl_site_order');
        $temp_split_order = kernel::single('b2c_cart_object_split')->split_order($controller,$area_id,$allCart);

        //取到购物车的json_order_split 后面计算运费要用到
        $params['split_order'] = $allCart['json_order_split'];
        //end
        $obj_member = app::get('b2c')->model('members');
        $orderLog = app::get('b2c')->model("order_log");
        $obj_goods = app::get('b2c')->model('goods');
        $obj_dlytype = app::get('b2c')->model('dlytype');

        //订单全部生成标志
        $flag = true;

        // 循环所有的分单数据 生成单条订单数据 创建订单
        foreach($temp_split_order as $store_id=>$sgoods){
            foreach($sgoods['slips'] as $order_sp=>$sorder){
                
                if(empty($sorder['shipping'])){
                      $db->rollback();
                      $this->send(false, null, app::get('b2c')->_('存在不支持所选地区配送的商品，请从购物车删除后重新结算！'));exit;
                   }
                $aCart=$this->get_split_cart($allCart,$sorder,$params,$params['shipping'][$store_id][$order_sp],$store_id);
                $postData=$this->get_post_cart($aCart,$sorder,$params,$params['shipping'][$store_id][$order_sp],$db);
                
                $obj_order_create = $obj_order_temp;
                //取得购物车数据对应的订单数据。生成一条订单数据
                $order_data = $this->get_order_data($aCart,$postData,$store_id,$msg,$arrMember,$obj_order_create,$db);
               
                $order_id = $order_data['order_id'];

                // 判断是参加活动时生成的订单 进行相关处理

                if(isset($params['type']) && $params['type'] == 'spike'){
                    //增加秒杀字段
                    $order_data['order_type'] = 'spike';
                    $applyObj = app::get('spike')->model('spikeapply');
                    $goods_id = $order_data['order_objects'][0]['order_items'][0]['goods_id'];
                    $act_id = $applyObj->getOnActIdByGoodsId($goods_id);
                    if($act_id){
                        $order_data['act_id'] = $act_id;
                    }
                }elseif(isset($params['type']) && $params['type'] == 'group'){
                    //增加团购字段
                    $order_data['order_type'] = 'group';
                    $applyObj = app::get('groupbuy')->model('groupapply');
                    $goods_id = $order_data['order_objects'][0]['order_items'][0]['goods_id'];
                    $act_id = $applyObj->getOnActIdByGoodsId($goods_id);
                    if($act_id){
                        $order_data['act_id'] = $act_id;
                    }
                }elseif(isset($params['type']) && $params['type'] == 'score'){
                    //增加积分换购字段
                    $order_data['order_type'] = 'score';
                    //增加商品积分by ql start
                    $applyObj = app::get('scorebuy')->model('scoreapply');
                    $goods_id = $order_data['order_objects'][0]['order_items'][0]['goods_id'];
                    $act_id = $applyObj->getOnActIdByGoodsId($goods_id);
                    if($act_id){
                        if(empty($order_data['score_u'])){
                            $order_data['score_u'] = 0;
                        }
                        $order_data['act_id'] = $act_id;
                        $goods_num = $order_data['itemnum'];
                        $apply = $applyObj->dump(array('id'=>$act_id));
                        if($apply['isMemLv'] == '1'){
                            $obj_members = app::get('b2c')->model('members');
                            $arr_member = $obj_members->dump($order_data['member_id'], 'point,member_lv_id');
                            $memLvScoreObj = app::get('scorebuy')->model('memberLvScore');
                            $memLvs = $memLvScoreObj->getMemLvScoreByIds($act_id,$goods_id);
                            $order_data['score_u'] += $memLvs[$arr_member['member_lv']['member_group_id']]['score'] * $goods_num;
                        }else{
                            $order_data['score_u'] += $apply['score'] * $goods_num;
                        }
                    }
                }                
                // 活动订单处理结束end


                $result = $obj_order_create->save($order_data, $msg);
                
                if($result){
                    // 发票高级配置埋点
                    foreach( kernel::servicelist('invoice_setting') as $services ) {
                        if ( is_object($services) ) {
                            if ( method_exists($services, 'saveInvoiceData') ) {
                                $services->saveInvoiceData($postData['order_id'],$postData['payment']);
                            }
                        }
                    }
                }

             
                //创建订单日志
                $log_text = "";
                if($result){
                    $log_text[] = array(
                        'txt_key'=>'订单创建成功！',
                        'data'=>array(
                        ),
                    );
                    $log_text = serialize($log_text);
                }else{
                    $log_text[] = array(
                        'txt_key'=>'订单创建失败！',
                        'data'=>array(
                        ),
                    );
                    $log_text = serialize($log_text);
                }
                
                $sdf_order_log = array(
                    'rel_id' => $order_id,
                    'op_id' => $arrMember['member_id'],
                    'op_name' => (!$arrMember['member_id']) ? app::get('b2c')->_('顾客') : $arrMember['uname'],
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'creates',
                    'result' => 'SUCCESS',
                    'log_text' => $log_text,
                );

                $log_id = $orderLog->save($sdf_order_log);
                //end
                if($result){
                    foreach(kernel::servicelist('b2c_save_post_om') as $object){
                        $object->set_arr($order_id, 'order');
                    }

                    // 设定优惠券不可以使用
                    if(isset($aCart['object']['coupon']) && $aCart['object']['coupon']){
                        $obj_coupon = kernel::single("b2c_coupon_mem");
                        foreach ($aCart['object']['coupon'] as $coupons){
                            if($coupons['used'])
                                $obj_coupon->use_c($coupons['coupon'], $arrMember['member_id']);
                        }
                        foreach ($aCart['object']['coupon'] as $ckey => $cval) {
                            if($cval['store_id'] == $store_id){
                                $this->cart_model->remove_object('coupon', $cval['obj_ident']);
                            }
                        }
                    }

                    /*
                    // 订单成功后清除购物车的的信息
                    if(isset($params['status']) && $params['status'] == 'fastbuy'){
                        unset($_SESSION['S[Cart_Fastbuy]']);//立即购买后清空session,普通购买清空购物车
                    }else{
                        $this->cart_model->remove_object();
                    }
                    */

                    // 得到物流公司名称
                    if($order_data['order_objects']){
                        $itemNum = 0;
                        $good_id = "";
                        $goods_name = "";
                        foreach($order_data['order_objects'] as $arr_objects){
                            if($arr_objects['order_items']){
                                if($arr_objects['obj_type'] == 'goods'){
                                    $good_id = $arr_objects['order_items'][0]['goods_id'];
                                    $obj_goods->updateRank($good_id, 'buy_count',$arr_objects['order_items'][0]['quantity']);
                                    $arr_goods = $obj_goods->dump($good_id);
                                }

                                foreach($arr_objects['order_items'] as $arr_items){
                                    $itemNum = $this->objMath->number_plus(array($itemNum, $arr_items['quantity']));
                                    if($arr_objects['obj_type'] == 'goods'){
                                        if($arr_items['item_type'] == 'product')
                                        $goods_name .= $arr_items['name'] . ($arr_items['products']['spec_info'] ? '(' . $arr_items['products']['spec_info'] . ')' : '') . '(' . $arr_items['quantity'] . ')';
                                    }
                                }
                            }
                        }
                        
                        $arr_dlytype = $obj_dlytype->dump($order_data['shipping']['shipping_id'], 'dt_name');
                        $arr_updates = array(
                            'order_id' => $order_id,
                            'total_amount' => $order_data['total_amount'],
                            'shipping_id' => $arr_dlytype['dt_name'],
                            'ship_mobile' => $order_data['consignee']['mobile'],
                            'ship_tel' => $order_data['consignee']['telephone'],
                            'ship_addr' => $order_data['consignee']['addr'],
                            'ship_email' => $order_data['consignee']['email'] ? $order_data['consignee']['email'] : '',
                            'ship_zip' => $order_data['consignee']['zip'],
                            'ship_name' => $order_data['consignee']['name'],
                            'member_id' => $order_data['member_id'] ? $order_data['member_id'] : 0,
                            'uname' => (!$order_data['member_id']) ? app::get('b2c')->_('顾客') : $arrMember['uname'],
                            'itemnum' => count($order_data['order_objects']),
                            'goods_id' => $good_id,
                            //'goods_url' => kernel::base_url(1).kernel::url_prefix().$this->gen_url(array('app'=>'b2c','ctl'=>'site_product','act'=>'index','arg0'=>$good_id)),
                            'thumbnail_pic' => base_storager::image_path($arr_goods['image_default_id']),
                            'goods_name' => $goods_name,
                            'ship_status' => '',
                            'pay_status' => 'Nopay',
                            'is_frontend' => true,
                        );
                        $this->obj_order->fireEvent('create', $arr_updates, $order_data['member_id']);
                    }

                   

                    /** 订单创建结束后执行的方法 **/
                    $odr_create_service = kernel::servicelist('b2c_order.create');
                    $arr_order_create_after = array();
                    if($odr_create_service){
                        foreach($odr_create_service as $odr_ser){
                            if(!is_object($odr_ser)) continue;

                            if(method_exists($odr_ser,'get_order'))$index = $odr_ser->get_order();
                            else $index = 10;

                            while(true) {
                                if( !isset($arr_order_create_after[$index]) )break;
                                $index++;
                            }
                            $arr_order_create_after[$index] = $odr_ser;
                        }
                    }
                    ksort($arr_order_create_after);
                    if($arr_order_create_after){
                        foreach($arr_order_create_after as $obj){
                            $obj->generate($order_data);
                        }
                    }
                    /** end **/

                    //更新会员订单数量
                    $order_num = $this->obj_order->count(array('member_id' => $order_data['member_id']));
                    $obj_member->update(array('order_num'=>$order_num), array('member_id'=>$order_data['member_id']));

                    // 与中心交互
                    $is_need_rpc = false;
                    $obj_rpc_obj_rpc_request_service = kernel::servicelist('b2c.rpc_notify_request');
                    foreach($obj_rpc_obj_rpc_request_service as $obj){
                        if($obj && method_exists($obj, 'rpc_judge_send')){
                            if($obj instanceof b2c_api_rpc_notify_interface)
                                $is_need_rpc = $obj->rpc_judge_send($order_data);
                        }

                        if($is_need_rpc) break;
                    }

                    if ($is_need_rpc){
                        //新的版本控制api
                        $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
                        $obj_apiv->rpc_caller_request($order_data, 'ordercreate');
                    }
                    //保存该订单订单号
                    $aOrders[] = $order_id;


                }else{
                    //插入订单不成功 db 事务回滚
                    $db->rollback();
                    //订单生成失败 将flag设为 false 
                    $flag = false;
                    $this->send(false,null,'生成订单失败');
                   }

   
               
            }        // 按照 店铺的配送方式 分单 foreach 结束
        }            // 第一个foreach 结束 按照店铺分单
 
        if($flag){

            //当所有的分单数据全部创建成功 db事务才能commit
            $db->commit($transaction_status);

            // 所有的订单生成成功后清除购物车的信息
            if(isset($params['status']) && $params['status'] == 'fastbuy'){
                unset($_SESSION['S[Cart_Fastbuy]']);//立即购买后清空session,普通购买清空购物车
            }else{
                $this->cart_model->remove_object(); //普通加入购物车购买 清空cart数据
            }

            //  订单通知
            if(app::get('b2c')->getConf('webcall.ordernotice.enabled') == 'true'){
                $webcall_service = kernel::service('api.b2c.webcall');
                if($webcall_service && method_exists($webcall_service, 'orderNotice')){
                    foreach($aOrders as $items){
                        $webcall_service->orderNotice($items);
                    }
                }
            }
            //end
            
            $data = $this->get_order_detail($aOrders);
            $this->send(true,$data,'生成订单成功');
        }else{
            $this->send(false,null,'生成订单失败');
        }
    }
    
    //取得订单数据。
    function get_order_data($aCart,$postData,$store_id,&$msg,$arrMember,$obj_order_create,$db){
        $obj_filter = kernel::single('b2c_site_filter');
        $postData = $obj_filter->check_input($postData);
      
        $postData['order_id'] = $order_id = $this->obj_order->gen_id();
        $postData['member_id'] = $arrMember['member_id'] ? $arrMember['member_id'] : 0;
        $order_data = array();
       
        if(!is_object($obj_order_create)){
            $obj_order_create = kernel::single("b2c_order_create");
        }
        // 加入订单能否生成的判断
        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        if ($obj_checkorder && !$obj_checkorder->check_create($aCart, $postData['delivery']['ship_area'], $message)){
            $this->send(false, null, $message);exit;
        }
        // $postData[split_order]传入生成订单总金额 运费等订单sdf数据
        $order_data = $obj_order_create->generate($postData,'',$msg,$aCart,$store_id);
        $order_data['store_id'] = $store_id;
        $order_data['source'] = 'phone'; //订单来源 手机APP
        $obj_checkproducts = kernel::servicelist('b2c_order_check_products');
        if ($obj_checkproducts) {
            foreach($obj_checkproducts as $obj_check){
                if (!$obj_check->check_products($order_data, $messages)){
                      $this->send(false, null, $message);exit;
                    }
            }
        }
        if (!$order_data || !$order_data['order_objects']){
            $db->rollback();
            $this->send(false, null, $msg);exit;
        }

        if($order_data['shipping']['shipping_id'] == null || $order_data['shipping']['shipping_id'] == ''){
            $db->rollback();
            $this->send(false, null, app::get('b2c')->_("请选择店铺配送方式！"));exit;
        }
        return $order_data;
    }
    
    //为分单准备post数据。
    function get_post_cart($aCart,$sOrder,$postData,$oShipping,$db){
        $post=array();
        if(isset($postData['purchase'])){
            $post['purchase']=$postData['purchase'];
        }
        if(isset($postData['extends_args'])){
            $post['extends_args']=$postData['extends_args'];
        }
        if(isset($postData['delivery'])){
            $post['delivery']=$postData['delivery'];
        }
        if(isset($postData['payment'])){
            $post['payinfo'] = array(
                'pay_app_id'=> $postData['payment']['pay_app_id'],
                'cost_payment'=>$postData['payment']['cost_payment']
                           
           );
            $post['currency'] = $postData['payment']['currency']; 
            $post['payment'] = $postData['payment'];

        }
        if(isset($postData['fromCart'])){
            $post['fromCart']=$postData['fromCart'];
        }
        //$postData['split_order']应该是没有传递过来 在PC上作为split_order POST到服务器的
        if(isset($postData['split_order'])){
            $post['split_order']= $postData['split_order'];// 很重要要作为统计订单总额的数据源
        }
        if(isset($postData['minfo'])){
            $post['minfo']=$postData['minfo'];
        }

        // 配送方式为 到店自提时 替换掉ship_addr
        $mdl_dlytype = app::get('b2c')->model('dlytype');
        $dt = $mdl_dlytype->getRow('dt_self',array('dt_id'=>$oShipping['shipping_id']));
        if($dt['dt_self']==1){
          $mdl_regionsshop = app::get('ectools')->model('regions_shop');
          $shop = $mdl_regionsshop->getRow('shop_name,shop_addr',array('shop_id'=>$oShipping['shop_id']));
          if(!$shop){
              $db->rollback();
              $this->send(false,null,'地址所在地区没有门店,请选择其他的配送方式');
          }
          $post['delivery']['ship_addr'] = $shop['shop_name'].$shop['shop_addr'];
        }
        // end
        $post['delivery']['shipping_id'] = $oShipping['shipping_id'];
        $post['delivery']['is_protect'][$shipping_id] = $oShipping['is_protect'];
        $post['is_protect'][]= $oShipping['is_protect'];
        $post['shipping_id'][]= $oShipping['shipping_id'];
        $post['memo'] = $oShipping['memo'];
        $post['payment']['dis_point']=0;

        if($this->point_usage==2&& $this->get_policy_method!=1){
            if($this->dis_point>0 && $this->site_point_deductible_value>0 &&$this->dis_point_money>0){
                //订单余额。
                $subtotal=$aCart['subtotal'] - $aCart['subtotal_discount'];//+$cost_protect;
                
                if($subtotal>$this->dis_point_money){//订单余额满足积分抵扣金额。
                    $post['payment']['dis_point']=$this->dis_point;
                    $this->dis_point=0;
                }else{
                    $tdis_point_money=$subtotal;
                    //根据抵扣钱数重新计算抵扣积分。
                    $post['payment']['dis_point']=$tdis_point_money/$this->site_point_deductible_value;
                    $this->dis_point-=$post['payment']['dis_point'];
                    $this->dis_point_money-=$tdis_point_money;
                }
            }
        }
        //print_r($post);
        return $post;
    }
    //根据分单数据重新构建购物车结构，以便直接生成订单结构。
    function get_split_cart(&$allCart,$sOrder,$postData,$oShipping,$store_id){
        $sCart=array();        
        foreach($sOrder['object'] as $obj_type=>$obj){
           foreach($obj['index'] as $index){
                $sCart['object'][$obj_type][]=$allCart['object'][$obj_type][$index];
           }
        }
         
        $this->mCart->count_objects($sCart);
        if($allCart['promotion']){
            foreach($allCart['promotion'] as $pkey=> $promotion){
                foreach($promotion as $ptypekey=> $pcoupon){
                    if($allCart['is_free_shipping'][$store_id]){//免运费
                        if($pcoupon['store_id']==$store_id){
                            $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                        }                              
                    }else{//折扣或者送优惠券
                        if($pcoupon['store_id']==$store_id){
                            if($pcoupon['discount_amount']>0){                                   
                               if($sCart['subtotal']>$pcoupon['discount_amount']){
                                    
                                    $sCart['discount_amount']=$pcoupon['discount_amount'];
                                    $sCart['subtotal_discount']=$pcoupon['discount_amount'];
                                    $sCart['discount_amount_order']=$pcoupon['discount_amount'];
                                    $allCart['promotion'][$pkey][$ptypekey]=$pcoupon['discount_amount']-$sCart['subtotal'];
                               }
                               $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                            }else if($pcoupon['discount_amount']==0){//送优惠券。
                               $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                               unset($allCart['promotion'][$pkey][$ptypekey]);
                            }
                        }
                    }
                }
            }
        }
        if($sCart['promotion']){
            if($allCart['object']['coupon']){
               foreach($allCart['object']['coupon'] as $coupon){
                   if($coupon['store_id']==$store_id){
                        $sCart['object']['coupon'][]=$coupon;
                   }
               }
            }
        }
        if($allCart['is_free_shipping']){
            $sCart['is_free_shipping']=$allCart['is_free_shipping'][$store_id];
        }
        if($allCart['free_shipping_rule_type']){
            $sCart['free_shipping_rule_type']=$allCart['free_shipping_rule_type'][$store_id];
        }
        if($allCart['free_shipping_rule_id']){
            $sCart['free_shipping_rule_id']=$allCart['free_shipping_rule_id'][$store_id];
        }        
        if(isset($allCart['inAct'])){
           $sCart['inAct']=$allCart['inAct'];
        }
        if(isset($allCart['subtotal_store_good_price'])){
            $sCart['subtotal_store_good_price']=$allCart['subtotal_store_good_price'];
        }
        if(isset($allCart['store_free_shipping'])){
            $sCart['store_free_shipping']=$allCart['store_free_shipping'];
        }
        if(isset($allCart['subtotal_weight_store'])){
            $sCart['subtotal_weight_store']=$allCart['subtotal_weight_store'];
        }
        //print_r($sCart);
        return $sCart;
    }
    
    function get_order_detail($orders){

        if((!empty($orders)) && is_array($orders)){
           $str_order_id = implode(',',$orders);
           
           $sql = 'SELECT o.order_id,o.final_amount,o.total_amount FROM sdb_b2c_orders AS o WHERE o.order_id  IN ('.$str_order_id.') ';
           $aData = $this->obj_order->db->select($sql);
           if(!empty($aData)){
               $Data['total_amount'] = 0.0;
               foreach($aData as $key => $value){
       
                   $Data['total_amount'] += $value['total_amount'];
                   $Data['final_amount'] += $value['final_amount'];
                   $Data['orders'][] = $value['order_id'];
                  }
             unset($aData);
             return $Data;
           }else{
             return array();
           }
          
        }else{
          return array();
        }
        
    }
    
    private function arrContentReplace($array){
        if(is_array($array)){
            foreach($array as $key => $v){
                $array[$key] = $this->arrContentReplace($array[$key]);
            }
        }else{
            $array = strip_tags($array);
        }
        return $array;
    }


    /**取消订单**/
    function gocancel(){
        $params = $this->params;
        $must_params = array(
            'session'=>'会员ID',
            'order_id'=>'订单ID',
        );
        $this->check_params($must_params);
        $member = $this->get_current_member(); 
        if(empty($member['member_id'])){
            $this->send(false,null,app::get('b2c')->_('session失效或您还未登录'));
        }
        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        if(!$obj_checkorder->check_order_cancel($params['order_id'],'',$message))
        {
            $this->send(false,null,$message);
           
        }
        $sdf['order_id'] = $params['order_id'];
        $sdf['op_id'] = $member['member_id'];       //用户ID     
        $sdf['opname'] = $member['uname'];          //获取用户名
        $b2c_order_cancel = kernel::single("b2c_order_cancel");
        if($b2c_order_cancel->generate($sdf, $b2c_order_cancel, $message)){
            //ajx crm
            $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
            $req_arr['order_id']=$params['order_id'];
            $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
            //begin by lijun
            $order_id = $params['order_id'];
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
            $this->send(true,null,app::get('b2c')->_('订单取消成功！'));
        }else{
            $this->send(false,null,app::get('b2c')->_('订单取消失败！'));
        }


    }

    /**确认收货**/
    function gofinish(){
        $params = $this->params;
        $must_params = array(
            'session'=>'会员ID',
            'order_id'=>'订单ID',
        );
        $this->check_params($must_params);
        $member = $this->get_current_member(); 
        if(empty($member['member_id'])){
            $this->send(false,null,app::get('b2c')->_('session失效或您还未登录'));
        }
        $obj_order_bills = app::get('ectools')->model('order_bills');
        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        if (!$obj_checkorder->check_order_finish($params['order_id'],'',$message))
        {
            $this->send(false,null,$message);
        }
        
        $point_money_value = app::get('b2c')->getConf('site.point_money_value');
        
        $sdf['order_id'] = $params['order_id'];
        $sdf['op_id'] = $member['member_id'];
        $sdf['opname'] = $member['uname'];
        $sdf['confirm_time'] = time();
        $b2c_order_finish = kernel::single("b2c_order_finish");
        $system_money_decimals = app::get('b2c')->getConf('system.money.decimals');
        $system_money_operation_carryset = app::get('b2c')->getConf('system.money.operation.carryset');

        if($b2c_order_finish->generate($sdf, $b2c_order_finish, $message)){
           //生成结算单
            $money =  $this->obj_order->getRow('payed,pmt_order,cost_freight,is_protect,cost_protect,cost_payment,member_id,ship_status,score_u,score_g,discount_value',array('order_id'=>$params['order_id']));

            $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
            $sdf_order = $this->obj_order->dump($sdf['order_id'],'*',$subsdf);
            
            $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');
            $sdf['payment'] = ($sdf['payment']) ? $sdf['payment'] : $sdf_order['payinfo']['pay_app_id'];
            $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);

            $time = time();
            $sdf['pay_app_id'] = $sdf['payment'];
            $sdf['member_id'] = $sdf_order['store_id'] ? $sdf_order['store_id'] : 0;
            $sdf['currency'] = $sdf_order['currency'];
            $sdf['paycost'] = 0;
          //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
            $sdf['t_begin'] = $time;
            $sdf['t_payed'] = $time;
            $sdf['t_confirm'] = $time;
            $sdf['pay_object'] = 'order';
            
            $return_product_obj = app::get('aftersales')->model('return_product');
            $returns = $return_product_obj->getList('amount',array('order_id'=>$sdf['order_id'],'refund_type|in'=>array('3','4'),'status'=>'3'));
            if($returns[0]['amount']){
                if($money['is_protect']){
                    $cost_freight = $money['cost_freight']+$money['cost_payment']+$money['cost_protect']-$returns[0]['amount'];
                }else{
                    $cost_freight = $money['cost_freight']+$money['cost_payment']-$returns[0]['amount'];
                }
                if($money['discount_value'] > 0){
                    $total_money = ($money['payed'])+$money['pmt_order']-$cost_freight+$money['discount_value'];
                }else{
                    $total_money = ($money['payed'])+$money['pmt_order']-$cost_freight;
                }
                $obj_items = app::get('b2c')->model('order_items');
                $items = $obj_items->getList('*',array('order_id'=>$sdf['order_id']));
                //退款金额小于运费
                if($cost_freight >= 0){
                    $profit = 0;
                    foreach($items as $k=>$v){
                        $obj_cat = app::get('b2c')->model('goods_cat');
                        $obj_goods = app::get('b2c')->model('goods');
                        $cat_id = $obj_goods->dump($v['goods_id'],'cat_id');
                        $profit_point = $obj_cat->dump($cat_id['category']['cat_id'],'profit_point');
                        if(is_null($profit_point['profit_point'])){
                            $parent_id = $obj_cat->dump($cat_id['category']['cat_id'],'parent_id');
                            $profit_point = $obj_cat->dump($parent_id['parent_id'],'profit_point');
                        }
                        if($total_money>0){
                            $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum']*(1-($money['pmt_order']/$total_money));
                        }else{
                            $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum'];
                        }
                    }
                    $freight_pro = app::get('b2c')->getConf('member.profit');
                    $profit = $profit + $cost_freight*($freight_pro/100);
                }else{
                    $freight_pro = app::get('b2c')->getConf('member.profit');
                    if($money['discount_value'] > 0){
                        $total_money = ($money['payed']+($money['discount_value']))*($freight_pro/100);
                    }else{
                        $total_money = ($money['payed'])*($freight_pro/100);
                    }
                }
                //计算系统价格 
                $math = kernel::single("ectools_math");
                $profit = $math->formatNumber($profit, $system_money_decimals, $system_money_operation_carryset);

                if($money['discount_value'] > 0){
                    $sdf['money'] = ($money['payed']+($money['discount_value']))-$profit;
                }else{
                    $sdf['money'] = ($money['payed'])-$profit;
                }

                if($money['score_g'] > 0){
                    $sdf['money'] = $sdf['money']-($money['score_g'])/$point_money_value;
                    $sdf['score_cost'] = ($money['score_g'])/$point_money_value;
                }
                
                //end
                $sdf['return_score'];

                $refunds = app::get('ectools')->model('refunds');
                //$objOrder->op_id = $this->user->user_id;
                //$objOrder->op_name = $this->user->user_data['account']['name'];
                //$sdf['op_id'] = $this->user->user_id;
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                
                $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');

                $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);
                    
                $sdf['refund_id'] = $refund_id = $refunds->gen_id();
                $sdf['cur_money'] = $sdf['money'];
                //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
                $sdf['op_id'] = $money['member_id'];
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                $sdf['status'] = 'ready';
                $sdf['app_name'] = $arrPaymentInfo['app_name'];
                $sdf['app_version'] = $arrPaymentInfo['app_version'];
                $sdf['refund_type'] = '2';
                $obj_ys = app::get('business')->model('storemanger');
                $ys = $obj_ys->getRow('*',array('store_id'=>$sdf['member_id']));
                $sdf['account'] = $ys['company_name'];
                $sdf['profit'] = $profit;
                
                if (!$obj_checkorder->check_order_refund($sdf['order_id'],$sdf,$message))
                {
                     $this->send(false,null,$message);
                }
                $res = $obj_order_bills->dump(array('rel_id'=>$sdf['order_id'],'bill_type'=>'blances'),'bill_id');
                if(!$res){
                    $obj_refunds = kernel::single("ectools_refund");
                    $rs_seller = $obj_refunds->generate($sdf, $b2c_order_finish, $msg);

                    // 增加经验值
                    $obj_member = app::get('b2c')->model('members');
                    $obj_member->change_exp($money['member_id'], floor($total_money));
                }
            }elseif($money['ship_status'] == '3'){
                //部分退款的确认收货
                $obj_items = app::get('b2c')->model('order_items');
                $items = $obj_items->getList('*',array('order_id'=>$sdf['order_id']));
                
                $payed = 0;
                foreach($items as $k=>$v){
                    $payed = $payed+$v['price']*$v['sendnum'];
                }
                $payed = $payed - $money['pmt_order'];
                //剩余可打金额
                $return_product_obj = app::get('aftersales')->model('return_product');
                $amount = $return_product_obj->getRow('amount',array('order_id'=>$sdf['order_id'],'status'=>'6'));
                if($money['discount_value'] > 0){
                    $money_useful = ($money['payed'])+($money['discount_value']);
                }else{
                    $money_useful = ($money['payed']);
                }
                //剩余杂费
                $cost_freight = $money_useful - $payed;

                $total_money = $payed+$money['pmt_order'];

                $profit = 0;
                foreach($items as $k=>$v){
                    $obj_cat = app::get('b2c')->model('goods_cat');
                    $obj_goods = app::get('b2c')->model('goods');
                    $cat_id = $obj_goods->dump($v['goods_id'],'cat_id');
                    $profit_point = $obj_cat->dump($cat_id['category']['cat_id'],'profit_point');
                    if(is_null($profit_point['profit_point'])){
                        $parent_id = $obj_cat->dump($cat_id['category']['cat_id'],'parent_id');
                        $profit_point = $obj_cat->dump($parent_id['parent_id'],'profit_point');
                    }
                    if($total_money>0){
                        $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum']*(1-($money['pmt_order']/$total_money));
                    }else{
                        $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum'];
                    }
                }
                $freight_pro = app::get('b2c')->getConf('member.profit');
                $profit = $profit + $cost_freight*($freight_pro/100);

                //计算系统价格 
                $math = kernel::single("ectools_math");
                $profit = $math->formatNumber($profit, $system_money_decimals, $system_money_operation_carryset);

                if($money['score_g'] > 0){
                    $sdf['money'] = $money_useful-$profit-($money['score_g'])/$point_money_value;
                    $sdf['score_cost'] = ($money['score_g'])/$point_money_value;
                }else{
                    $sdf['money'] = $money_useful-$profit;
                }
                //end

                $sdf['return_score'];

                $refunds = app::get('ectools')->model('refunds');
                //$objOrder->op_id = $this->user->user_id;
                //$objOrder->op_name = $this->user->user_data['account']['name'];
                //$sdf['op_id'] = $this->user->user_id;
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                
                $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');

                $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);
                    
                $sdf['refund_id'] = $refund_id = $refunds->gen_id();
                $sdf['cur_money'] = $sdf['money'];
                //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
                $sdf['op_id'] = $money['member_id'];
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                $sdf['status'] = 'ready';
                $sdf['app_name'] = $arrPaymentInfo['app_name'];
                $sdf['app_version'] = $arrPaymentInfo['app_version'];
                $sdf['refund_type'] = '2';

                $obj_ys = app::get('business')->model('storemanger');
                $ys = $obj_ys->getRow('*',array('store_id'=>$sdf['member_id']));
                $sdf['account'] = $ys['company_name'];
                $sdf['profit'] = $profit;

                if (!$obj_checkorder->check_order_refund($sdf['order_id'],$sdf,$message))
                {
                     $this->send(false,null,$message);
                }
                $res = $obj_order_bills->dump(array('rel_id'=>$sdf['order_id'],'bill_type'=>'blances'),'bill_id');
                if(!$res){
                    $obj_refunds = kernel::single("ectools_refund");
                    $rs_seller = $obj_refunds->generate($sdf, $b2c_order_finish, $msg);

                    // 增加经验值
                    $obj_member = app::get('b2c')->model('members');
                    $obj_member->change_exp($money['member_id'], floor($total_money));
                }
            }else{
                //进行提成计算（正常流程）
                if($money['is_protect']){
                    $cost_freight = $money['cost_freight']+$money['cost_payment']+$money['cost_protect'];
                }else{
                    $cost_freight = $money['cost_freight']+$money['cost_payment'];
                }
                if($money['discount_value'] > 0){
                    $total_money = $money['payed']+$money['pmt_order']-$cost_freight+($money['discount_value']);
                }else{
                    $total_money = $money['payed']+$money['pmt_order']-$cost_freight;
                }
                $obj_items = app::get('b2c')->model('order_items');
                $items = $obj_items->getList('*',array('order_id'=>$sdf['order_id']));

                $profit = 0;
                foreach($items as $k=>$v){
                    $obj_cat = app::get('b2c')->model('goods_cat');
                    $obj_goods = app::get('b2c')->model('goods');
                    $cat_id = $obj_goods->dump($v['goods_id'],'cat_id');
                    $profit_point = $obj_cat->dump($cat_id['category']['cat_id'],'profit_point');
                    if(is_null($profit_point['profit_point'])){
                        $parent_id = $obj_cat->dump($cat_id['category']['cat_id'],'parent_id');
                        $profit_point = $obj_cat->dump($parent_id['parent_id'],'profit_point');
                    }
                    if($total_money>0){
                        $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum']*(1-($money['pmt_order']/$total_money));
                    }else{
                        $profit = $profit + ($profit_point['profit_point']/100)*$v['price']*$v['sendnum'];
                    }
                }
                $freight_pro = app::get('b2c')->getConf('member.profit');
                $profit = $profit + $cost_freight*($freight_pro/100);

                //计算系统价格 
                $math = kernel::single("ectools_math");
                $profit = $math->formatNumber($profit, $system_money_decimals, $system_money_operation_carryset);

                if($money['discount_value'] > 0 && $money['score_g'] > 0){
                    $sdf['money'] = $money['payed']+($money['discount_value'])-$profit-($money['score_g'])/$point_money_value;
                    $sdf['score_cost'] = ($money['score_g'])/$point_money_value;
                }elseif($money['discount_value'] > 0 && $money['score_g'] == 0){
                    $sdf['money'] = $money['payed']+($money['discount_value'])-$profit;
                }elseif($money['discount_value'] == 0 && $money['score_g'] > 0){
                    $sdf['money'] = $money['payed']-$profit-($money['score_g'])/$point_money_value;
                    $sdf['score_cost'] = ($money['score_g'])/$point_money_value;
                }else{
                    $sdf['money'] = $money['payed']-$profit; 
                }
                //end

                $sdf['return_score'];

                $refunds = app::get('ectools')->model('refunds');
                //$objOrder->op_id = $this->user->user_id;
                //$objOrder->op_name = $this->user->user_data['account']['name'];
                //$sdf['op_id'] = $this->user->user_id;
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                
                $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');

                $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);
                    
                $sdf['refund_id'] = $refund_id = $refunds->gen_id();
                $sdf['cur_money'] = $sdf['money'];
                //$sdf['money'] = $this->objMath->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
                $sdf['op_id'] = $money['member_id'];
                //$sdf['op_name'] = $this->user->user_data['account']['login_name'];
                $sdf['status'] = 'ready';
                $sdf['app_name'] = $arrPaymentInfo['app_name'];
                $sdf['app_version'] = $arrPaymentInfo['app_version'];
                $sdf['refund_type'] = '2';
                $obj_ys = app::get('business')->model('storemanger');
                $ys = $obj_ys->getRow('*',array('store_id'=>$sdf['member_id']));
                $sdf['account'] = $ys['company_name'];
                $sdf['profit'] = $profit;

                if (!$obj_checkorder->check_order_refund($sdf['order_id'],$sdf,$message))
                {
                     $this->send(false,null,$message);
                }
                $res = $obj_order_bills->dump(array('rel_id'=>$sdf['order_id'],'bill_type'=>'blances'),'bill_id');
                if(!$res){
                    $obj_refunds = kernel::single("ectools_refund");
                    $rs_seller = $obj_refunds->generate($sdf, $b2c_order_finish, $msg);

                    // 增加经验值
                    $obj_member = app::get('b2c')->model('members');
                    $obj_member->change_exp($money['member_id'], floor($total_money));
                }
            }
            
            //$this->updateRank($sdf['order_id']); // by cam
            
            //将款项打给卖家
            if($rs_seller){
                $refund = app::get('ectools')->model('refunds');
                $refund_data = $refund->dump($refund_id,'*');
                $bill = app::get('ectools')->model('order_bills');
                $rel_order_id = $bill->dump(array('bill_id'=>$refund_id),'rel_id');
                $payment_id = $refund->get_payment($rel_order_id['rel_id']);
                $obj_payment = app::get('ectools')->model('payments');
                $cur_money = $obj_payment->dump($payment_id['bill_id'],'cur_money,merge_payment_id,trade_no');
                if($refund_data['refund_type'] == '2' && $refund_data['status'] == 'ready'){
                    if($refund_data['pay_app_id'] == 'mbkpay'){
                        if($refund_data['cur_money'] == 0){
                            $obj_refunds = kernel::single("ectools_refund");
                            $ref_rs = $obj_refunds->generate_after(array('refund_id'=>$refund_id,'refund_type'=>'2'));
               
                            if ($ref_rs)
                            {
                                $this->send(true,null, '确认收货成功！');

                            }else{
                                $this->send(true,null, '确认收货成功！');
                            }
                        }else{
                            foreach( kernel::servicelist('mbkpay_tools') as $services ) {
                                if ( is_object($services)) {
                                    if ( method_exists($services, 'amount_transfer') ) {

                                        $confirmData['orderid'] = $rel_order_id['rel_id'];
                                        $confirmData['amount'] = $refund_data['cur_money']*100;
                                        $confirmData['ordertime'] = date('YmdHis',$refund_data['t_begin']);
                                        $confirmData['oldCenterSsn'] = $cur_money['trade_no'];

                                        $result = $services->amount_transfer($confirmData);
                                    }
                                }
                            }
                        }
                        if($result == "SUCCESS_00"){
                            if ($ref_rs)
                            {
                                $this->send(true,null, '确认收货成功！');

                            }else{
                                $this->send(true,null, '确认收货成功！');
                            }
                        }else{
                            $obj_refunds = kernel::single("ectools_refund");
                            $ref_rs = $obj_refunds->generate_after(array('refund_id'=>$refund_id,'refund_type'=>'2'),'failed');
                            $refund->update(array('memo'=>$result),array('refund_id'=>$refund_id));
                            $this->send(true,null, '确认收货成功！'.$result);
                        }
                    }else{
                        $obj_refunds = kernel::single("ectools_refund");
                        $ref_rs = $obj_refunds->generate_after(array('refund_id'=>$refund_id,'refund_type'=>'2'));
           
                        if ($ref_rs)
                        {
                            $this->send(true,null, '确认收货成功！');

                        }else{
                            $this->send(false,null, '确认收货成功！更新结算单状态失败！');
                        }
                    }
                }else{
                    $this->send(false,null,'结算单类型错误！');
                }
            }
            
        }else{
            $this->send(false,null,'确认收货失败！');
        }
    }


      /*根据区域ID 取到当前地区下的自提实体店的名称地址等信息**/
    public  function getmyshop(){
        $params = $this->params;

         $must_params = array(
            'area_id'=>'三级地区ID',
        );
        $this->check_params($must_params);
        $area_id = intval($params['area_id']);
        $obj_regions_shop = app::get('ectools')->model('regions_shop');
        $result = $obj_regions_shop->getList('shop_id,shop_name,shop_addr',array('area_id'=>$area_id),0,-1);
        if($result){  
        $this->send(true,$result,app::get('b2c')->_('门店列表'));
        }else{
        $this->send(true,null,app::get('b2c')->_('没有门店'));
        }
    }


}