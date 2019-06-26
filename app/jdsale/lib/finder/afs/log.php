<?php
class jdsale_finder_afs_log{
    var $detail_basic = '基本信息';
    

    function __construct($app){
        $this->app = $app;
    }

	//基本信息
    function detail_basic($gid){
		$render = $this->app->render();
		$afslogDate = app::get('jdsale')->model('afs_log')->getRow('jd_order_id,sku_id,jd_suborder_id',array('log_id'=>$gid));
		
		$order_id = $afslogDate['jd_suborder_id'] ?  $afslogDate['jd_suborder_id'] : $afslogDate['jd_order_id'];

		$params  = array('param' =>
                            array('jdOrderId'=>$order_id,
                                'pageIndex'=>1,
                                'pageSize' =>10));
		//lpc 获取类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$order_id),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

		$result = kernel::single('jdsale_api_aftersales')->afterSaleServiceList($params,$jdgoodsKind);
		foreach($result['result']['serviceInfoList'] as $val){
			$serviceInfoListDate[$val['wareId']] = $val['afsServiceId'];
		}
		
		$params =array('param' =>
				array('afsServiceId'=>$serviceInfoListDate[$afslogDate['sku_id']],
				'appendInfoSteps'=> array(1,2,3)));

		$res = kernel::single('jdsale_api_aftersales')->afterSaleServiceDetail($params,$jdgoodsKind);
		

		$render->pagedata['data'] = $res['result'];
		return $render->fetch('admin/aftersale/aftersale_detail.html');
    }
}
