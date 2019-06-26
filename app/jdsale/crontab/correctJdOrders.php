<?php
require_once(dirname(__FILE__) . '/../lib/config/config.php');
require_once(ROOT_DIR . '/config/SFSC.HTTPClient.class.php');

class sync {

    public function init(){
        $this->_do_sync();
    }

    private function _do_sync(){		
		ini_set('date.timezone','Asia/Shanghai');

        $orderArr = $this->_get_abnormal_order();
        $orderList = $orderArr['orders'];
        $objectsList = $orderArr['objects'];

        $msg = '';
        foreach ($orderList as $orderItem){
            $orderId = $orderItem['order_id'];
            $memberId = $orderItem['member_id'];
            $memberUname = $this->_get_member_info($memberId);
            $orderObjects = $objectsList[$orderId];
            $this->get_jdorders($orderItem , $orderObjects ,$msg ,  $memberUname);
        }
    }

    private function _get_abnormal_order(){
        $filter = array("order_id" => array("2018060610636263","2018060611108195","2018060611176851","2018060611588729","2018060613750099","2018060615673442","2018060615727444","2018060617194757","2018060617968064","2018060618087315","2018060618113684","2018060619500909","2018060620229946","2018060621593030","2018060623121404","2018060623297145","2018060623447317","2018060623946787","2018060700939826","2018060709787647","2018060710147671","2018060713662607","2018060717880251","2018060718975906","2018060720351187","2018060720641087","2018060722982070","2018060809545074","2018060810355440","2018060810383735","2018060811375872","2018060815940232","2018060817022597","2018060818642099","2018060818676347","2018060819604776","2018060821987288","2018060822712953","2018060823911016","2018060901448709","2018060911821464","2018060918265765","2018061010811678","2018061011173811","2018061021434877","2018061101277660","2018061101452350","2018061109903503","2018061111111038","2018061112053496","2018061113550157","2018061115987053","2018061116701586","2018061117051363","2018061117308529","2018061118040292","2018061118080975","2018061119069638","2018061119406968","2018061121048571","2018061122243719","2018061201461745","2018061207480118","2018061212315702","2018061212471366","2018061212478226","2018061212816389","2018061213220454","2018061213518034","2018061214497063","2018061215986660","2018061216555690","2018061218069158","2018061218650213","2018061219524072","2018061220801065","2018061222396923","2018061222699003","2018061222962710","2018061223066126","2018061223114541","2018061223405967","2018061223452424","2018061223900743","2018061223994774","2018061309009129","2018061309546501","2018061314910798","2018061315919093","2018061316544237","2018061411003181","2018061411189749","2018061411254213","2018061411417066","2018061411625780","2018061414077228","2018061414228945","2018061414312645","2018061416150760","2018061416249930","2018061416862580"));
        $orderList = app::get("b2c")->model("orders")->getList("*",$filter);

        $orderObjectList = app::get("b2c")->model("order_items")->getList("*",$filter);
        $newObjectList = array();
        foreach($orderObjectList as $index => $item){
            $orderId = $item['order_id'];
            if(! isset($newObjectList[$orderId])){
                $newObjectList[$orderId] = array();
            }

            array_push($newObjectList[$orderId] , $item);
        }

        return array('orders' => $orderList , 'objects' => $newObjectList);
    }

    private function _get_member_info($memberId){
        $memberId = intval($memberId);
        if($memberId < 1){
            exit("会员ID, 不正确,请核对");
        }


        $filter = array("account_id" => $memberId);
        $memberInfo = app::get("pam")->model("account")->getRow("login_name",$filter);

        return $memberInfo['login_name'];
    }

    //京东预占库存订单
    private function get_jdorders($order_data , $orderObjects,&$msg,$memberUname,$jdgoodsKind='normal'){
        if(empty($order_data) || empty($orderObjects) || empty($memberUname)){
            return false;
        }
        if($order_data['pay_status'] != '1'){
            return false;
        }

        $area_id = explode(':',$order_data['ship_area']);
        $mdl_jdsale_regions = app::get('jdsale')->model('regions');
        $jdsale_array = $mdl_jdsale_regions->dump(array('area_id'=>$area_id[2]),'region_path');

        $jdsale_area_array = explode(",",$jdsale_array['region_path']);
        foreach( $jdsale_area_array as $k1=>$v1){
            if( !$v1 )
                unset( $jdsale_area_array[$k1] );
        }
        reset($jdsale_area_array);
        $jd_sku =array();
        foreach($orderObjects as $k=>$v){
            $jd_sku[] = array(
                'skuId'=>intval($v['bn']),
                'num'=>intval($v['nums']),
            );
        }

        //获取rdp邮箱接口 _ start
        $email = "Abd@yoofuu.com";
        if($memberUname){
            $_sjson = array(
                'METHOD'=>'getSingleEmployeeByHumbasNo',
                'HUMBAS_NO'=>$memberUname
            );
            $post_data = array('serviceNo'=>'EmployeeService',"inputParam"=>json_encode($_sjson));
            $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
            $tmp2 = SFSC_HttpClient::objectToArray($tmpdata);
            $email = $tmp2['RESULT_DATA']['EMAIL'];
        }

        //获取rdp邮箱接口 _ end
        $param = array(
            'thirdOrder'=>$order_data['order_id'],
            'sku'=>$jd_sku,
            'name'=>$order_data['ship_name'],
            'province'=>$jdsale_area_array[1],
            'city'=>$jdsale_area_array[2]?$jdsale_area_array[2]:0,
            'county'=>$jdsale_area_array[3]?$jdsale_area_array[3]:0,
            'town'=>$jdsale_area_array[4]?$jdsale_area_array[4]:0,
            'address'=>$order_data['ship_addr'],
            'zip'=>$order_data['ship_zip'],
            'mobile'=>$order_data['ship_mobile'],
            'email'=>$email,
            'remark'=>$order_data['memo'],
            'invoiceState'=>'2',
            'invoiceType'=>'2',
            'selectedInvoiceTitle'=>'5',
            'invoiceContent'=>'1',
            'companyName'=>'上海外服商务管理有限公司',
            'paymentType'=>'5',
            'isUseBalance'=>'0',
            'submitState'=>'0',
            'reservingDate'=>'-1',
        );

        //lpc 改增值发票还是普通发票
        if ($jdgoodsKind == "book") {
            $param['invoiceType'] = '1';
        }

        $jdorde_obj = kernel::single('jdsale_api_orders');
        $jd_data = $jdorde_obj->getOrderSubmit($param,$jdgoodsKind);
        $jd_data['resultMessage'] = str_replace(array('\r','\n',"\r","\n"),'',$jd_data['resultMessage']);
        $jd_data['resultMessage'] = trim($jd_data['resultMessage']);
        if(!empty($jd_data['resultMessage'])){
            $msg = $jd_data['resultMessage'];
        }

        if($jd_data['success'] == false){
            $msg = empty($msg) ? "抱歉！订单中存在商品不支持本区域购买" : $msg;

            $oGoods = app::get("b2c")->model("goods");
            foreach ($jd_sku as $jk => $jv) {
                $goodsData = $oGoods->getRow('name',array('bn'=>$jv['skuId']));
                $msg = preg_replace('#\b'.$jv['skuId'].'\b#',$goodsData['name'],$msg);
                $msg = str_replace("[", "", $msg);
                $msg = str_replace("]", "", $msg);
            }

            return false;
        }else{
            $msg = empty($msg) ? "下单成功" : $msg;
            //如果有数据返回，需要记录该订单与第京东订单的关联关系
            $jdorder_mdl = app::get("jdsale")->model("jdorders");
            $jdorders = array(
                "jdorders_id" => $jd_data['result']['jdOrderId'],
                "order_id" => $order_data['order_id'],
                "createtime" => time(),
            );

            if ($jdgoodsKind == "book") {
                $jdorders['order_kind'] = "jdbook";
            }

            $jdorder_mdl->insert($jdorders);
            return true;
        }

    }
}
$sync = new sync();
$sync->init();

?>