<?php
 

class jdbookconsole_ctl_admin_order extends desktop_controller{

    var $workground = 'jdbookconsole.workground.order';

	public function index(){
        if($_GET['action'] == 'export') $this->_end_message = '导出订单';
        $this->finder('b2c_mdl_orders',array(

            'title'=>app::get('b2c')->_('订单列表'),
            'allow_detail_popup'=>true,
            'base_filter'=>array('order_refer'=>'local','disabled'=>'false','order_kind'=>array('jdbook')),
            'use_buildin_export'=>true,
			'use_view_tab'=>true,
			'force_view_tab'=>true,
            'actions'=>array(
					array('label'=>app::get('b2c')->_('打印样式'),'target'=>'_blank','href'=>'index.php?app=b2c&ctl=admin_order&act=showPrintStyle'),
					array('label'=>app::get('b2c')->_('打印选定订单'),'submit'=>'index.php?app=b2c&ctl=admin_order&act=toprint','target'=>'_blank'),
					//array('label'=>app::get('b2c')->_('导出'),'target'=>'_blank','submit'=>'index.php?app=b2c&ctl=admin_order&act=card_explode'),
					
				),
            'use_buildin_set_tag'=>true,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            ));
    }
	
		
		
	public function _views(){
		$mdl_orders = kernel::single('b2c_mdl_orders');
		$order_filter=array('order_kind'=>array('jdbook'));
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
                $show_menu[$k]['href'] = 'index.php?app=jdbookconsole&ctl=admin_order&act='.$act.'&view='.($k).(isset($_GET['optional_view'])?'&optional_view='.$_GET['optional_view'].'&view_from=dashboard':'');
            }elseif(($_GET['view_from']=='dashboard')&&$k==$_GET['view']){
                $show_menu[$k] = $v;
            }
        }
		
        return $show_menu;	
    }
     
}	