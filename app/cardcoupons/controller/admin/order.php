<?php
class cardcoupons_ctl_admin_order extends desktop_controller{
	Var $workground = 'cardcoupons.wrokground.card';
    
    /**
     * 构造方法
     * @params object app object
     * @return null
     */
    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
        $this->objMath = kernel::single('ectools_math');
    }



    public function index(){
        if($_GET['action'] == 'export') $this->_end_message = '导出订单';
        $this->finder('b2c_mdl_orders',array(

            'title'=>app::get('b2c')->_('订单列表'),
            'allow_detail_popup'=>true,
            'base_filter'=>array('order_refer'=>'local','disabled'=>'false','order_kind'=>array('card')),
            'use_buildin_export'=>false,
			'use_view_tab'=>true,
			'force_view_tab'=>true,
			'finder_aliasname'=>'card_order',
            'actions'=>array(
                            // array('label'=>app::get('b2c')->_('添加订单'),'href'=>'index.php?app=b2c&ctl=admin_order&act=addnew','target'=>'_blank','icon'=>'sss.ccc'),
                            array('label'=>app::get('b2c')->_('打印样式'),'target'=>'_blank','href'=>'index.php?app=b2c&ctl=admin_order&act=showPrintStyle'),
                            array('label'=>app::get('b2c')->_('打印选定订单'),'submit'=>'index.php?app=b2c&ctl=admin_order&act=toprint','target'=>'_blank'),
							array('label'=>app::get('b2c')->_('导出'),'target'=>'_blank','submit'=>'index.php?app=b2c&ctl=admin_order&act=card_explode'),
							
                        ),'use_buildin_set_tag'=>true,'use_buildin_recycle'=>false,'use_buildin_filter'=>true,'use_view_tab'=>true,
            ));
    }
   public function sale(){
	   //卡券自营店铺
	    $kv_obj=kernel::single('base_mdl_kvstore');
		$store=$kv_obj->getList('*',array('key'=>'cardcoupons.store.set'));
		$store_id_arr = $store[0]['value'];

		if($_GET['action'] == 'export') $this->_end_message = '导出订单';
        $this->finder('b2c_mdl_orders',array(

            'title'=>app::get('b2c')->_('订单列表'),
            'allow_detail_popup'=>true,
            'base_filter'=>array('order_refer'=>'local','disabled'=>'false','order_kind'=>array('b2c_card','card'),'store_id'=>$store_id_arr,'order_type|notin'=>array('largeCustomer')),
            'use_buildin_export'=>false,
			'use_view_tab'=>true,
			'force_view_tab'=>true,
			'finder_aliasname'=>'card_saleorder',
            'actions'=>array(
                            // array('label'=>app::get('b2c')->_('添加订单'),'href'=>'index.php?app=b2c&ctl=admin_order&act=addnew','target'=>'_blank','icon'=>'sss.ccc'),
                            array('label'=>app::get('b2c')->_('打印样式'),'target'=>'_blank','href'=>'index.php?app=b2c&ctl=admin_order&act=showPrintStyle'),
                            array('label'=>app::get('b2c')->_('打印选定订单'),'submit'=>'index.php?app=b2c&ctl=admin_order&act=toprint','target'=>'_blank'),
							array('label'=>app::get('b2c')->_('导出'),'target'=>'_blank','submit'=>'index.php?app=b2c&ctl=admin_order&act=card_explode'),
                        ),'use_buildin_set_tag'=>true,'use_buildin_recycle'=>false,'use_buildin_filter'=>true,'use_view_tab'=>true,
            ));
	}
    /**
     * 桌面订单相信汇总显示
     * @param null
     * @return null
     */
    public function _views(){
        $mdl_orders = kernel::single('b2c_mdl_orders');
		$act=$_GET['act'];
		if($_GET['act']=='sale')$order_kind=array('b2c_card');
		if($_GET['act']=='index')$order_kind=array('card');
		$order_filter=array('order_kind'=>$order_kind);
        $sub_menu = array(
            0=>array('label'=>app::get('b2c')->_('全部'),'optional'=>false,'filter'=>array_merge($order_filter,array('disabled'=>'false','order_type|notin'=>array('largeCustomer')))),
            1=>array('label'=>app::get('b2c')->_('未处理'),'optional'=>false,'filter'=>array_merge($order_filter,array('pay_status'=>array('0'),'ship_status'=>array('0'),'status'=>'active','disabled'=>'false','order_type|notin'=>array('largeCustomer')))),
            2=>array('label'=>app::get('b2c')->_('已付款待发货'),'optional'=>false,'filter'=>array_merge($order_filter,array('pay_status'=>array('1','2','3'),'ship_status'=>array('0','2'),'status'=>'active','disabled'=>'false','order_type|notin'=>array('largeCustomer')))),
            3=>array('label'=>app::get('b2c')->_('已发货'),'optional'=>false,'filter'=>array_merge($order_filter,array('ship_status'=>array('1'),'status'=>'active','disabled'=>'false','order_type|notin'=>array('largeCustomer')))),
            4=>array('label'=>app::get('b2c')->_('已完成'),'optional'=>false,'filter'=>array_merge($order_filter,array('status'=>'finish','disabled'=>'false','order_type|notin'=>array('largeCustomer')))),
            5=>array('label'=>app::get('b2c')->_('已退款'),'optional'=>false,'filter'=>array_merge($order_filter,array('pay_status'=>array('4','5'),'status'=>'active','disabled'=>'false','order_type|notin'=>array('largeCustomer')))),
            6=>array('label'=>app::get('b2c')->_('已退货'),'optional'=>false,'filter'=>array_merge($order_filter,array('ship_status'=>array('3','4'),'status'=>'active','disabled'=>'false','order_type|notin'=>array('largeCustomer')))),
            7=>array('label'=>app::get('b2c')->_('已作废'),'optional'=>false,'filter'=>array_merge($order_filter,array('status'=>'dead','disabled'=>'false','order_type|notin'=>array('largeCustomer')))),
        );
        //新留言订单
        //fix filter condition by danny(数据量大后是性能瓶颈)
        //$filter = array('adm_read_status'=>'false','object_type'=>'order');
        //$orders_num = kernel::single('b2c_message_order')->count($filter);

        $order_id_arr = $mdl_orders->db->select("select order_id from sdb_b2c_member_comments where adm_read_status = 'false' and object_type= 'order'");
        if(is_array($order_id_arr)){
            foreach($order_id_arr as $ok=>$ov){
                $forders['order_id'][] = $ov['order_id'];
            }
        }

        $sub_menu[8] = array('label'=>app::get('b2c')->_('新留言订单'),'optional'=>true,'filter'=>array_merge($order_filter,$forders),'addon'=>count($forders['order_id']),'href'=>'index.php?app=cardcoupons&ctl=admin_order&act='.$act.'&view=8&view_from=dashboard');

        //今日订单
        $today_filter = array(
                    '_createtime_search'=>'between',
                    'createtime_from'=>date('Y-m-d',strtotime('TODAY')),
                    'createtime_to'=>date('Y-m-d'),
                    'createtime' => date('Y-m-d'),
                    '_DTIME_'=>
                        array(
                            'H'=>array('createtime_from'=>'00','createtime_to'=>date('H')),
                            'M'=>array('createtime_from'=>'00','createtime_to'=>date('i'))
                        )
                );
		$today_filter=array_merge($order_filter,$today_filter);
        $today_order = $mdl_orders->count($today_filter);
        $sub_menu[9] = array('label'=>app::get('b2c')->_('今日订单'),'optional'=>true,'filter'=>$today_filter,'addon'=>$today_order,'href'=>'index.php?app=cardcoupons&ctl=admin_order&act='.$act.'&view=9&view_from=dashboard');

        //昨日订单
        $date = strtotime('yesterday');
        $yesterday_filter = array(
                    '_createtime_search'=>'between',
                    'createtime_from'=>date('Y-m-d',$date),
                    'createtime_to'=>date('Y-m-d',strtotime('today')),
                    'createtime' => date('Y-m-d',$date),
                    '_DTIME_'=>
                        array(
                            'H'=>array('createtime_from'=>'00','createtime_to'=>date('H',$date)),
                            'M'=>array('createtime_from'=>'00','createtime_to'=>date('i',$date))
                        )
                );
		$yesterday_filter=array_merge($order_filter,$today_filter);
        $yesterday_order = $mdl_orders->count($yesterday_filter);
        $sub_menu[10] = array('label'=>app::get('b2c')->_('昨日订单'),'optional'=>true,'filter'=>$yesterday_filter,'addon'=>$yesterday_order,'href'=>'index.php?app=cardcoupons&ctl=admin_order&act='.$act.'&view=10&view_from=dashboard');

        //今日已付款订单
        $today_filter = array_merge($today_filter,array('pay_status'=>'1'));
        $today_payed = $mdl_orders->count($today_filter);
        $sub_menu[11] = array('label'=>app::get('b2c')->_('今日已付款'),'optional'=>true,'filter'=>$today_filter,'addon'=>$today_payed,'href'=>'index.php?app=cardcoupons&ctl=admin_order&act='.$act.'&view=11&view_from=dashboard');

        //昨日已付款订单
        $yesterday_filter = array_merge($yesterday_filter,array('pay_status'=>'1'));
        $sub_menu[12] = array('label'=>app::get('b2c')->_('昨日已付款'),'optional'=>true,'filter'=>$yesterday_filter,'addon'=>$yesterday_payed,'href'=>'index.php?app=cardcoupons&ctl=admin_order&act='.$act.'&view=12&view_from=dashboard');

        //TAB扩展
        foreach(kernel::servicelist('b2c_order_view_extend') as $service){
            if(method_exists($service,'getViews')) {
                $service->getViews($sub_menu);
            }
        }

        if(isset($_GET['optional_view'])) $sub_menu[$_GET['optional_view']]['optional'] = false;


        foreach($sub_menu as $k=>$v){
            if($v['optional']==false){
                $show_menu[$k] = $v;
                if(is_array($v['filter'])){
                    $v['filter'] = array_merge(array('order_refer'=>'local'),$v['filter']);
                }else{
                    $v['filter'] = array('order_refer'=>'local');
                }
                $show_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
                $show_menu[$k]['addon'] = $mdl_orders->count($v['filter']);
                $show_menu[$k]['href'] = 'index.php?app=cardcoupons&ctl=admin_order&act='.$act.'&view='.($k).(isset($_GET['optional_view'])?'&optional_view='.$_GET['optional_view'].'&view_from=dashboard':'');
            }elseif(($_GET['view_from']=='dashboard')&&$k==$_GET['view']){
                $show_menu[$k] = $v;
            }
        }
        return $show_menu;
    }
	
 function change(){
		$this->begin('index.php?app=cardcoupons&ctl=admin_order&act=index');
		$order_id = $_GET['order_id'];
		$oOrders = app::get('b2c')->model('orders');
		$card_pass = app::get('cardcoupons')->model('cards_pass');
		$info = $oOrders->getRow('*',array('order_id'=>$order_id));
		if($info['order_kind']!='card'){
			$this->end(false,app::get('cardcoupons')->_('该订单不是卡券兑换订单！'));
		}
		$flag = $card_pass->getRow('exchange_order_id,card_no',array('exchange_order_id|has'=>$order_id));
		if(count($flag['exchange_order_id'])>1){
			$this->end(false,app::get('cardcoupons')->_('该卡号还兑换了其他订单，请联系相关商家'));
		}
		if(count($flag['exchange_order_id'])<1){
			$this->end(false,app::get('cardcoupons')->_('该订单不存在关联的卡号'));
		}
		$res1= $oOrders->update(array('status'=>'dead'),array('order_id'=>$order_id));
		if($res1){
			$res2 = $card_pass->update(array('status'=>'1','exchange_order_id'=>NULL),array('exchange_order_id|has'=>$order_id));
		}
		if($res2){
			$obj_filter = kernel::single('b2c_site_filter');
			$_sjson = array(
				'METHOD'=>'updateDocItemStatus',
				'CARD_NUMBER'=>$obj_filter->check_input($flag['card_no']),
				'REC_ORDER_ID'=>$order_id,
				'REC_STATUS'=>'I01101'
			);
			$post_data = array('serviceNo'=>'DocumentItemService',"inputParam"=>json_encode($_sjson));
			$tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
			$this->end(true,app::get('cardcoupons')->_('操作成功'));
		}
	}
	
}