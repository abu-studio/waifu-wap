<?php
/*
 * @Title: pass.php
 * @Description: 卡密finder列扩展列
 *
 * @author zhyu
 * @date Jun 30, 2015 1:54:53 PM
 * @version V1.0
 */
 class cardcoupons_finder_cards_pass {
	 function __construct($app){
        $this->app = $app;
		$this->app_ectools = app::get('ectools');
		$this->odr_action_buttons = array('pay','delivery','finish','refund','reship','cancel','delete');
        // 判定是否绑定ome或者其他后端店铺
        $obj_b2c_shop = kernel::single('b2c_mdl_shop');
        $cnt = $obj_b2c_shop->count(array('status'=>'bind','node_type'=>'ecos.ome'));
        if ($cnt > 0)
        {
            $this->odr_action_is_all_disable = true;
        }
        else
        {
            $this->odr_action_is_all_disable = false;
        }
    }

	var $detail_orderinfo = '订单信息';
	public function detail_orderinfo($id)
    {
        $render = $this->app->render();
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
		$pass_info = $pass_obj->getRow("order_id",array('card_pass_id'=>$id));
		
		$order_id = $pass_info['order_id'];

        $order = kernel::single('b2c_mdl_orders');
        $payments = $this->app_ectools->model('payments');
        
        $subsdf = array('order_pmt'=>array('*'),'order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $aOrder = $order->dump($order_id, '*', $subsdf);

        $oCur = $this->app_ectools->model('currency');
        $aCur = $oCur->getSysCur();
        $aOrder['cur_name'] = $aCur[$aOrder['currency']];
    
        if (intval($aOrder['payinfo']['pay_app_id']) < 0)
            $aOrder['payinfo']['pay_app_id'] = app::get('cardcoupons')->_('货到付款');
        else
        {  
            $payid = $aOrder['payinfo']['pay_app_id'];
            $obj_paymentsfg = $this->app_ectools->model('payment_cfgs');
            $arr_payments = $obj_paymentsfg->getPaymentInfo($payid);
            $aOrder['payinfo']['pay_app_id'] = $arr_payments['app_name'] ? $arr_payments['app_name'] : $payid;
        }
    
        if ($aOrder['member_id'])
        {
            $member = kernel::single('b2c_mdl_members');
            $aOrder['member'] = $member->dump($aOrder['member_id'], '*', array(':account@pam'=>'*'));
            
            // 得到meta的信息
            $arrTree = array();
            $index = 0;
            if ($aOrder['member']['contact'])
            {
                if ($aOrder['member']['contact']['qq'])
                    $arrTree[$index++] = array(
                        'attr_name' => app::get('cardcoupons')->_('腾讯QQ'),
                        'attr_tyname' => 'QQ',
                        'value' => $aOrder['member']['contact']['qq'],
                    );
                
                if ($aOrder['member']['contact']['msn'])
                    $arrTree[$index++] = array(
                        'attr_name' => 'windows live',
                        'attr_tyname' => 'MSN',
                        'value' => $aOrder['member']['contact']['msn'],
                    );
                
                if ($aOrder['member']['contact']['wangwang'])
                    $arrTree[$index++] = array(
                        'attr_name' => 'WangWang',
                        'attr_tyname' => app::get('cardcoupons')->_('旺旺'),
                        'value' => $aOrder['member']['contact']['wangwang'],
                    );
                
                if ($aOrder['member']['contact']['skype'])
                    $arrTree[$index++] = array(
                        'attr_name' => 'Skype',
                        'attr_tyname' => 'Skype',
                        'value' => $aOrder['member']['contact']['skype'],
                    );
                
                $render->pagedata['tree'] = $arrTree;
            }
        }
    
    
        $aOrder['discount'] = 0 - $aOrder['discount'];
        $render->pagedata['order'] = $aOrder;
        //+todo license权限----------
    //    $_is_all_ship = 1;
    //    $_is_all_return_ship = 1;
    
        foreach ((array)$aItems as $_item)
        {
            if((!$_item['supplier_id']) && ($_item['sendnum'] < $_item['nums'] )){
                $_is_all_ship = 0;
            }
            if((!$_item['supplier_id']) && ($_item['sendnum'] > 0 )){
                $_is_all_return_ship = 0;
            }
        }
        
        foreach((array)$gItems as $g_item){
            if($g_item['sendnum'] < $g_item['nums'] ){
                $_is_all_ship = 0;
            }
            if($g_item['sendnum'] > 0 ){
                $_is_all_return_ship = 0;
            }
        }
        $render->pagedata['order']['_is_all_ship'] = $_is_all_ship;
        $render->pagedata['order']['_is_all_return_ship'] = $_is_all_return_ship;
        $render->pagedata['order']['flow']= array('refund' => $this->app->getConf('order.flow.refund'),
            'consign' => $this->app->getConf('order.flow.consign'),
            'reship' => $this->app->getConf('order.flow.reship'),
            'payed' => $this->app->getConf('order.flow.payed'),
        );
            
        if (!$render->pagedata['order']['member']['contact']['area'])
        {
            $render->pagedata['order']['member']['contact']['area'] = '';
        }
        else
        {
            if (strpos($render->pagedata['order']['member']['contact']['area'], ':') !== false)
            {
                $arr_areas = explode(':', $render->pagedata['order']['member']['contact']['area']);
                $render->pagedata['order']['member']['contact']['area'] = $arr_areas[1];
            }
        }
        
        if (strpos($render->pagedata['order']['consignee']['area'], ':') !== false)
        {
            $arr_areas = explode(':', $render->pagedata['order']['consignee']['area']);
            $render->pagedata['order']['consignee']['area'] = $arr_areas[1];
        }
        
        $objMath = kernel::single('ectools_math');
        $render->pagedata['order']['pmt_amount'] = $objMath->number_plus(array($render->pagedata['order']['pmt_goods'],$render->pagedata['order']['pmt_order']));
        if ($render->pagedata['order']['pmt_amount'] > 0)
        {
            if (isset($aOrder['order_pmt']) && $aOrder['order_pmt'])
            {
                foreach ($aOrder['order_pmt'] as $arr_pmts)
                {
                    if ($arr_pmts['pmt_type'])
                    {
                        switch ($arr_pmts['pmt_type'])
                        {
                            case 'order':
                            case 'coupon':
                                $obj_save_rules = kernel::single('b2c_mdl_sales_rule_order');
                                break;
                            case 'goods':
                                $obj_save_rules = kernel::single('b2c_mdl_sales_rule_goods');
                                break;
                            default:
                                break;
                        }
                    }
                    
                    $arr_save_rules = $obj_save_rules->dump($arr_pmts['pmt_id']);
                    $render->pagedata['order']['use_pmt'] .= $arr_save_rules['name'] . ', ';
                }
                
                if (strpos($render->pagedata['order']['use_pmt'], ', ') !== false)
                    $render->pagedata['order']['use_pmt'] = substr($render->pagedata['order']['use_pmt'], 0, strlen($render->pagedata['order']['use_pmt']) - 2);
            }
        }
        
        // 判断是否使用了推广服务
        $is_bklinks = 'false';
        $obj_input_helpers = kernel::servicelist("html_input");
        if (isset($obj_input_helpers) && $obj_input_helpers)
        {
            foreach ($obj_input_helpers as $obj_bdlink_input_helper)
            {
                if (get_class($obj_bdlink_input_helper) == 'bdlink_input_helper')
                {
                    $is_bklinks = 'true';
                }
            }
        }
        $render->pagedata['is_bklinks'] = $is_bklinks;
        
        /** 是否开启配送时间的限制 */
        $this->pagedata['site_checkout_receivermore_open'] = $this->app->getConf('site.checkout.receivermore.open');
        
        // 得到订单的优惠方案
        $arr_pmt_lists = array();
        $arr_order_items = array();
        $arr_gift_items = array();
        $arr_extends_items = array();
        
        $this->get_pmt_lists($aOrder, $arr_pmt_lists);
        $this->get_goods_detail($aOrder, $arr_order_items, $arr_gift_items, $arr_extends_items);
        //判断购买商品列表是否显示卡号信息
		if($arr_order_items[0]['product']['card_pass']){
			$render->pagedata['is_card_pass']=true;
		}
        $render->pagedata['goodsItems'] = $arr_order_items;
        $render->pagedata['giftItems'] = $arr_gift_items;
        $render->pagedata['arr_extends_items'] = $arr_extends_items;
        $render->pagedata['order']['pmt_list'] = $arr_pmt_lists;
        $obj_action_button = kernel::servicelist('b2c_order.b2c_finder_orders');
        $arr_obj_action_button = array();
        if ($obj_action_button)
        {
            foreach($obj_action_button as $object) 
            {
                if(!is_object($object)) continue;
                
                if( method_exists($object,'get_order') ) 
                    $index = $object->get_order();
                else $index = 10;
                
                while(true) {
                    if( !isset($arr_obj_action_button[$index]) )break;
                    $index++;
                }
                $arr_obj_action_button[$index] = $object;
            }
        }
        ksort($arr_obj_action_button);
        if ($arr_obj_action_button)
        {
            $render->pagedata['action_buttons'] = array();
            $render->pagedata['ext_action_buttons'] = array();
            foreach ($arr_obj_action_button as $obj)
            {
                $obj->is_display($this->odr_action_buttons);
                $render->pagedata['action_buttons'] = $obj->get_buttons($render->pagedata['order'], $this->odr_action_is_all_disable);
                $render->pagedata['ext_action_buttons'] = $obj->get_extension_buttons($render->pagedata['order']);
            }
        }
        // 添加 html 埋点
        foreach( kernel::servicelist('b2c.order_add_html') as $services ) {
            if ( is_object($services) ) {
                if ( method_exists($services, 'fetchHtml') ) {
                    $services->fetchHtml($render,$order_id,'admin/invoice_detail.html');
                }
            }
        }
        // 判断是否安装物流单跟踪服务
        //物流跟踪安装并且开启
        $logisticst = app::get('cardcoupons')->getConf('system.order.tracking');
        $logisticst_service = kernel::service('b2c_change_orderloglist');
        if(isset($logisticst) && $logisticst == 'true' && $logisticst_service){
            $render->pagedata['services']['logisticstrack'] = $logisticst_service;
        }
        
        $render->pagedata['services']['logisticstrack_url'] = 'index.php?'.utils::http_build_query(array(
            'app'=>'b2c','ctl'=>'admin_order','act'=>'index','action'=>'detail',
            'finderview'=>'detail_delivery','_finder'=>array('finder_id'=>$_GET['finder_id']),'finder_name'=>$_GET['finder_id'],'finder_id'=>$_GET['finder_id'],
            'id'=>$order_id,
        )); 
        
        return $render->fetch('admin/cards/pass/order_detail.html');
    }

	private function get_pmt_lists(&$sdf_order, &$arr_pmt_lists)
    {
        $arr_pmt_lists = array();
        
        if (isset($sdf_order['order_pmt']) && $sdf_order['order_pmt'])
        {
            foreach ($sdf_order['order_pmt'] as $arr_pmt_items)
            {
                $arr_pmt_lists[] = array(
                    'pmt_describe' => $arr_pmt_items['pmt_describe'],
                    'pmt_amount' => $arr_pmt_items['pmt_amount'],
                );
            }
        }
        
        return true;
    }

	private function get_goods_detail(&$aItems, &$order_items, &$gift_items, &$extend_items, $tml='admin_order_detail')
    {
        $order_items = array();
        $objMath = kernel::single("ectools_math");
        if ($aItems['order_objects'])
        {
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
            foreach ($arr_service_goods_type as $obj_service_goods_type)
            {
                $goods_types = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;                
            }
            $index_card = 0;
            foreach ($aItems['order_objects'] as $k=>$v)
            {
                $index = 0;
                $index_adj = 0;
                $index_gift = 0;
                $image_set = app::get('image')->getConf('image.set');
                if ($v['obj_type'] == 'goods')
                {                    
                    foreach($v['order_items'] as $key => $item)
                    {  
                        if (!$item['products'])
                        {
                            $o = kernel::single('b2c_mdl_order_items');
                            $tmp = $o->getList('*', array('item_id'=>$item['item_id']));
                            $item['products']['product_id'] = $tmp[0]['product_id'];
                        }
                        
                        if ($item['item_type'] != 'gift')
                        {
                            if($item['addon'] && unserialize($item['addon'])){
                                $gItems[$k]['minfo'] = unserialize($item['addon']);
                            }else{
                                $gItems[$k]['minfo'] = array();
                            }
                            
                            if ($item['item_type'] == 'product')
                            {  
                                if ($arr_service_goods_type_obj['goods'])
                                {
                                    $str_service_goods_type_obj = $arr_service_goods_type_obj['goods'];
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $item['goods_id'],'product_id'=>$item['products']['product_id']), $arrGoods, $tml);
                                }
                                
                                if (!$arrGoods['image_default_id'])
                                {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                
                                $order_items[$k]['product'] = $item;
                                $order_items[$k]['product']['small_pic'] = $arrGoods['image_default_id'];
                                $order_items[$k]['product']['is_type'] = $v['obj_type'];
                                $order_items[$k]['product']['item_type'] = $arrGoods['category']['cat_name'];
                                $order_items[$k]['product']['nums'] = $item['quantity'];
                                $order_items[$k]['product']['minfo'] = $gItems[$k]['minfo'];
                                $order_items[$k]['product']['total_amount'] = $objMath->number_multiple(array($item['price'], $item['quantity']));
                                $order_items[$k]['product']['link'] = $arrGoods['link_url'];
                                
                                if ($item['addon'])
                                {                                        
                                    $item['addon'] = unserialize($item['addon']);
                                    if ($item['addon']['product_attr'])
                                    {
                                        $order_items[$k]['product']['name'] .= '(';
                                        foreach ($item['addon']['product_attr'] as $arr_special_info)
                                        {
                                            $order_items[$k]['product']['name'] .= $arr_special_info['label'] . app::get('b2c')->_('：') . $arr_special_info['value'] . app::get('b2c')->_('、'); 
                                        }
                                        $order_items[$k]['product']['name'] = substr($order_items[$k]['product']['name'], 0, strrpos($order_items[$k]['product']['name'], app::get('b2c')->_('、')));
                                        $order_items[$k]['product']['name'] .= ')';
                                    }
                                }
                            }else if($item['item_type'] == 'virtual'){//卡券，电子码处理
								if ($arr_service_goods_type_obj['cards'])
                                {
                                    $str_service_goods_type_obj = $arr_service_goods_type_obj['cards'];
                                    $str_service_goods_type_obj->get_order_object(array('order_id'=>$item['order_id'],'goods_id' => $item['goods_id'],'product_id'=>$item['products']['product_id'],'type'=>'virtual'), $arrGoods, $tml);
                                }
                                
                                if (!$arrGoods['image_default_id'])
                                {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                
                                $order_items[$index_card]['product'] = $item;
                                $order_items[$index_card]['product']['small_pic'] = $arrGoods['image_default_id'];
                                $order_items[$index_card]['product']['is_type'] = $v['obj_type'];
                                $order_items[$index_card]['product']['item_type'] = $arrGoods['category']['cat_name'];
                                $order_items[$index_card]['product']['nums'] = $item['quantity'];
                                $order_items[$index_card]['product']['minfo'] = $gItems[$k]['minfo'];
                                $order_items[$index_card]['product']['total_amount'] = $objMath->number_multiple(array($item['price'], $item['quantity']));
                                $order_items[$index_card]['product']['link'] = $arrGoods['link_url'];
                                $order_items[$index_card]['product']['card_pass'] = $arrGoods['card_pass'];
								$order_items[$index_card]['product']['pass_type'] ='电子码';
                                if ($item['addon'])
                                {                                        
                                    $item['addon'] = unserialize($item['addon']);
                                    if ($item['addon']['product_attr'])
                                    {
                                        $order_items[$index_card]['product']['name'] .= '(';
                                        foreach ($item['addon']['product_attr'] as $arr_special_info)
                                        {
                                            $order_items[$index_card]['product']['name'] .= $arr_special_info['label'] . app::get('b2c')->_('：') . $arr_special_info['value'] . app::get('b2c')->_('、'); 
                                        }
                                        $order_items[$index_card]['product']['name'] = substr($order_items[$k]['product']['name'], 0, strrpos($order_items[$k]['product']['name'], app::get('b2c')->_('、')));
                                        $order_items[$index_card]['product']['name'] .= ')';
                                    }
                                }
								$index_card++;
							}
							else if($item['item_type'] == 'entity'){//卡券，实体卡处理
								if ($arr_service_goods_type_obj['cards'])
                                {
                                    $str_service_goods_type_obj = $arr_service_goods_type_obj['cards'];
                                    $str_service_goods_type_obj->get_order_object(array('order_id'=>$item['order_id'],'goods_id' => $item['goods_id'],'product_id'=>$item['products']['product_id'],'type'=>'entity'), $arrGoods, $tml);
                                }
                                
                                if (!$arrGoods['image_default_id'])
                                {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                
                                $order_items[$index_card]['product'] = $item;
                                $order_items[$index_card]['product']['small_pic'] = $arrGoods['image_default_id'];
                                $order_items[$index_card]['product']['is_type'] = $v['obj_type'];
                                $order_items[$index_card]['product']['item_type'] = $arrGoods['category']['cat_name'];
                                $order_items[$index_card]['product']['nums'] = $item['quantity'];
                                $order_items[$index_card]['product']['minfo'] = $gItems[$k]['minfo'];
                                $order_items[$index_card]['product']['total_amount'] = $objMath->number_multiple(array($item['price'], $item['quantity']));
                                $order_items[$index_card]['product']['link'] = $arrGoods['link_url'];
                                $order_items[$index_card]['product']['card_pass'] = $arrGoods['card_pass'];
                                $order_items[$index_card]['product']['pass_type'] ='实体卡';
                                if ($item['addon'])
                                {                                        
                                    $item['addon'] = unserialize($item['addon']);
                                    if ($item['addon']['product_attr'])
                                    {
                                        $order_items[$index_card]['product']['name'] .= '(';
                                        foreach ($item['addon']['product_attr'] as $arr_special_info)
                                        {
                                            $order_items[$index_card]['product']['name'] .= $arr_special_info['label'] . app::get('b2c')->_('：') . $arr_special_info['value'] . app::get('b2c')->_('、'); 
                                        }
                                        $order_items[$index_card]['product']['name'] = substr($order_items[$k]['product']['name'], 0, strrpos($order_items[$k]['product']['name'], app::get('b2c')->_('、')));
                                        $order_items[$index_card]['product']['name'] .= ')';
                                    }
                                }
								$index_card++;
							}
                            else
                            {
                                if ($arr_service_goods_type_obj['adjunct'])
                                {
                                    $str_service_goods_type_obj = $arr_service_goods_type_obj['adjunct'];
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $item['goods_id'],'product_id'=>$item['products']['product_id']), $arrGoods, $tml);
                                }
                                
                                if (!$arrGoods['image_default_id'])
                                {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                            
                                $order_items[$k]['adjunct'][$index_adj] = $item;
                                $order_items[$k]['adjunct'][$index_adj]['small_pic'] = $arrGoods['image_default_id'];
                                $order_items[$k]['adjunct'][$index_adj]['is_type'] = $v['obj_type'];
                                $order_items[$k]['adjunct'][$index_adj]['item_type'] = $arrGoods['category']['cat_name'];
                                $order_items[$k]['adjunct'][$index_adj]['nums'] = $item['quantity'];
                                $order_items[$k]['adjunct'][$index_adj]['total_amount'] = $objMath->number_multiple(array($item['price'], $item['quantity']));
                                $order_items[$k]['adjunct'][$index_adj]['link'] = $arrGoods['link_url'];
                                
                                $order_items[$k]['adjunct'][$index_adj]['name'] = $item['name'];
                                
                                if ($item['addon'])
                                {                                        
                                    $item['addon'] = unserialize($item['addon']);
                                    if ($item['addon']['product_attr'])
                                    {
                                        $order_items[$k]['adjunct'][$index_adj]['name'] .= '(';
                                        foreach ($item['addon']['product_attr'] as $arr_special_info)
                                        {
                                            $order_items[$k]['adjunct'][$index_adj]['name'] .= $arr_special_info['label'] . app::get('b2c')->_('：') . $arr_special_info['value'] . app::get('b2c')->_('、'); 
                                        }
                                        $order_items[$k]['adjunct'][$index_adj]['name'] = substr($order_items[$k]['adjunct'][$index_adj]['name'], 0, strrpos($order_items[$k]['adjunct'][$index_adj]['name'], app::get('b2c')->_('、')));
                                        $order_items[$k]['adjunct'][$index_adj]['name'] .= ')';
                                    }
                                }
                                
                                $index_adj++;
                            }
                        }
                        else
                        {
                            if ($arr_service_goods_type_obj['gift'])
                            { 
                                $str_service_goods_type_obj = $arr_service_goods_type_obj['gift'];
                                $str_service_goods_type_obj->get_order_object(array('goods_id' => $item['goods_id'],'product_id'=>$item['products']['product_id']), $arrGoods, $tml);
                                
                                if (!$arrGoods['image_default_id'])
                                {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                
                                $order_items[$k]['gifts'][$index_gift] = $item;
                                $order_items[$k]['gifts'][$index_gift]['small_pic'] = $arrGoods['image_default_id'];
                                $order_items[$k]['gifts'][$index_gift]['is_type'] = $v['obj_type'];
                                $order_items[$k]['gifts'][$index_gift]['item_type'] = $arrGoods['category']['cat_name'];
                                $order_items[$k]['gifts'][$index_gift]['nums'] = $item['quantity'];
                                $order_items[$k]['gifts'][$index_gift]['total_amount'] = $objMath->number_multiple(array($item['price'], $item['quantity']));
                                $order_items[$k]['gifts'][$index_gift]['link'] = $arrGoods['link_url'];
                                
                                $order_items[$k]['gifts'][$index_gift]['name'] = $item['name'];
                                if ($item['addon'])
                                {                                        
                                    $item['addon'] = unserialize($item['addon']);
                                    if ($item['addon']['product_attr'])
                                    {
                                        $order_items[$k]['gifts'][$index_gift]['name'] .= '(';
                                        foreach ($item['addon']['product_attr'] as $arr_special_info)
                                        {
                                            $order_items[$k]['gifts'][$index_gift]['name'] .= $arr_special_info['label'] . app::get('b2c')->_('：') . $arr_special_info['value'] . app::get('b2c')->_('、'); 
                                        }
                                        $order_items[$k]['gifts'][$index_gift]['name'] = substr($order_items[$k]['gifts'][$index_gift]['name'], 0, strrpos($order_items[$k]['gifts'][$index_gift]['name'], app::get('b2c')->_('、')));
                                        $order_items[$k]['gifts'][$index_gift]['name'] .= ')';
                                    }
                                }
                                
                                $index_gift++;
                            }
                        }
                    }
                }
                else
                {
                    if ($v['obj_type'] == 'gift')
                    {
                        if ($arr_service_goods_type_obj['gift'])
                        {
                            $str_service_goods_type_obj = $arr_service_goods_type_obj['gift'];
                            foreach ($v['order_items'] as $gift_key => $gift_item)
                            {
                                if (!$gift_item['products'])
                                {
                                    $o = kernel::single('b2c_mdl_order_items');
                                    $tmp = $o->getList('*', array('item_id'=>$gift_item['item_id']));
                                    $gift_item['products']['product_id'] = $tmp[0]['product_id'];
                                }
                                
                                if (isset($gift_items[$gift_item['goods_id']]) && $gift_items[$gift_item['goods_id']])
                                    $gift_items[$gift_item['goods_id']]['nums'] = $objMath->number_plus(array($gift_items[$gift_item['goods_id']]['nums'], $item['quantity']));
                                else
                                {
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $gift_item['goods_id'],'product_id'=>$gift_item['products']['product_id']), $arrGoods, $tml);
                                    
                                    if (!isset($gift_item['products']['product_id']) || !$gift_item['products']['product_id'])
                                        $gift_item['products']['product_id'] = $gift_item['goods_id'];
                                        
                                    if (!$arrGoods['image_default_id'])
                                    {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }
                                    
                                    $gift_name = $gift_item['name'];
                                    if ($gift_item['addon'])
                                    {
                                        $arr_addon = unserialize($gift_item['addon']);

                                        if ($arr_addon['product_attr'])
                                        {
                                            $gift_name .= '(';

                                            foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                            {
                                                $gift_name .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }

                                            if (strpos($gift_name, $this->app->_(" ")) !== false)
                                            {
                                                $gift_name = substr($gift_name, 0, strrpos($gift_name, $this->app->_(" ")));
                                            }

                                            $gift_name .= ')';
                                        }
                                    }
                                
                                    if ($arrGoods)
                                        $gift_items[$gift_item['products']['product_id']] = array(
                                            'goods_id' => $gift_item['goods_id'],
                                            'bn' => $gift_item['bn'],
                                            'nums' => $gift_item['quantity'],
                                            'name' => $gift_name,
                                            'item_type' => $arrGoods['category']['cat_name'],
                                            'price' => $gift_item['price'],
                                            'quantity' => $gift_item['quantity'],
                                            'sendnum' => $gift_item['sendnum'],
                                            'small_pic' => $arrGoods['image_default_id'],
                                            'is_type' => $v['obj_type'],
                                            'total_amount' => $objMath->number_multiple(array($gift_item['price'], $gift_item['quantity'])),
                                            'link' => $arrGoods['link_url'],
                                        );
                                }
                            }
                        }
                    }
                    else
                    {
                        if ($arr_service_goods_type_obj[$v['obj_type']])
                        {
                            $str_service_goods_type_obj = $arr_service_goods_type_obj[$v['obj_type']];
                            $extend_items[] = $str_service_goods_type_obj->get_order_object($v, $arr_Goods, $tml);
                        }
                    }
                }
            }
        }
        
        return true;
    }

	var $detail_physical = '体检预约单信息';
	function detail_physical($id){
		$render = $this->app->render();
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
		$pass_info = $pass_obj->getRow("exchange_no",array('card_pass_id'=>$id));

		if($pass_info['exchange_no']){
			$_type=array("1"=>"体检");
			$_c_type=array(
				1 => app::get('physical')->_('身份证'),
				2 => app::get('physical')->_('军官证'),
				3 => app::get('physical')->_('团员证'),
			);
			$_status = array (
				1 => app::get('physical')->_('待付款'),
				2 => app::get('physical')->_('待处理'),
				3 => app::get('physical')->_('预约成功待检'),
				4 => app::get('physical')->_('预约成功逾期'),
				5 => app::get('physical')->_('预约失败'),
				6 => app::get('physical')->_('体检完成'),
				7 => app::get('physical')->_('已关闭'),
			);

			$physical_orders_obj=kernel::single('physical_mdl_orders');
			$order_info = $physical_orders_obj -> getInfobyid($pass_info['exchange_no']);

			$order_info["type_name"] = $_type[$order_info["type"]];
			$order_info["c_type_name"] = $_c_type[$order_info["c_type"]];
			$order_info['status_name'] = $_status[$order_info['status']];

			$order_time_arr = explode(",",$order_info['order_times']);
			$order_info['order_time_arr'] = $order_time_arr;

			
			$render->pagedata['order_info']=$order_info;
		}
        return $render->fetch('admin/cards/pass/physical.html');
    }

	var $detail_exchange = '兑换信息';
	function detail_exchange($id){
		$render = $this->app->render();
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
		$pass_info = $pass_obj->getRow("exchange_order_id",array('card_pass_id'=>$id));

		if($pass_info['exchange_order_id']){
			$orders_obj=kernel::single('b2c_mdl_orders');
			$order_list = $orders_obj -> getList("*",array("order_id"=>$pass_info['exchange_order_id']));
			$render->pagedata['order_list']=$order_list;
		}
        return $render->fetch('admin/cards/pass/exchange.html');
    }

	var $column_control='操作';
	function column_control ($row){
		$return='';
		if($row['status']=='1'){
			$return.='<a href="index.php?app=cardcoupons&ctl=admin_cards_pass&act=send&p[0]='.$row['card_pass_id'].'&finder_id='.$_GET['_finder']['finder_id'].'" target="dialog::{title:\''.app::get('cardcoupons')->_('发送卡密').'\', width:500, height:300}">'.app::get('cardcoupons')->_('重发').'</a>';
		}
		return $return;
	}
 }
?>
