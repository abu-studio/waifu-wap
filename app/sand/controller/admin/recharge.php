<?php
 

class sand_ctl_admin_sandorder extends desktop_controller{

    var $workground = 'ectools.wrokground.order';


    function index(){
        $this->finder('sand_mdl_sandorder',array(
            'title'=>app::get('b2c')->_('杉德充值列表'),
			'allow_detail_popup'=>true,
			'use_buildin_export'=>true,
			'use_buildin_set_tag'=>true,
			'use_buildin_recycle'=>false,
			'use_buildin_filter'=>true,
			'use_view_tab'=>true,
            'actions'=>array(

               // array('label'=>app::get('b2c')->_('添加品牌'),'icon'=>'add.gif','href'=>'index.php?app=b2c&ctl=admin_brand&act=create','target'=>'_blank'),

            )
            ));
    }

     function _views(){
	    $sub_menu = array();
        $mdl_redbag= $this->app->model('sandorder');
		//全部记录
            $count = $mdl_redbag->count("");
            if($count >0){
                $sub_menu[0] = array('label'=>app::get('b2c')->_('全部'),'optional'=>true,'filter'=>"",'addon'=>$count,'href'=>'index.php?app=sand&ctl=admin_sandorder&act=index');
            }
		
            $filter1 = array('status'=>array('0','2'));
            $count1 = $mdl_redbag->count($filter1);
            if($count1 >0){
                $sub_menu[1] = array('label'=>app::get('b2c')->_('未充值'),'optional'=>true,'filter'=>$filter1,'addon'=>$count1,'href'=>'index.php?app=sand&ctl=admin_sandorder&act=index&view=1');
            }
		
			$filter2 = array('status'=>'1');
			 $count2 = $mdl_redbag->count($filter2);
            if($count2 >0){
                $sub_menu[2] = array('label'=>app::get('b2c')->_('已充值'),'optional'=>true,'filter'=>$filter2,'addon'=>$count2,'href'=>'index.php?app=sand&ctl=admin_sandorder&act=index&view=2');
            }
		
			return  $sub_menu;
	}
}	