<?php
class jdsale_finder_jdorders{
    var $detail_basic = '基本信息';
    var $detail_delivery = '订单物流信息';


    function __construct($app){
        $this->app = $app;
    }

	//基本信息
    function detail_basic($gid){
		$render = $this->app->render();
		$goodsObj = app::get('b2c')->model('goods');
		$obj =  $this->app->model('jdorders');
		$jdorderData = $obj->getRow('jdorders_id,order_kind',array('order_id'=>$gid));
		$params = array('jdOrderId'=>$jdorderData['jdorders_id']);

		//lpc 获取类型（goods、book）
        $jdgoodsKind = "normal";
        if ($jdorderData['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

		$Data = kernel::single('jdsale_api_orders')->getOrderJdOrder($params,$jdgoodsKind);
		$router = app::get('site')->router();
		
		if ($Data['result']['cOrder']){
			$Data_tmp = $Data['result']['cOrder'];
			foreach($Data_tmp as $key=>$val){
				foreach($val['sku'] as $k=>$v){
				  $goodsDate = $goodsObj->getRow('goods_id',array('bn|tequal'=>$v['skuId']));
				  $Data_tmp[$key]['sku'][$k]['link'] = $router->gen_url(array('app'=>'jdsale', 'ctl'=>'site_product','act'=>'index','arg0'=>$goodsDate['goods_id']));
				}
			}
			$render->pagedata['data'] = $Data_tmp;
			return $render->fetch('admin/order/orders_detail.html');
		}else{
			$Data_tmp = $Data['result'];
			foreach($Data_tmp['sku'] as $key=> $val){
				$goodsDate = $goodsObj->getRow('goods_id',array('bn|tequal'=>$val['skuId']));
				$Data_tmp['sku'][$key]['link'] = $router->gen_url(array('app'=>'jdsale', 'ctl'=>'site_product','act'=>'index','arg0'=>$goodsDate['goods_id']));
			}
			$render->pagedata['data'] = $Data_tmp;		
		    return $render->fetch('admin/order/order_detail.html');
		}
    }

	//物流信息
	function detail_delivery($gid){
		$render = $this->app->render();
		$obj =  $this->app->model('jdorders');
		$jdorderData = $obj->getRow('jdorders_id,order_kind',array('order_id'=>$gid));
		$param = array('jdOrderId'=>$jdorderData['jdorders_id']);

		//lpc 获取类型（goods、book）
        $jdgoodsKind = "normal";
        if ($jdorderData['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

		$Data = kernel::single('jdsale_api_orders')->getOrderJdOrder($param,$jdgoodsKind);

		
		if ($Data['result']['cOrder']){
			foreach($Data['result']['cOrder'] as $val){
				$params = array('jdOrderId'=>$val['jdOrderId']);
				$Data_tmp = kernel::single('jdsale_api_orders')->getOrderTrack($params,$jdgoodsKind);
				$Datas[] = $Data_tmp['result'];
			}
			$render->pagedata['data'] = $Datas;
			return $render->fetch('admin/order/orders_delivery.html');
		}else{
			$params = array('jdOrderId'=>$jdorderData['jdorders_id']);
			$Datas = kernel::single('jdsale_api_orders')->getOrderTrack($params,$jdgoodsKind);
			$render->pagedata['data'] = $Datas['result'];
			return $render->fetch('admin/order/order_delivery.html');
		}
	}    
}
