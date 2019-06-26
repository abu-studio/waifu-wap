<?php
 

class jdbookconsole_ctl_admin_jdorder extends desktop_controller{

    var $workground = 'jdbookconsole.workground.order';

	public function index(){

		$finder_arr = array(

				'title'=>app::get('b2c')->_('京东第三方订单列表'),
				'allow_detail_popup'=>true,
				'base_filter'=>array('order_refer'=>'local','disabled'=>'false','order_kind'=>'jdbook'),
				'use_buildin_export'=>true,
				'use_view_tab'=>true,
				'force_view_tab'=>true,
				'use_buildin_set_tag'=>false,
				'use_buildin_recycle'=>false,
				'use_buildin_filter'=>true,
		);
		if($_GET['view'] == '6') {
			$finder_arr['actions']= array(
					array('label'=>app::get('b2c')->_('强制下单'),'submit'=>'index.php?app=jdbookconsole&ctl=admin_jdorder&act=jd_submit','target'=>'_self'),
					array('label'=>app::get('b2c')->_('强制对账'),'submit'=>'index.php?app=jdbookconsole&ctl=admin_jdorder&act=jd_checkorders','target'=>'_self'),

			);
		}

        $this->finder('jdbookconsole_mdl_jdorders',$finder_arr);

    }

	public function _views(){
		$mdl_orders = app::get('jdsale')->model('jdorders');

		$order_filter=array('order_kind'=>'jdbook');
        $order_filter_2 = array_merge($order_filter,array('jdstatus|noequal' =>'close'));
        $sub_menu = array(
            0=>array('label'=>app::get('jdsale')->_('全部'),'optional'=>false,'filter'=>$order_filter),
            1=>array('label'=>app::get('jdsale')->_('京东待审核'),'optional'=>false,'filter'=>array_merge($order_filter_2,array('order_state'=>9))),
            2=>array('label'=>app::get('jdsale')->_('新建订单'),'optional'=>false,'filter'=>array_merge($order_filter_2,array('order_state'=>0))),
            3=>array('label'=>app::get('jdsale')->_('已妥投订单'),'optional'=>false,'filter'=>array_merge($order_filter_2,array('order_state'=>1))),
            4=>array('label'=>app::get('jdsale')->_('已拒收订单 '),'optional'=>false,'filter'=>array_merge($order_filter_2,array('order_state'=>2))),
            5=>array('label'=>app::get('jdsale')->_('正常订单'),'optional'=>false,'filter'=>array_merge($order_filter_2,array('check_status'=>1))),
            6=>array('label'=>app::get('jdsale')->_('异常订单'),'optional'=>false,'filter'=>array_merge($order_filter_2,array('check_status'=>2))),
            7=>array('label'=>app::get('jdsale')->_('已关闭订单'),'optional'=>false,'filter'=>array_merge($order_filter_2,array('check_status'=>2))),
        );
        
        foreach($sub_menu as $k=>$v){
            if($v['optional']==false){
                $show_menu[$k] = $v;
                $show_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
                $show_menu[$k]['addon'] = $mdl_orders->count($v['filter']);
                $show_menu[$k]['href'] = 'index.php?app=jdbookconsole&ctl=admin_jdorder&act=index&view='.($k).'&view_from=dashboard';
            }elseif(($_GET['view_from']=='dashboard')&&$k==$_GET['view']){
                $show_menu[$k] = $v;
            }
        }
		
        return $show_menu;
    }

	function search_info(){
		if($_POST['order_id']){
			$router = app::get('site')->router();
			$suborders = app::get('jdsale')->model('jd_suborders');
			$goodsObj = app::get('b2c')->model('goods');
			$order_id = trim($_POST['order_id']);
			//$ =  $suborders->getRow('*',array('order_id'=>$order_id));
			
			//lpc
			$jdOrdersData = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$order_id),'order_kind');
            $jdgoodsKind = "normal";
			if ($jdOrdersData['order_kind'] == "jdbook") {
				$jdgoodsKind = "book";
			}
			
			$params = array('jdOrderId'=>$order_id);
			$Data = kernel::single('jdsale_api_orders')->getOrderJdOrder($params,$jdgoodsKind);
			$data_tmp = $Data['result'];
			if($data_tmp){
				if($data_tmp['pOrder']){
					if(is_array($data_tmp['pOrder'])){
						 $datalist = $data_tmp;
					}else{
						$params = array('jdOrderId'=>$data_tmp['pOrder']);
						$data = kernel::single('jdsale_api_orders')->getOrderJdOrder($params,$jdgoodsKind);
						$datalist = $data['result'];
					}
					foreach($datalist['cOrder'] as $key=>$val){
						foreach($val['sku'] as $k=>$v){
						  $goodsDate = $goodsObj->getRow('goods_id',array('bn'=>$v['skuId']));
						  $datalist['cOrder'][$key]['sku'][$k]['link'] = $router->gen_url(array('app'=>'jdbookconsole', 'ctl'=>'site_product','act'=>'index','arg0'=>$goodsDate['goods_id']));
						}
					}
					$this->pagedata['datalist'] = $datalist;
				}else{
					foreach($data_tmp['sku'] as $key=> $val){
						$goodsDate = $goodsObj->getRow('goods_id',array('bn'=>$val['skuId']));
						$data_tmp['sku'][$key]['link'] = $router->gen_url(array('app'=>'jdbookconsole', 'ctl'=>'site_product','act'=>'index','arg0'=>$goodsDate['goods_id']));
					}
					$this->pagedata['data'] = $data_tmp;
				}
            }else{
				$this->pagedata['null'] = true;
			}
			$this->pagedata['order_id'] = $order_id;
		}
		
		$this->page('admin/search_info.html');
	}

	function jd_submit(){
		if(count($_POST['order_id']) == 0 && $_POST['_finder']['select'] != 'multi' && !$_POST['_finder']['id'] && !$_POST['filter']){
			echo __('请选择异常订单');
			exit;
		}
		$this->begin('index.php?app=jdbookconsole&ctl=admin_jdorder&act=index');
		//var_dump($_POST['order_id']);die();
		kernel::single('jdsale_autofinshjdorders')->manual_submit_orders($_POST['order_id']);

		$this->end(true, app::get('b2c')->_('完成强制下单'));

	}

	function jd_checkorders(){


		if(count($_POST['order_id']) == 0 && $_POST['_finder']['select'] != 'multi' && !$_POST['_finder']['id'] && !$_POST['filter']){
			echo __('请选择异常订单');
			exit;
		}

		$this->begin('index.php?app=jdbookconsole&ctl=admin_jdorder&act=index');

		kernel::single('jdsale_checkjdorders')->manual_checkorders($_POST['order_id']);

		$this->end(true, app::get('b2c')->_('完成强制对账'));
	}
     
}	