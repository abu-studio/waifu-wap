<?php


class jdsale_ctl_wap_aftersales extends b2c_ctl_wap_member
{

    public function __construct($app)
    {
        if(is_array($app)){
            $verify = $app['arg1'];
            $app = $app['app'];
        }else{
            $verify = true;
        }
        $this->app_current = $app;
        $this->app_b2c = app::get('b2c');
        parent::__construct($this->app_b2c,$verify);
    }

    public function index($order_id)
    {
        if (empty($order_id))
        {
            $order_skus_url = array('app' => 'b2c','ctl' => 'wap_member','act'=>'index');
            $this->redirect($order_skus_url);
        }
        $order_skus_url = array('app' => 'jdsale','ctl' => 'wap_aftersales','act'=>'order_skus','arg0'=>$order_id);
        $this->redirect($order_skus_url);
    }

    public function order_skus($order_id)
    {
        if (empty($order_id))
        {
            $order_skus_url = array('app' => 'b2c','ctl' => 'wap_member','act'=>'index');
            $this->redirect($order_skus_url);
        }
        $this->pagedata['order_id'] = $order_id;
        $skus = $this->getSkus($order_id);
        $result = $this->getJdOrderID($order_id);
        if ($result['success']){
            $jdOrderId = $result['result'];
            $this->pagedata['jdOrderId'] = $jdOrderId;
        }else{
            $this->pagedata['availableMsg'] = $result['resultMessage'];
            $this->page('wap/member/order_skus.html',false,'jdsale');
        }
        //需判断是否有拆单
        $mdl_jd_suborders = $this->app_current->model('jd_suborders');
        $sub_filter = array('jd_order_id'=>$jdOrderId);
        $c = $mdl_jd_suborders->count($sub_filter);
        if ($c>0){
            $has_sub_order = true;
            $this->pagedata['hasSubOrder']=1;
        }else{
            $has_sub_order = false;
            $this->pagedata['hasSubOrder']=0;
        }

        if($has_sub_order){
            $this->pagedata['hasService'] = 0;
            foreach ($skus as $k => $v){

                $jd_suborder_info = $mdl_jd_suborders->getRow('*',array_merge($sub_filter,array('sku_id'=>$v['bn'])));
                $jd_suborder_id = $jd_suborder_info['jd_suborder_id'];

                $available = $this->afterSaleAvailable($jd_suborder_id,$v['bn']);
                $skus[$k]['jd_suborder_id'] = $jd_suborder_id;
                if ($available === true){
                    $skus[$k]['afterSales'] = 1;

                }else{
                    $skus[$k]['afterSales'] = 0;
                    $this->pagedata['availableMsg'] = $available;
                }
                if($this->hasafsService($jd_suborder_id)){
                    $this->pagedata['hasService'] = 1;
                }
            }

        }else{
            $this->pagedata['hasService'] = $this->hasafsService($jdOrderId);
            foreach ($skus as $k => $v){
                $available = $this->afterSaleAvailable($jdOrderId,$v['bn']);
                if ($available === true){
                    $skus[$k]['afterSales'] = 1;

                }else{
                    $skus[$k]['afterSales'] = 0;
                    $this->pagedata['availableMsg'] = $available;
                }
            }
        }
        $this->pagedata['skus'] = $skus;
        $this->page('wap/member/order_skus.html',false,'jdsale');
    }

    public function apply(){

        $order_id = $_POST['order_id'];
        $jdOrderId = $_POST['jdOrderId'];
        $jd_suborder_id =$_POST['jd_suborder_id'];
        if (empty($jd_suborder_id)){
            $has_sub_order = false;
        }else{
            $has_sub_order = true;
            $jdOrderId = $jd_suborder_id;
        }

        $sku_id = $_POST['sku_id'];
        $apply = $_POST;

        $apply['customerExpect'] = $this->afterSaleCustomerExpect($jdOrderId,$sku_id);
        $apply['pickWareType'] =$this->afterSaleWareReturnJd($jdOrderId,$sku_id);;

        $orderInfo = $this->getOrderInfo($order_id);
        $apply['pickArea'] = $orderInfo['ship_area'];
        $apply['pickAddr'] = $orderInfo['ship_addr'];
        $apply['returnArea'] = $orderInfo['ship_area'];
        $apply['returnAddr'] = $orderInfo['ship_addr'];

        $apply['customerContactName'] = $orderInfo['ship_name'];
        $apply['customerTel'] = $orderInfo['ship_mobile'];
        $apply['customerMobilePhone'] = $orderInfo['ship_mobile'];
        $apply['customerPostcode'] = $orderInfo['ship_zip'];

        $member_id = $orderInfo['member_id'];
        $mdl_member = $this->app_b2c->model('members');
        $member_info = $mdl_member->get_member_info( $member_id );

        $apply['customerEmail'] = $member_info['email'];
        $apply['member_id'] = $member_info['member_id'];
        $apply['member_name'] = $member_info['uname'];

        $this->pagedata['apply'] = $apply;
        $this->page('wap/member/apply.html',false,'jdsale');
    }

    public function save_apply(){

        if (empty($_POST['jd_suborder_id'])){
            $hasSubOrder = '0';
        }else{
            $hasSubOrder = '1';
        }

        $sucess_url = array('app' => 'jdsale','ctl' =>'wap_aftersales','act'=>'service_list',
                            'arg0'=>$_POST['jdOrderId'],'arg1'=>$_POST['order_id'],'arg2'=>$hasSubOrder );
        $failed_url = array('app' => 'jdsale','ctl' => 'wap_aftersales', 'act'=>'order_skus' ,
                            'arg0'=>$_POST['order_id']);
        $this->begin();

        if ($this->apply_afs($_POST)){
            $this->end(true,app::get('jdsale')->_('该服务申请单保存成功！'),$sucess_url);
        }else{
            $this->end(false,app::get('jdsale')->_('该服务单申请失败！'),$failed_url);
        }

    }

    public function service_list($jdOrderId,$order_id,$hasSubOrder,$nPage=1){
        $mdl_jd_suborders = $this->app_current->model('jd_suborders');
        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $totalNum = 0;
        // lpc 增加jd_suborders表查询字段order_kind来判断是什么（book？）
        $jdgoodsKind = "normal";
        if($hasSubOrder === '1'){
            $jd_sub_order = $mdl_jd_suborders->db->select(
                "SELECT DISTINCT jd_suborder_id,order_kind FROM sdb_jdsale_jd_suborders WHERE jd_order_id ='".$jdOrderId."'");
            $sub_afsServiceList = array();
            foreach($jd_sub_order as $k=>$v){
                //lpc 
                if ($v['order_kind'] == "jdbook")
                    $jdgoodsKind = "book";

                $afsServiceList = $jdsale_api_aftersales -> afterSaleServiceList(
                    array('param'=> array('jdOrderId'=>$v['jd_suborder_id'],'pageIndex'=>$nPage,'pageSize' =>$this->pagesize)),$jdgoodsKind);
                if ($afsServiceList['result']){
                    $totalNum += $afsServiceList['result']['totalNum'];
                    $sub_afsServiceList = array_merge($afsServiceList['result']['serviceInfoList'],$sub_afsServiceList);
                }
            }
            $this->pagedata['afsServiceList'] = $sub_afsServiceList;

        }else{

            //lpc 获取售后类型（goods、book）
            $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jdOrderId),'order_kind');
            if ($jdGoods['order_kind'] == "jdbook")
                $jdgoodsKind = "book";

            $afsServiceList = $jdsale_api_aftersales -> afterSaleServiceList(
                array('param'=> array('jdOrderId'=>$jdOrderId,'pageIndex'=>$nPage,'pageSize' =>$this->pagesize)),$jdgoodsKind);
            $totalNum = $afsServiceList['result']['totalNum'];

            $this->pagedata['afsServiceList'] = $afsServiceList['result']['serviceInfoList'];
        }
        $this->pagedata['hasSubOrder'] = $hasSubOrder;
        $this->pagedata['jdOrderId'] = $jdOrderId;
        $this->pagedata['order_id'] = $order_id;

        $arr_args = array($jdOrderId,$order_id,$hasSubOrder);
        $arrMaxPage = $this->get_start($nPage, $totalNum);
        $this->pagination($nPage, $arrMaxPage['maxPage'], 'service_list', $arr_args, 'jdsale', 'wap_aftersales');
        $this->page('wap/member/service_list.html',false,'jdsale');
    }

    public function service_detail($afsServiceId,$order_id,$jd_order_id,$hasSubOrder){

        //lpc 获取售后类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jd_order_id),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $afsServiceDetail = $jdsale_api_aftersales -> afterSaleServiceDetail(
            array('param'=> array('afsServiceId'=>$afsServiceId,
                                  'appendInfoSteps'=> array())),$jdgoodsKind);
        $afsServiceDetail['result']['customerExpect']=$this->getCustomerExpectName($afsServiceDetail['result']['customerExpect']);
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['jdOrderId'] = $jd_order_id;
        $this->pagedata['afsServiceDetail']= $afsServiceDetail['result'];
        $this->pagedata['hasSubOrder'] = $hasSubOrder;
        $this->page('wap/member/service_detail.html',false,'jdsale');
    }

    public function cancel_apply($afsServiceId,$jdOrderID,$order_id){
        $this->begin(array('app' => 'jdsale','ctl' => 'wap_aftersales', 'act'=>'service_list' ,
                           'arg0'=>$jdOrderID ,'arg1'=>$order_id ));

        //lpc 获取类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jdOrderID),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $result  = $jdsale_api_aftersales -> afterSaleAuditCancel(
            array('param'=> array('serviceIdList'=>array($afsServiceId),'approveNotes'=> 'cancel')),$jdgoodsKind);
        if ($result['result']){
            $this->end(true, app::get('jdsale')->_('该服务单取消操作成功！'));

        }else{
            $this->end(false, app::get('jdsale')->_('该服务单取消操作失败！'));
        }
    }

    private function getSkus($order_id){
        $mdl_order_items = app::get('b2c')->model('order_items');
        $filter = array('order_id'=>$order_id);
        $skus = $mdl_order_items->getList('goods_id,bn,name,nums',$filter);
        return $skus;
    }

    private function getOrderInfo($order_id){
        $mdl_orders = app::get('b2c')->model('orders');
        $filter = array('order_id'=>$order_id);
        $order = $mdl_orders->getRow('*',$filter);
        return $order;
    }

    private function afterSaleAvailable($jdOrderID,$sku_id){

        //lpc 获取售后类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jdOrderID),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $result = $jdsale_api_aftersales -> afterSaleAvailable(
            array('param'=> array('jdOrderId'=>$jdOrderID,'skuId'=>$sku_id)),$jdgoodsKind);
        if ($result['result']>0&&$result['success']){
            return true;
        }
        return $result['resultMessage'];
    }

    private function hasafsService($jdOrderID){

        //lpc 获取售后类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jdOrderID),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $result = $jdsale_api_aftersales -> afterSaleServiceList(
            array('param'=> array('jdOrderId'=>$jdOrderID,'pageIndex'=>1,'pageSize' =>10)),$jdgoodsKind);
        if (empty($result['result'])){
            return 0;
        }else{
            return 1;
        }
    }

    private function getJdOrderID($order_id){

        //lpc 获取类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('order_id'=>$order_id),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_orders = kernel::single('jdsale_api_orders');
        $result = $jdsale_api_orders -> getOrderJdOrderIDByThridOrderID(array('thirdOrder'=>$order_id),$jdgoodsKind);
        return $result;
    }

    private function afterSaleCustomerExpect($jdOrderID,$sku_id){

        //lpc 获取类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$jdOrderID),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $result = $jdsale_api_aftersales -> afterSaleCustomerExpect(
            array('param'=> array('jdOrderId'=>$jdOrderID,'skuId'=>$sku_id)),$jdgoodsKind);
        return $result['result'];
    }

    private function afterSaleWareReturnJd($jdOrderID,$sku_id){

        //只保留上门取件
        $result = array(array('code' =>4,'name' =>'上门取件'));
        return $result;

        //$jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        //$result = $jdsale_api_aftersales -> afterSaleWareReturnJd(
        //    array('param'=> array('jdOrderId'=>$jdOrderID,'skuId'=>$sku_id)));

        //return $result['result'];
    }

    private function apply_afs($apply){
        if(empty($apply)){
            return false;
        }
        $order_id = $apply['order_id'];
        $pickAreaID = $this->getAreaID($apply['pickArea']);
        if ($apply['customerExpect'] != '10'){
            $returnAreaID = $this->getAreaID($apply['returnArea']);
            $apply['refund_status'] = 0;
        }else{
            $returnAreaID = $pickAreaID;
            $apply['refund_status'] = 1;
        }
        if (empty($apply['jd_suborder_id'])){
            $jdOrderId = $apply['jdOrderId'];
        }else{
            $jdOrderId = $apply['jd_suborder_id'];
        }

        //lpc 获取售后类型（goods、book）
        $jdGoods = app::get('jdsale')->model('jdorders')->dump(array('jdorders_id'=>$apply['jdOrderId']),'order_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['order_kind'] == "jdbook")
            $jdgoodsKind = "book";

        $params = array('param' =>
            array(
                'jdOrderId'=>$jdOrderId,//订单号,必填
                'customerExpect'=>$apply['customerExpect'],//客户预期（退货(10)、换货(20)、维修(30)），非必填
                'questionDesc'=>$apply['questionDesc']?$apply['questionDesc']:'',//产品问题描述，非必填
                'isNeedDetectionReport'=>0,//是否需要检测报告,非必填
                'questionPic'=>'',//问题描述图片,非必填
                'isHasPackage'=> $apply['isHasPackage']?1:0,//是否有包装,非必填
                'packageDesc'=> $apply['packageDesc']?$apply['packageDesc']:'',//包装描述,非必填
                //客户信息实体,必填
                'asCustomerDto' => array(
                    'customerContactName' =>$apply['customerContactName'],//联系人
                    'customerTel' =>$apply['customerTel'],//联系电话
                    'customerMobilePhone' =>$apply['customerMobilePhone'],//手机号
                    'customerEmail' =>$apply['customerEmail'],//Email
                    'customerPostcode' =>$apply['customerPostcode'],//邮编
                ),
                //取件信息实体,必填,原商品如何返回京东或者卖家，如果不为取件方式，默认设置订单中省市县镇信息
                'asPickwareDto' => array(
                    'pickwareType' => $apply['pickWareType'],//取件方式 : 4 上门取件 7 客户送货 40客户发货
                    'pickwareProvince' => $pickAreaID[1],//取件省
                    'pickwareCity' => $pickAreaID[2],//取件市
                    'pickwareCounty' => $pickAreaID[3],//取件县
                    'pickwareVillage' => $pickAreaID[4]?$pickAreaID[4]:0,//取件乡镇
                    'pickwareAddress' => $apply['pickAddr'],//取件街道地址
                ),
                //返件信息实体,必填
                'asReturnwareDto' => array(
                    "returnwareType" => 10,//返件方式 自营配送(10),第三方配送(20);换、修这两种情况必填（默认值）
                    "returnwareProvince" => $returnAreaID[1],//返件省 换、修这两种情况必填
                    "returnwareCity" => $returnAreaID[2],//返件市
                    "returnwareCounty" => $returnAreaID[3],//返件县
                    "returnwareVillage" => $returnAreaID[4]?$returnAreaID:0,//返件乡镇
                    "returnwareAddress" => $apply['returnAddr'],//返件街道地址, 换、修这两种情况必填
                ),
                //申请单明细,必填
                'asDetailDto' => array(
                    'skuId' => $apply['sku_id'],//商品编号
                    'skuNum' => $apply['apply_num'],//申请数量
                )
            )
        );

        $jdsale_api_aftersales = kernel::single('jdsale_api_aftersales');
        $result = $jdsale_api_aftersales -> afterSaleApply($params,$jdgoodsKind);
        if ($apply['refund_status'] === 1){
            $return_id = $this->return_save($apply);
        }

        $apply2 = array(
            'order_id' => $order_id,
            'order_kind'=>$jdGoods['order_kind'],
            'jd_order_id' => $apply['jdOrderId'],
            'jd_suborder_id' => $apply['jd_suborder_id'],
            'sku_id' => $apply['sku_id'],
            'apply_num' => $apply['apply_num'],
            'sku_name' => $apply['sku_name'],
            'customerExpect' => $apply['customerExpect'],
            'applyer_id' => $apply['member_id'],
            'applyer_name' => $apply['member_name'],
            'refund_status' => $apply['refund_status'],
            'apply_time' => time(),
        );
		
		if ($return_id){
            $apply2['return_id'] = $return_id;
        }
        if ($result['success']){
            $apply2['result'] = 'SUCCESS';
			
			//保存申请记录
			$mdl_afs_log = $this->app_current->model('afs_log');
			$mdl_afs_log->insert($apply2);
			
			//用户点击 退货，换货，维修 后，自动订单完成时间往后推7天
			$objOrder = app::get('b2c')->model('orders');
			$confirm_time = $objOrder->getRow('confirm_time,status', array('order_id' => $order_id ));
			$time = time() + 7*86400;
			/*
			if ($confirm_time['confirm_time']) {
				$time = $confirm_time['confirm_time'] + 7*86400;
			}else{
				$time = time() + 7*86400;
			}
			*/
			$objOrder->update(array('confirm_time' => $time),array('order_id' => $order_id ));
        }else{
            $apply2['result'] = 'FAILURE';
        }
        
        return $result['success'];
    }

    public function return_save($apply){
        $mdl_goods = app::get('b2c')->model('goods');
        $objOrder = app::get('b2c')->model('orders');

        $order_id = $apply['order_id'];
        $product_data = array();
        $item = array();
        $item['bn'] = $apply['sku_id'];
        $item['name'] = $apply['sku_name'];
        $item['num'] = $apply['apply_num'];;
        $product_data[] = $item;

        $store_id = $objOrder->getRow('*',array('order_id'=>$order_id));
        $sto= kernel::single("business_memberstore",$order_id);
        $aData['store_id'] = $store_id['store_id'];
        $aData['order_id'] = $order_id;
        $aData['add_time'] = time();
        $aData['member_id'] = $store_id['member_id'];
        $aData['product_data'] = serialize($product_data);
        $aData['content'] = $apply['questionDesc'];
        $aData['status'] = 1;

        $order_item_filter = array('order_id'=>$order_id,
                                   'bn'=>$apply['sku_id'],);
        $mdl_order_items = app::get('b2c')->model('order_items');
        $sku_price = $mdl_order_items->getRow('price',$order_item_filter);
        $amount = $sku_price['price'] * $apply['apply_num'];

        $aData['amount'] = $amount;
        $aData['close_time'] = time()+86400*(app::get('b2c')->getConf('member.to_agree'));
        $aData['comment'] = app::get('aftersales')->_('京东售后申请退货和退款');
        $aData['refund_type'] = 2;
        $aData['is_safeguard'] = '2';
        $aData['safeguard_type'] = '1';
        $aData['safeguard_require'] = '2';
        $aData['seller_amount'] = $amount;

        $msg = "";
        $obj_aftersales = kernel::service("api.aftersales.request");
        if ($obj_aftersales->generate($aData, $msg)) {
            $return_id = $aData['return_id'];

            $obj_rpc_request_service = kernel::service('b2c.rpc.send.request');
            if ($obj_rpc_request_service && method_exists($obj_rpc_request_service, 'rpc_caller_request')) {
                if ($obj_rpc_request_service instanceof b2c_api_rpc_request_interface) {
                    $obj_rpc_request_service->rpc_caller_request($aData, 'aftersales');
                }
            } else {
                $obj_aftersales->rpc_caller_request($aData);
            }
            return $return_id;
        }else{
            return false;
        }
    }

    private function getAreaID($region_str){
        $region_str_arr =  explode(':',$region_str);
        $jdsale_regions_operation = kernel::single('jdsale_regions_operation');
        $region_path = $jdsale_regions_operation -> getRegionPathById($region_str_arr[2]);
        return $region_path;
    }

    private function getCustomerExpectName($applyCustomerExpect){
        if($applyCustomerExpect == '10'){
            $customerExpect = '退货';
        }elseif($applyCustomerExpect == '20'){
            $customerExpect = '换货';
        }elseif($applyCustomerExpect == '30'){
            $customerExpect = '维修';
        }
        return $customerExpect;
    }
}
