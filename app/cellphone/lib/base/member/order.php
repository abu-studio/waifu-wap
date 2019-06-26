<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class cellphone_base_member_order extends cellphone_cellphone{

   /* private static $support_corps = array(
        'EMS' => 'ems',
        'STO' => 'shentong',
        'YTO' => 'yuantong',
        'SF' => 'shunfeng',
        'YUNDA' => 'yunda',
        'APEX' => 'quanyikuaidi',
        'LBEX' => 'longbanwuliu',
        'ZJS' => 'zhaijisong',
        'TTKDEX' => 'tiantian',
        'ZTO' => 'zhongtong',
        'HTKY' => 'huitongkuaidi',
        'CNMH' => 'minghangkuaidi',
        'AIRFEX' => 'yafengsudi',
        'CNKJ' => 'kuaijiesudi',
        'DDS' => 'dsukuaidi',
        'HOAU' => 'huayuwuliu',
        'CRE' => 'zhongtiewuliu',
        'FedEx' => 'fedex',
        'UPS' => 'ups',
        'DHL' => 'dhl',
        'CYEXP' => 'changyuwuliu',
        'DBL' => 'debangwuliu',
        'POST' => 'post',
        'CCES' => 'cces',
        'DTW' => 'datianwuliu',
        'ANTO' => 'andewuliu',
    );
    */

    public function __construct($app){
        parent::__construct();
        $this->app = $app;
    }


    public function getlist(){ 
        $params = $this->params;

        //检查应用级必填参数
        $must_params = array(
            'session'=>'会员ID',
        );
        $this->check_params($must_params);
        $member = $this->get_current_member();
        if(empty($member['member_id'])){
           $this->send(false,null,app::get('b2c')->_('session失效或您还未登录'));
        }
        $member_id = $member['member_id'];//获取当期用户member_id

        if($params['pagelimit']){
            $pagelimit = $params['pagelimit'];
        }else{
            $pagelimit = 10000;
        }
        if($params['nPage']){
            $nPage = $params['nPage'];
        }else{
            $nPage = 1;
        }
        if($params['picSize']){
            $picSize= trim($params['picSize']);
        }else{
            $picSize = 'cs';
        }
        
        //订单类型 @params ordertype
        //nopayed 待付款  ship 待发货   shipped 待收货  comment 未评论 
        //finish 已完成  confirm 待确认  dead 作废
        if($params['ordertype']){
            $ordertype = trim($params['ordertype']);
            if(!in_array($ordertype,array('nopayed','ship','shipped','comment','finish','confirm','dead'))){
               $this->send(false,null,app::get('b2c')->_('不存在此订单类型'));
            }
        }
        $order = &app::get('b2c')->model('orders');
        $sql = $this->get_search_sql($ordertype,$time,$member_id);//$ordertype 没传参数 默认所有订单类型
        $arr_order_id  = $order->db->select($sql);//返回订单ID数组
        if(empty($arr_order_id)){
            $this->send(true,null,app::get('b2c')->_('没有订单信息'));
        }
        $aData = $order->fetchByMember($member_id,$nPage-1,'','',$arr_order_id,$pagelimit);
        $this->get_order_details($aData,'member_orders');
        $oImage = app::get('image')->model('image');
        $imageDefault = app::get('image')->getConf('image.set');
        $applySObj = app::get('spike')->model('spikeapply');
        $applyGObj = app::get('groupbuy')->model('groupapply');
        $applyScoreObj = app::get('scorebuy')->model('scoreapply');
        foreach($aData['data'] as $k=>$v) {
            $obj_payment = app::get('ectools')->model('refunds');
            $payment_id = $obj_payment->get_payment($v['order_id']);
            $pay_time = app::get('ectools')->model('payments')->getRow('t_payed',array('payment_id'=>$payment_id['bill_id']));
            $aData['data'][$k]['pay_time'] = $pay_time['t_payed'];
            $obj_aftersales = app::get('aftersales')->model('return_product');
            $ord_id = $obj_aftersales->getRow('return_id',array('order_id'=>$v['order_id'],'status'=>'3','refund_type'=>'2'));
            if($ord_id){
                $aData['data'][$k]['need_send'] = 1;
            }else{
                $aData['data'][$k]['need_send'] = 0;
            }
            $ord_id = $obj_aftersales->getRow('return_id',array('order_id'=>$v['order_id'],'status'=>'11','refund_type'=>'2'));
            if($ord_id){
                $aData['data'][$k]['need_edit'] = 1;
            }else{
                $aData['data'][$k]['need_edit'] = 0;
            }
            //end
            foreach($v['goods_items'] as $k2=>$v2) {
                if( !$v2['product']['thumbnail_pic'] && !$oImage->getList("image_id",array('image_id'=>$v['image_default_id']))){
                    $aData['data'][$k]['goods_items'][$k2]['product']['thumbnail_pic'] =
                    $this->get_img_url($v['image_default_id'],$picSize);

                }
                $act_id = '';
               // 判断字段order_type的类型 区分活动订单或者正常订单
                switch($v['order_type']){
                    case 'spike':
                        $act_id = $applySObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'group':
                        $act_id = $applyGObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'score':
                        $act_id = $applyScoreObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'normal':
                        break;
                }

                if($act_id){
                    $aData['data'][$k]['goods_items'][$k2]['product']['args'] = array($v2['product']['goods_id'],'','',$act_id);
                }
            }

          //会员用户名
            $aData['data'][$k]['uname'] = $member['uname'];

           /* $obj_strman = app::get('business')->model('storemanger');
            $seller_id = $obj_strman->getRow('account_id,store_idcardname',array('store_id'=>$v['store_id']));
            $seller_name = $obj_members->getRow('login_name',array('account_id'=>$seller_id['account_id']));
            $aData['data'][$k]['seller_name'] = $seller_name['login_name'];
            $aData['data'][$k]['seller_real_name'] = $seller_id['store_idcardname'];
            */
        }


        //整理返回值数组
        $obj_store=&app :: get('business')->model('storemanger');
        $obj_goods=&app :: get('b2c')->model('goods');
       
        foreach($aData['data'] as $key=>&$value){

           /* *根据订单的多个状态(支付状态、发货状态、申请退款状态)
              *来返回订单最终的状态 order_status
            **/
            if($value['status']=='active'){
               if($value['pay_status']=='0' && $value['ship_status']=='0' && $value['refund_status']=='0'){
                  $value['order_status'] = 'nopayed';//待付款
               }
               if($value['pay_status']=='1' && $value['ship_status']=='0' && $value['refund_status']=='0'){
                  $value['order_status'] = 'ship';//待发货
               }
               if($value['pay_status']=='1' && $value['ship_status']=='1' && $value['refund_status']=='0'){
                  $value['order_status'] = 'shipped';//待收货
               }
               if($value['pay_status']=='1' && $value['ship_status']=='2' && $value['refund_status']=='0'){
                  $value['order_status'] = 'some_shipped';//部分发货
               }
               if($value['pay_status']=='1' && $value['refund_status']!='0'){
                  $value['order_status'] = 'refund';//退款退货中
               }
             
            } else if($value['status']=='finish'){
               if($value['pay_status']=='1' && $value['ship_status']=='1' && $value['refund_status']=='0'){
               //交易完成 然后判断订单的评论是否
                  $day_1 = app::get('b2c')->getConf('site.comment_original_time');//评论的有效期
                  $day_2 = app::get('b2c')->getConf('site.comment_additional_time');//追加评论的有效期
                  $day_1 = intval($day_1)?intval($day_1):30;
                  $day_2 = intval($day_2)?intval($day_2):90;
                  if(intval($value['comments_count']) == 0 && intval($value['createtime']) >= strtotime("-{$day_1} day")){
                    $value['order_status'] = 'comment'; // 未评论且还在有效期内 可以评论
                  }
                  else if(intval($value['comments_count']) ==1 && intval($value['createtime']) >= strtotime("-{$day_2} day")){
                    $value['order_status'] = 'addition';// 已评论且在有效期内 追加评论
                  }else{
                    $value['order_status'] = 'finish';  // 其他 设为交易完成
                  }
               }
               if($value['pay_status']=='5' && $value['refund_status']=='4'){
                  $value['order_status'] = 'refund_finish';//退款完成
               }
               
            
            } else{
               $value['order_status'] = 'dead';//交易关闭
            }


            /** end */

            unset(
            $value['memo'],$value['confirm_time'],$value['order_type'], 
            $value['pay_status'],$value['ship_status'],$value['refund_status'],$value['status'],
            $value['createtime'],$value['last_modified'],
            $value['shipping'],$value['confirm'],$value['consignee'],
            $value['weight'],$value['title'],$value['itemnum'],
            $value['ip'],$value['cost_item'],$value['is_tax'],
            $value['cost_tax'],$value['tax_title'],$value['currency'],
            $value['cur_rate'],$value['score_u'],$value['score_g'],
            $value['discount'],$value['pmt_goods'],$value['pmt_order'],
            $value['payed'],$value['disabled'],$value['mark_type'],
            $value['mark_text'],$value['extend'],$value['order_refer'],
            $value['addon'],$value['pay_time'],$value['need_send'],
            $value['comments_count'],$value['act_id'],$value['is_extend'],
            $value['order_kind'],$value['need_edit'],$value['seller_real_name'],
            $value['buy_name'],$value['is_delivery'],$value['pos_id'],
            $value['erp_time'],$value['blance_type'],$value['blance_no']
            );

			//如果是 待评论订单类型 则需要再次过滤掉已经不在评论期限内的订单
            if(trim($params['ordertype'])=='comment'){
			  if($value['order_status']=='finish'){
			  unset($aData['data'][$key]);
			  continue;
			  }
			}
			//end

          //获取订单所在店铺
            $oStore= $obj_store->getList('store_name',array('store_id'=> $value['store_id']));
            if($oStore[0]){
                $value['store_name']=$oStore[0]['store_name'];
            }

            unset($value['goods_items']);

            $value['order_objects'] = array_values($value['order_objects']);

            foreach($value['order_objects'] as $xkey=>&$xvalue){

                unset($xvalue['obj_id'],$xvalue['order_id'],
                        $xvalue['obj_type'],$xvalue['obj_alias'],$xvalue['weight'],$xvalue['price'],
                        $xvalue['goods_id'],$xvalue['bn'],$xvalue['score']
                );

                foreach($xvalue['order_items'] as $ykey=>&$yvalue){
                    unset($yvalue['item_id'],$yvalue['order_id'],
                    $yvalue['obj_id'],$yvalue['goods_id'],
                    $yvalue['type_id'],$yvalue['bn'],
                    $yvalue['sendnum'],$yvalue['addon'],
                    $yvalue['addon'],$yvalue['weight'], $yvalue['g_price'],$yvalue['score'],
                    $yvalue['cost'],
                    //$yvalue['products']['goods_id'],
                    $yvalue['products']['bn'],
                    $yvalue['products']['uptime'],
                    $yvalue['products']['last_modify'],$yvalue['products']['disabled'],
                    $yvalue['products']['status'],
                    $yvalue['products']['title'],
                    $yvalue['products']['store_place'],
                    $yvalue['products']['freez'],
                    $yvalue['products']['store'],
                    $yvalue['products']['unit'],
                    $yvalue['products']['price']['mktprice'],
                    $yvalue['products']['price']['cost'],
                    $yvalue['products']['price'],
                    $yvalue['products']['spec_desc'],
                    $yvalue['products']['weight'], $yvalue['products']['goods_type'],
                    $yvalue['products']['barcode']
                    );

                    $oGoods = $obj_goods->getList('image_default_id,udfimg,thumbnail_pic',array('goods_id'=>$yvalue['products']['goods_id']));
                    $yvalue['products']['image_default_id'] =
                        $this->get_img_url(($oGoods[0]['udfimg']=='true'?$oGoods[0]['thumbnail_pic']:$oGoods[0]['image_default_id']),$picSize);

                }

                $xvalue['order_items'] = array_values($xvalue['order_items']);

            }

            //$value['goods_items']=array_values($value['goods_items']);

        }
       $aData['data'] = array_values($aData['data']);
       if($aData['data']){ 
          $this->send(true,$aData, app::get('b2c')->_('订单列表'));
	   }else{
	      $this->send(true,null,app::get('b2c')->_('订单列表')) ;
	   }
    }


     /**
    *根据时间筛选订单 add by hzy
    * @return array
    */
    private function get_search_sql($type='',$time='',$member_id){

         //解析时间
        $year = date('Y',time());
        $sdb = kernel::database()->prefix;

        $time_sql = "";
        $str_sql;
        //三个月内
        if($time == '3th'){
            $time_sql = " createtime<".time()." AND createtime>".strtotime("-3 month");
        //半年内
        }else if($time == '6th'){
            $time_sql = " createtime<".time()." AND createtime>".strtotime("-6 month");
        //今年
        }else if($time == $year){
            $time_sql = " createtime<".time()." AND createtime>".mktime(0,0,0,1,1,$year);
        //一年前
        }else if($time == '1'){
            $time_sql = " createtime<".mktime(0,0,0,12,31,$year-1);
        }else {
            $time_sql = " 1=1 ";
        }

		//type
		$type_sql='';
		if($type == 'nopayed'){
			$type_sql=" pay_status='0' and status='active' ";//待付款
		}else if($type == 'ship'){
			$type_sql=" pay_status='1' and ship_status='0' and refund_status='0' ";//待发货
		}else if($type == 'shipped'){
			$type_sql=" pay_status='1' and ship_status='1' and status='active'";//待收货
		}else if($type == 'comment'){
			$type_sql="status='finish' and comments_count in(0,1) ";//未评论或追加评论
		}else if($type == 'finish'){
			$type_sql=" status='finish' ";//已完成
		}else if($type == 'confirm'){
			$type_sql=" pay_status='1' and ship_status='1' and status='active' and confirm='N' and refund_status='0' ";//待确认
		}else if($type == 'dead'){
			$type_sql=" status='dead' ";//作废
		}else{
			$type_sql=' 1=1 ';
		}


        $str_sql = "SELECT order_id FROM ".$sdb."b2c_orders WHERE member_id=".$member_id;

        $str_sql.=" AND ". $time_sql.' AND '.$type_sql;

        return $str_sql;

    }

     /**
     * 得到订单列表详细
     * @param array 订单详细信息
     * @param string tpl
     * @return null
     */
    protected function get_order_details(&$aData,$tml='member_orders')
    {
        if (isset($aData['data']) && $aData['data'])
        {
            //$objMath = kernel::single('ectools_math');
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
            foreach ($arr_service_goods_type as $obj_service_goods_type)
            {
                $goods_types = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
            }

            foreach ($aData['data'] as &$arr_data_item)
            {
                $this->get_order_detail_item($arr_data_item,$tml);
            }
        }
    }

    /**
     * 得到订单列表详细
     * @param array 订单详细信息
     * @param string 模版名称
     * @return null
     */
    protected function get_order_detail_item(&$arr_data_item,$tpl='member_order_detail')
    {
        if (isset($arr_data_item) && $arr_data_item)
        {
            $objMath = kernel::single('ectools_math');
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
            foreach ($arr_service_goods_type as $obj_service_goods_type)
            {
                $goods_types = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
            }


            $arr_data_item['goods_items'] = array();
            $obj_specification = &app::get('b2c')->model('specification');
            $obj_spec_values = &app::get('b2c')->model('spec_values');
            $obj_goods = &app::get('b2c')->model('goods');
            if (isset($arr_data_item['order_objects']) && $arr_data_item['order_objects'])
            {
                       
                foreach ($arr_data_item['order_objects'] as $k=>$arr_objects)
                {
                    $index = 0;
                    $index_adj = 0;
                    $index_gift = 0;
                    $image_set = app::get('image')->getConf('image.set');
                    if ($arr_objects['obj_type'] == 'goods')
                    {
                        foreach ($arr_objects['order_items'] as $arr_items)
                        {
                            if (!$arr_items['products'])
                            {
                                $o = &app::get('b2c')->model('order_items');
                                $tmp = $o->getList('*', array('item_id'=>$arr_items['item_id']));
                                $arr_items['products']['product_id'] = $tmp[0]['product_id'];
                            }

                            if ($arr_items['item_type'] == 'product')
                            {
                                if ($arr_data_item['goods_items'][$k]['product'])
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k]['product']['quantity']));
                                else
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $arr_items['quantity'];

                                $arr_data_item['goods_items'][$k]['product']['name'] = $arr_items['name'];
                                $arr_data_item['goods_items'][$k]['product']['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][$k]['product']['price'] = $arr_items['price'];
                                $arr_data_item['goods_items'][$k]['product']['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k]['product']['quantity']);
                                $arr_data_item['goods_items'][$k]['product']['amount'] = $arr_items['amount'];
                                $arr_goods_list = $obj_goods->getList('image_default_id', array('goods_id' => $arr_items['goods_id']));
                                $arr_goods = $arr_goods_list[0];
                                if (!$arr_goods['image_default_id'])
                                {
                                    $arr_goods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                $arr_data_item['goods_items'][$k]['product']['thumbnail_pic'] =
                                             $this->get_img_url($arr_goods['image_default_id'],$picSize);


                                //团购秒杀链接add by ql 2013-7-27
                                if($arr_data_item['order_type']=='group' || $arr_data_item['order_type']=='spike' || $arr_data_item['order_type']=='score'){
                                    switch($arr_data_item['order_type']){
                                        case 'group':
                                            $appName = 'groupbuy';
                                            break;
                                        case 'spike':
                                            $appName = 'spike';
                                            break;
                                        case 'score':
                                            $appName = 'scorebuy';
                                            break;
                                        default:
                                            $appName = 'b2c';
                                    }
                                    $args = array($arr_items['goods_id'],'','',$arr_data_item['act_id']);

                                    //$arr_data_item['goods_items'][$k]['product']['link_url'] = $this->gen_url(array('app'=>$appName,'ctl'=>'site_product','act'=>'index','args'=>$args));
                                }else{
                                    //$arr_data_item['goods_items'][$k]['product']['link_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'site_product','act'=>'index','arg0'=>$arr_items['goods_id']));
                                }
                                if ($arr_items['addon'])
                                {
                                    $arrAddon = $arr_addon = unserialize($arr_items['addon']);
                                    if ($arr_addon['product_attr'])
                                        unset($arr_addon['product_attr']);
                                    $arr_data_item['goods_items'][$k]['product']['minfo'] = $arr_addon;
                                }else{
                                    unset($arrAddon,$arr_addon);
                                }
                                if ($arrAddon['product_attr'])
                                {
                                    foreach ($arrAddon['product_attr'] as $arr_product_attr)
                                    {
                                        $arr_data_item['goods_items'][$k]['product']['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                    }
                                }

                                if (isset($arr_data_item['goods_items'][$k]['product']['attr']) && $arr_data_item['goods_items'][$k]['product']['attr'])
                                {
                                    if (strpos($arr_data_item['goods_items'][$k]['product']['attr'], $this->app->_(" ")) !== false)
                                    {
                                        $arr_data_item['goods_items'][$k]['product']['attr'] = substr($arr_data_item['goods_items'][$k]['product']['attr'], 0, strrpos($arr_data_item['goods_items'][$k]['product']['attr'], $this->app->_(" ")));
                                    }
                                }
                            }
                            elseif ($arr_items['item_type'] == 'adjunct')
                            {
                                $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_items['item_type']];
                                $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);


                                if ($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj])
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity']));
                                else
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity'] = $arr_items['quantity'];

                                if (!$arrGoods['image_default_id'])
                                {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['name'] = $arr_items['name'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity']);
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['price'] = $arr_items['price'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['thumbnail_pic'] =
                                                                             $this->get_img_url($arrGoods['image_default_id'],$picSize);

                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['link_url'] = $arrGoods['link_url'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['amount'] = $arr_items['amount'];

                                if ($arr_items['addon'])
                                {
                                    $arr_addon = unserialize($arr_items['addon']);

                                    if ($arr_addon['product_attr'])
                                    {
                                        foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                        {
                                            $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                        }
                                    }
                                }

                                if (isset($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr']) && $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'])
                                {
                                    if (strpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], $this->app->_(" ")) !== false)
                                    {
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'] = substr($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], 0, strrpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], $this->app->_(" ")));
                                    }
                                }

                                $index_adj++;
                            }
                            else
                            {
                                // product gift.
                                if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                                {
                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_items['item_type']];
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);

                                    if ($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift])
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']));
                                    else
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity'] = $arr_items['quantity'];

                                    if (!$arrGoods['image_default_id'])
                                    {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['name'] = $arr_items['name'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['goods_id'] = $arr_items['goods_id'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['price'] = $arr_items['price'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['thumbnail_pic'] =
                                            $this->get_img_url($arrGoods['image_default_id'],$picSize);

                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity']);
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['link_url'] = $arrGoods['link_url'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['amount'] = $arr_items['amount'];

                                    if ($arr_items['addon'])
                                    {
                                        $arr_addon = unserialize($arr_items['addon']);

                                        if ($arr_addon['product_attr'])
                                        {
                                            foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                            {
                                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }
                                        }
                                    }

                                    if (isset($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr']) && $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'])
                                    {
                                        if (strpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], $this->app->_(" ")) !== false)
                                        {
                                            $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'] = substr($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], 0, strrpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], $this->app->_(" ")));
                                        }
                                    }
                                }
                                $index_gift++;
                            }
                        }
                    }
                    else
                    {
                    
                        if ($arr_objects['obj_type'] == 'gift')
                        {
                            if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                            {
                                foreach ($arr_objects['order_items'] as $arr_items)
                                {
                                    if (!$arr_items['products'])
                                    {
                                        $o = &app::get('b2c')->model('order_items');
                                        $tmp = $o->getList('*', array('item_id'=>$arr_items['item_id']));
                                        $arr_items['products']['product_id'] = $tmp[0]['product_id'];
                                    }

                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_objects['obj_type']];
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);

                                    if (!isset($arr_items['products']['product_id']) || !$arr_items['products']['product_id'])
                                        $arr_items['products']['product_id'] = $arr_items['goods_id'];

                                    if ($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']])
                                        $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']));
                                    else
                                        $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity'] = $arr_items['quantity'];

                                    if (!$arrGoods['image_default_id'])
                                    {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }

                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['name'] = $arr_items['name'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['goods_id'] = $arr_items['goods_id'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['price'] = $arr_items['price'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['thumbnail_pic'] =
                                        $this->get_img_url($arrGoods['image_default_id'],$picSize);

                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['point'] = intval($arr_items['score']*$arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']);
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['nums'] = $arr_items['quantity'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['link_url'] = $arrGoods['link_url'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['amount'] = $arr_items['amount'];

                                    if ($arr_items['addon'])
                                    {
                                        $arr_addon = unserialize($arr_items['addon']);

                                        if ($arr_addon['product_attr'])
                                        {
                                            foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                            {
                                                $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }
                                        }
                                    }

                                    if (isset($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr']) && $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'])
                                    {
                                        if (strpos($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], $this->app->_(" ")) !== false)
                                        {
                                            $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'] = substr($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], 0, strrpos($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], $this->app->_(" ")));
                                        }
                                    }
                                }
                            }
                        }
                        else
                        {
                       
                            if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                            {

                                $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_objects['obj_type']];
                                //$arr_data_item['extends_items'][] = $str_service_goods_type_obj->get_order_object($arr_objects, $arr_Goods,$tpl);
                            }
                            
                            
                        }
                        
                    }
                }
                
                
            }

        }
    }

    //根据订单ID获取订单详细
    public function detail(){

        $params = $this->params;

        //检查应用级必填参数
        $must_params = array(
            'session'=>'会员ID',
            'order_id'=>'订单编号'
        );
        $this->check_params($must_params);
        $member = $this->get_current_member();
        if(empty($member['member_id'])){
        $this->send(false,null,app::get('b2c')->_('session失效或您还未登录'));
        }
        $member_id = $member['member_id'];
        if($params['picSize']){
            $picSize = trim($params['picSize']);
        }else{
            $picSize = 'cs';
        }

        $order_id = $params['order_id'];
        $objOrder = &app::get('b2c')->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))), 'order_pmt'=>array('*'));
        $sdf_order = $objOrder->dump($order_id, '*', $subsdf);
        if(!$sdf_order||$member_id!=$sdf_order['member_id']){
           $this->send(false,null,app::get('b2c')->_('订单号：') . $order_id . app::get('b2c')->_('不存在！'));
        }
      //获取订单所在店铺及卖家手机号
        $obj_store=&app :: get('business')->model('storemanger');
        $oStore= $obj_store->getList('store_name,tel,company_ctel',array('store_id'=>$sdf_order['store_id']));
        if($oStore[0]){
            $sdf_order['store_name']=$oStore[0]['store_name'];
			$sdf_order['seller_phone'] = $oStore[0]['tel'];
			$sdf_order['seller_company_phone'] = $oStore[0]['company_ctel'];
        }

      //end

        /**根据订单的多个状态(支付状态、发货状态、申请退款状态)来返回订单最终的状态 order_status**/
        if($sdf_order['status']=='active'){
           if($sdf_order['pay_status']=='0' && $sdf_order['ship_status']=='0' && $sdf_order['refund_status']=='0'){
              $sdf_order['order_status'] = 'nopayed';//待付款
           }
           if($sdf_order['pay_status']=='1' && $sdf_order['ship_status']=='0' && $sdf_order['refund_status']=='0'){
              $sdf_order['order_status'] = 'ship';//待发货
           }
           if($sdf_order['pay_status']=='1' && $sdf_order['ship_status']=='1' && $sdf_order['refund_status']=='0'){
              $sdf_order['order_status'] = 'shipped';//待收货
           }
           if($sdf_order['pay_status']=='1' && $sdf_order['ship_status']=='2' && $sdf_order['refund_status']=='0'){
              $sdf_order['order_status'] = 'some_shipped';//部分发货
           }
           if($sdf_order['pay_status']=='1' && $sdf_order['refund_status']!='0'){
              $sdf_order['order_status'] = 'refund';//退款退货中
           }
         
        } else if($sdf_order['status']=='finish'){
             if($sdf_order['pay_status']=='1' && $sdf_order['ship_status']=='1' && $sdf_order['refund_status']=='0'){
               //交易完成 然后判断订单的评论是否
                  $day_1 = app::get('b2c')->getConf('site.comment_original_time');//评论的有效期
                  $day_2 = app::get('b2c')->getConf('site.comment_additional_time');//追加评论的有效期
                  $day_1 = intval($day_1)?intval($day_1):30;
                  $day_2 = intval($day_2)?intval($day_2):90;
                  if(intval($sdf_order['comments_count']) == 0 && intval($sdf_order['createtime']) >= strtotime("-{$day_1} day")){
                    $sdf_order['order_status'] = 'comment'; // 未评论且还在有效期内 可以评论
                  }
                  else if(intval($sdf_order['comments_count']) ==1 && intval($sdf_order['createtime']) >= strtotime("-{$day_2} day")){
                    $sdf_order['order_status'] = 'addition';// 已评论且在有效期内 追加评论
                  }else{
                    $sdf_order['order_status'] = 'finish';  // 其他 设为交易完成
                  }
               }
               if($sdf_order['pay_status']=='5' && $sdf_order['refund_status']=='4'){
                  $sdf_order['order_status'] = 'refund_finish';//退款完成
               }
        } else{
           $sdf_order['order_status'] = 'dead';//交易关闭
        }
        unset(     $sdf_order['pay_status'],$sdf_order['ship_status'],
                   $sdf_order['status'],$sdf_order['refund_status'],
                   $sdf_order['order_kind'],$sdf_order['blance_type'],
                   $sdf_order['blance_no'],$sdf_order['pos_id'],$sdf_order['erp_time'],
                   $sdf_order['mark_type'],$sdf_order['mark_text'],$sdf_order['extend'],
                   $sdf_order['order_refer'],$sdf_order['addon'],$sdf_order['disabled']
              );
        /** end */
        
        /** 处理收货地址信息**/
        $arr_consignee_area = array();
        $arr_consignee_regions = array();
        if (strpos($sdf_order['consignee']['area'], ':') !== false)
        {
            $arr_consignee_area = explode(':', $sdf_order['consignee']['area']);
            if ($arr_consignee_area[1])
            {
                if (strpos($arr_consignee_area[1], '/') !== false)
                {
                    $arr_consignee_regions = explode('/', $arr_consignee_area[1]);
                }
            }

            $sdf_order['consignee']['area'] = (is_array($arr_consignee_regions) && $arr_consignee_regions) ? $arr_consignee_regions[0] . $arr_consignee_regions[1] . $arr_consignee_regions[2] : $sdf_order['consignee']['area'];
        }

        /**订单的相关信息的修改**/
       /* $obj_other_info = kernel::servicelist('b2c.order_other_infomation');
        if ($obj_other_info)
        {
            foreach ($obj_other_info as $obj)
            {
                $this->pagedata['discount_html'] = $obj->gen_point_discount($sdf_order);
            }
        }
        */

        $order_items = array();
        $gift_items = array();
        $this->get_order_detail_item($sdf_order,'member_order_detail');


        /** 去掉商品优惠 **/
        /*
        if ($this->pagedata['order']['order_pmt'])
        {
            foreach ($this->pagedata['order']['order_pmt'] as $key=>$arr_pmt)
            {
                if ($arr_pmt['pmt_type'] == 'goods')
                {
                    unset($this->pagedata['order']['order_pmt'][$key]);
                }
            }
        }
        */
        /** end **/

     // 得到订单留言.
        $oMsg = &kernel::single("b2c_message_order");
        $arrOrderMsg = $oMsg->getList('*', array('order_id' => $order_id, 'object_type' => 'order'), $offset=0, $limit=-1, 'time DESC');

        $sdf_order['ordermsg']= $arrOrderMsg;

     // 生成订单日志明细
       
        $arr_order_logs = $objOrder->getOrderLogList($order_id);
        $sdf_order['orderlogs'] = $arr_order_logs['data'];
       
     /*
        //创建时间
        $sdf_order[ 'creates_time']='';
        //付款时间
        $sdf_order[ 'payments_time']='';
        //更新时间
        $sdf_order['updates_time']='';
        //退款时间
        $sdf_order['refunds_time']='';
        //发货时间
        $sdf_order['delivery_time']='';
        //换货时间
        $sdf_order['reship_time']='';
        //完成时间
        $sdf_order['finish_time']='';
        //取消时间
        $sdf_order['cancel_time']='';
        //更改价格时间
        $sdf_order['change_price_time']='';
        //延长收货时间的时间
        $sdf_order['extend_time_time']='';

        $obj_orderlog = &app::get('b2c')->model('order_log');
        $oOrderlog = $obj_orderlog->getList('*',array('rel_id'=>$sdf_order['order_id'],'bill_type'=>'order','result'=>'SUCCESS'));

        foreach($oOrderlog as $item){
            switch($item['behavior']) {
               case 'creates':
                   $sdf_order[ 'creates_time']=$item['alttime'];
                   break;
               case 'payments':
                    $sdf_order[ 'payments_time']=$item['alttime'];
                   break;
               case 'updates' :
                    $sdf_order[ 'updates_time']=$item['alttime'];
                   break;
               case 'refunds':
                    $sdf_order[ 'refunds_time']=$item['alttime'];
                   break;
               case 'delivery':
                    $sdf_order[ 'delivery_time']=$item['alttime'];
                   break;
               case 'reship' :
                    $sdf_order[ 'reship_time']=$item['alttime'];
                   break;
               case 'finish':
                    $sdf_order[ 'finish_time']=$item['alttime'];
                   break;
               case 'cancel':
                    $sdf_order[ 'cancel_time']=$item['alttime'];
                   break;
               case 'change_price':
                    $sdf_order[ 'change_price_time']=$item['alttime'];
                   break;
               case 'extend_time':
                    $sdf_order[ 'extend_time_time']=$item['alttime'];
                   break;
            }

        }
     */
/*
     // 取到支付单信息
        $obj_payment = &app::get('ectools')->model('payments');
        $oPayment = $obj_payment->get_payments_by_order_id($sdf_order['order_id']);
        $sdf_order['payment_id'] = $oPayment[0]['payment_id'];//支付单号

     // 物流跟踪安装并且开启
        $logisticst = app::get('b2c')->getConf('system.order.tracking');
        $logisticst_service = kernel::service('b2c_change_orderloglist');
        if(isset($logisticst) && $logisticst == 'true' && $logisticst_service){
            $sdf_order['services']['logisticstack'] = $logisticst_service;
        }
*/
      //整理返回值数组 order_objects
        $sdf_order['order_objects']=array_values($sdf_order['order_objects']);

        foreach($sdf_order['order_objects'] as $xkey=>&$xvalue){


            $xvalue['order_items']=array_values($xvalue['order_items']);
        }

        $sdf_order['goods_items']=array_values($sdf_order['goods_items']);


       

        unset($sdf_order['goods_items'],$sdf_order['weight'],$sdf_order['title'],
              $sdf_order['itemnum'],$sdf_order['last_modified'],
              $sdf_order['createtime'],$sdf_order['ip']
              );

         $obj_goods=&app :: get('b2c')->model('goods');
         foreach($sdf_order['order_objects'] as $xkey=>&$xvalue){

                unset($xvalue['obj_id'],$xvalue['order_id'],$xvalue['obj_type'],
                      $xvalue['obj_alias'],$xvalue['weight'],$xvalue['price'],
                      $xvalue['goods_id'],$xvalue['bn'],$xvalue['score']
                );

                foreach($xvalue['order_items'] as $ykey=>&$yvalue){
                    unset($yvalue['item_id'],$yvalue['order_id'],$yvalue['obj_id'],
                          $yvalue['goods_id'],$yvalue['type_id'],$yvalue['bn'],
                          $yvalue['sendnum'],$yvalue['addon'],$yvalue['addon'],
                          $yvalue['weight'], $yvalue['g_price'],$yvalue['score'],$yvalue['cost'],
                          $yvalue['products']['bn'],$yvalue['products']['uptime'], $yvalue['products']['last_modify'],$yvalue['products']['disabled'],
                          $yvalue['products']['status'],$yvalue['products']['title'],
                          $yvalue['products']['store_place'],$yvalue['products']['freez'],
                          $yvalue['products']['store'],$yvalue['products']['unit'],
                          $yvalue['products']['price']['mktprice'],$yvalue['products']['price']['cost'],
                          $yvalue['products']['price'],$yvalue['products']['spec_desc'],
                          $yvalue['products']['weight'],$yvalue['products']['goods_type'],
                          $yvalue['products']['barcode']
                        //$yvalue['products']['goods_id'],
                    );

                    $oGoods=$obj_goods->getList('udfimg,thumbnail_pic,image_default_id',array('goods_id'=>$yvalue['products']['goods_id']));
                    $yvalue['products']['image_default_id'] =
                        $this->get_img_url(($oGoods[0]['udfimg']=='true'?$oGoods[0]['thumbnail_pic']:$oGoods[0]['image_default_id']),$picSize);

                }

                $xvalue['order_items']=array_values($xvalue['order_items']);

        }

       
     /* 物流跟踪信息
        $obj_delivery= &app::get('b2c')->model('delivery');
        $obj_dlycorp= &app::get('b2c')->model('dlycorp');
        $oDelivery=$obj_delivery->getList('delivery_id,logi_id,logi_name,logi_no',array('order_id'=>$sdf_order['order_id']));
        foreach($oDelivery as $key=>&$value){
            $oDlycorp=$obj_dlycorp->getList('corp_code',array('corp_id'=>$value['logi_id']));
            if($oDlycorp[0]){
                //转换接口用物流代码
                $value['logi_code']=self::$support_corps[$oDlycorp[0]['corp_code']];
                if(empty( $value['logi_code'])){
                    $value['logi_code']=$oDlycorp[0]['corp_code'];
                }
            }
        }

        $sdf_order['shipping']['delivery_id']=$oDelivery[0]['delivery_id'];
        $sdf_order['shipping']['logi_id']=$oDelivery[0]['logi_id'];
        $sdf_order['shipping']['logi_name']=$oDelivery[0]['logi_name'];
        $sdf_order['shipping']['logi_no']=$oDelivery[0]['logi_no'];
        $sdf_order['shipping']['logi_code']=$oDelivery[0]['logi_code'];
      */
        //print_r('<pre>');print_r($sdf_order);print_r('</pre>');exit;

        $this->send(true,$sdf_order,app::get('b2c')->_('订单详情'));
        

    }


     /*订单评论*/
    function setcomment(){
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
        $member_id = $member['member_id'];
        $order_id = $params['order_id'];

        $picSize = in_array(strtolower($params['pic_size']), array('cl', 'cs'))?strtolower($params['pic_size']):'cl';
        
        $app = app::get('b2c');
        $objCommentType = $app->model('comment_goods_type');
        $comment_type = $objCommentType->getList('*');
        if(!$comment_type){
            $sdf['type_id'] = 1;
            $sdf['name'] = app::get('b2c')->_('宝贝与描述相符');
            $addon['is_total_point'] = 'on';
            $addon['description'] = array(5 => '质量非常好，与卖家描述的完全一致，非常满意',
                            4 => '质量不错，与卖家描述的基本一致，还是挺满意的',
                            3 => '质量一般，没有卖家描述的那么好',
                            2 => '部分有破损，与卖家描述的不符，不满意',
                            1 => '差的太离谱，与卖家描述的严重不符，非常不满');
            $sdf['addon'] = serialize($addon);
            $objCommentType->insert($sdf);
            $comment_type = $objCommentType->getList('*');
        }
        foreach($comment_type as $k=>&$v){
           // $v['addon'] = unserialize($v['addon']);
           unset($v['addon']);

        }
        $data['comment_store_type'] = $comment_type;
        $goods_point_status = $app->getConf('goods.point.status');
        $data['point_status'] = $goods_point_status ? $goods_point_status: 'on';
        $data['order_id'] = $order_id;
        
        $objOrder = $app->model('orders');
        $objOrderItems = $app->model('order_items');
        $objGoods = $app->model('goods');
        $day_1 = app::get('b2c')->getConf('site.comment_original_time');
        $day_1 = intval($day_1)?intval($day_1):30;
        
        $sql  = " select o.order_id,o.createtime,o.comments_count ,o.store_id ,s.store_name from sdb_b2c_orders as o left join sdb_business_storemanger as s on o.store_id = s.store_id  where o.order_id='{$order_id}' and o.member_id='{$member_id}' and o.status='finish' and ifnull(o.comments_count,0)=0 and DATE_SUB(CURDATE(),INTERVAL {$day_1} DAY)<=from_unixtime(o.createtime) ";
		
        $order_info = $objOrder->db->select($sql);
		if(empty($order_info)){
		    $this->send(false, null, app::get('b2c')->_('该订单不可以评论了'));
		}
        $data['store_id'] = $order_info[0]['store_id'];
        $data['store_name'] = $order_info[0]['store_name'];
      
        $goods_info = array();
        foreach($order_info as $rows){
            //if(intval($rows['comments_count']) > 0 || intval($rows['createtime']) < strtotime("-1 month")) continue;
            $order_item = $objOrderItems->getList('order_id,goods_id,product_id',array('order_id' => $rows['order_id']));
            $gid = array();
            foreach($order_item as $items){
                $gid[] = $items['goods_id'];
            }
            foreach($objGoods->getList('goods_id,name,thumbnail_pic,udfimg,image_default_id',array('goods_id' => $gid),0,-1) as $product){
                if($product['udfimg']=='true'){
                    $product['image']=$this->get_img_url($product['thumbnail_pic'],$picSize);
                }else{
                    $product['image']=$this->get_img_url($product['image_default_id'],$picSize);
                }
                unset($product['thumbnail_pic'],$product['image_default_id']);
                $goods_info[] = array(
                    'order_id' => $rows['order_id'],
                    'items' => $product,
                );
            }
        }
        $data['order_info'] = $goods_info;
       
        $this->send(true, $data, app::get('b2c')->_('订单评论'));
    }

     /*订单追加评论*/
    function setaddition(){
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
        $order_id = $params['order_id'];
        $member_id = $member['member_id'];
        $picSize = in_array(strtolower($params['pic_size']), array('cl', 'cs'))?strtolower($params['pic_size']):'cl';
        
        $app = app::get('b2c');
        $objOrder = $app->model('orders');
        $objOrderItems = $app->model('order_items');
        $objGoods = app::get('business')->model('goods');
        $day_2 = app::get('b2c')->getConf('site.comment_additional_time');
        $day_2 = intval($day_2)?intval($day_2):90;
        
        //$order_info = $objOrder->getList('order_id,createtime,comments_count', array('order_id'=>$order_id,'member_id'=>$this->app_b2c->member_id,'status'=>'finish'), 0, -1, 'createtime desc');
        $sql  = " select o.order_id,o.createtime,o.comments_count from sdb_b2c_orders as o 
                  left join sdb_business_comment_orders_point as p on p.order_id = o.order_id 
                  where o.order_id='{$order_id}' and o.member_id='{$member_id}' and o.status='finish' 
                  and ifnull(o.comments_count,0)=1 and DATE_SUB(CURDATE(),INTERVAL {$day_2} DAY)<=from_unixtime(o.createtime) 
                  and p.order_id is not null 
                  order by createtime desc ";
        $order_info = $objOrder->db->select($sql);
		if(empty($order_info)){
            $this->send(false, null, app::get('b2c')->_('该订单不可以追加评论了'));
        }
        foreach($order_info as $rows){
            //if(intval($rows['comments_count']) > 1 || intval($rows['createtime']) < strtotime("-3 month")) continue;
            $order_item = $objOrderItems->getList('order_id,goods_id,product_id',array('order_id' => $rows['order_id']));
            $gid = array();
            foreach($order_item as $items){
                $gid[] = $items['goods_id'];
            }
            $goods_info = array();
            foreach($objGoods->get_comment_goods($gid, $rows['order_id']) as $product){
                if($product['udfimg']=='true'){
                    $product['image']=$this->get_img_url($product['thumbnail_pic'],$picSize);
                }else{
                    $product['image']=$this->get_img_url($product['image_default_id'],$picSize);
                }
                $goods_info[] = array(
                    'order_id' => $rows['order_id'],
                    'items' => $product,
                );
            }
        }
        $data['order_info'] = $goods_info;
        
        $this->send(true, $data, app::get('b2c')->_('订单追加评论'));
    }

    /*保存订单评论*/
    /* params ginfo  Array(
             
    [0]=>Array( [order_id]=>111111 [goods_id]=>100 [comment]=>很好 [goods_point]=>Array([0]=>2 [1]=>4) [for_comment_id]=> [hidden_name]=>)

    [1]=>Array( [order_id]=>111111 [goods_id]=>101 [comment]=>很好 [goods_point]=>Array() [for_comment_id]=> [hidden_name]=>)

    [2]=>Array( [order_id]=>111111 [goods_id]=>102 [comment]=>很好 [goods_point]=>Array([0]=>2 ) [for_comment_id]=> [hidden_name]=>)

    [3]=>Array( [order_id]=>111111 [goods_id]=>103 [comment]=>很好 [goods_point]=>Array([0]=>2 [1]=>4) [for_comment_id]=> [hidden_name]=>)
    ...
    )
    params sinfo  Array(
                           [store_id]=>38 
                           [order_id]=>111111
                           [store_point]=> Array(
                                [0]=>Array([type_id]=>1 [point]=>4)
                                [1]=>Array([type_id]=>2 [point]=>5)
                                [2]=>Array([type_id]=>3 [point]=>4)
                              )
    
    
    )
    
    */
    function tocomment(){
        $params = $this->params;
        $must_params = array(
            'session'=>'会员ID',
            'type'=>'1评论，3追加评论',
            'order_id'=>'订单号',
            'ginfo'=>'商品评价',
            'sinfo'=>'店铺(订单)评价',
        );
        $this->check_params($must_params);
		
        $member = $this->get_current_member();
        if(empty($member['member_id'])){
        $this->send(false,null,app::get('b2c')->_('session失效或您还未登录'));
        }
        $objGoods = app::get('business')->model('goods');
        $order_info = $objGoods->get_order_info(array($params['order_id']), $member['member_id']);
        if(!$order_info){
            $this->send(false, null, app::get('b2c')->_('订单不存在'));
        }
        $params['ginfo'] = json_decode($params['ginfo'],1);
        $params['sinfo'] = json_decode($params['sinfo'],1);
		//校验 参数及参数格式
		if(is_array($params['ginfo'])){
		  foreach($params['ginfo'] as $key=>$item){
		   if(empty($item['comment'])||is_null($item['comment'])|| $item['comment']=='null'){
		    $this->send(false,null,app::get('b2c')->_('评论内容不能为空'));
		   }
		   if(empty($item['goods_id'])){
		    $this->send(false,null,app::get('b2c')->_('goods_id不能为空'));
		   }
		   if(empty($item['order_id']) || $item['order_id']!= $params['order_id']){
		    $this->send(false,null,app::get('b2c')->_('order_id不能为空或订单号不一致'));
		   }
		  
		  }
		}else {
		    $this->send(false,null,app::get('b2c')->_('ginfo参数格式错误'));
		}
		if(is_array($params['sinfo'])){
			if($params['sinfo']['order_id']!=$params['order_id']){
			  $this->send(false,null,app::get('b2c')->_('订单号不一致'));
			}
		    foreach ($params['sinfo']['store_point'] as $key=>$item){
			  if(empty($item['point'])){
			  $this->send(false,null,app::get('b2c')->_('店铺评分不能为空'));
			  }
			}
		}else {
		    $this->send(false,null,app::get('b2c')->_('sinfo参数格式错误'));
		}
      //end 

        $app = app::get('b2c');
        $objComment = kernel::single('business_message_disask');//完成商品各项评分和评论内容的保存
        $aData = array();
        $aData['comments_type'] = $params['type'];//评论类型 1 评论 3 追加评论
        $aData['title'] = null;
        $aData['object_type'] = 'discuss';
        $aData['author_id'] = $member['member_id'] ? $member['member_id']:0;
        $aData['author'] = $member['uname'] ? $member['uname'] : '非会员顾客';
        $aData['contact'] = $member['email'];
        $aData['time'] = time();
        $aData['lastreply'] = 0;
        $aData['ip'] = $_SERVER["REMOTE_ADDR"];
        $aData['display'] = ($app->getConf('comment.display.discuss')=='soon' ? 'true' : 'false');
      
            if($params['sinfo']['order_id'] == $order_info[0]['order_id']){
                foreach($params['ginfo'] as $item){
                   
                    $temp = $aData;
                    $temp['order_id'] = $item['order_id'];
                    $temp['goods_id'] = $item['goods_id'];
                    if($temp['comments_type'] == '3'){//追加评论时
                        if(empty($item['for_comment_id'])){
                          $this->send(false,null,'追加评论时 for_comment_id为空');
                        }
                        $temp['for_comment_id'] = $item['for_comment_id'];
                    }
                    //没有设置是否匿名 默认YES
                    $temp['hidden_name'] = $item['hidden_name']? $item['hidden_name'] : 'YES';
                    //没有商品评分 默认5分
                    $temp['goods_point'] = empty($item['goods_point'])? array(5) : $item['goods_point'];
                    // 评论的内容
                    $temp['comment'] = $item['comment'];
                    //完成商品各项评分和评论内容的保存
                    if($objComment->send($temp, 'discuss')){
                        //$objGoods->updateRank($gid, 'discuss',1);
                        $objGoods->db->exec('update sdb_b2c_goods as g inner join (select avg(goods_point) as point ,goods_id,count(point_id) as comments_count from sdb_b2c_comment_goods_point where goods_id='.intval($item['goods_id']).' group by goods_id) as p on g.goods_id=p.goods_id set g.avg_point = p.point,g.comments_count=p.comments_count where g.goods_id='.intval($item['goods_id']));
                        if($app->getConf('comment.display.discuss') == 'soon' && $aData['author_id']){
                            $_is_add_point = app::get('b2c')->getConf('member_point');
                            if($_is_add_point){
                                $obj_member_point = $app->model('member_point');
                                $obj_member_point->change_point($aData['author_id'],$_is_add_point,$_msg,'comment_discuss',2,$aData['goods_id'],$aData['author_id'],'comment');
                            }
                        }
                    }else{
                       $this->send(false,null,app::get('b2c')->_('商品评论失败'));
                    }
                }
            }
       
        //店铺的各项评分开始
        $objCommentType = $app->model('comment_goods_type');
        $comment_type = $objCommentType->getList('*');
        $exp_type = '';
        foreach($comment_type as $rows){
            $sdf['addon'] = unserialize($rows['addon']);
            if($sdf['addon']['is_total_point'] == 'on'){
                $exp_type = $rows['type_id'];
                break;
            }
        }
        $obj_store = app::get('business')->model('storemanger');
        if(count($order_info) > 0 ){
            $objBOrderPoint = app::get('business')->model('comment_orders_point');
           
                $aData = array();
                $aData['store_id'] = $params['sinfo']['store_id'];
                $aData['member_id'] = $member['member_id'] ? $member['member_id']:0;
                $aData['order_id'] = $params['sinfo']['order_id'];
                
                foreach($params['sinfo']['store_point'] as $item){
                    $temp = $aData;
                    $temp['point'] = $item['point']? $item['point'] : 5;
                    $temp['type_id'] = $item['type_id'];
                    if($objBOrderPoint->save($temp)){;
                       if($exp_type && $exp_type == $item['type_id']){
                          $exper = 0;
                          switch(intval($temp['point'])){
                            case 1:
                            case 2:
                            $exper = -1;
                            break;
                            case 4:
                            case 5:
                            $exper = 1;
                            break;
                            default:
                            $exper = 0;
                            break;
                          }
                        $obj_store->change_experience($aData['store_id'],$exper,$msg,$aData['member_id'],'1',$aData['order_id'],'discuss');
                    }
				  }else{
				     $this->send(false,null,app::get('b2c')->_('订单评分失败'));
				  }
                }
            
        }
           //更新订单的评论数
           $result =  $objGoods->updateOrderRank($params['order_id'], 'discuss',1);
           if($result){
             $this->send(true, null, app::get('b2c')->_('评论成功'));
		   }else{
		     $this->send(false, null, app::get('b2c')->_('订单评论数更改失败'));
		   }
        
        

    }


}