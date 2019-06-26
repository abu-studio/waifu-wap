<?php
/**
 * 售后服务的api接口方法
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/11
 * Time: 13:26
 */

class jdsale_api_aftersales extends jdsale_api_base {

    public function __construct($app) {
        parent::__construct($app);

    }


    /**
     * 3.1	服务单保存申请
     *
     * 接口依赖:
        需要该配送单已经妥投。
        需要先调用3.3接口校验订单中某商品是否可以提交售后服务
        需要先调用3.4接口查询支持的服务类型
        需要先调用3.5接口查询支持的商品返回京东方式
     * @param $params = array('param' =>
                array(
                    'jdOrderId'=>45794690848,//订单号,必填
                    'customerExpect'=>10,//客户预期（退货(10)、换货(20)、维修(30)），非必填
                    'questionDesc'=>'想要的不是这种耳机，要求无理由退货',//产品问题描述，非必填
                    'isNeedDetectionReport'=>0,//是否需要检测报告,非必填
                    'questionPic'=>'',//问题描述图片,非必填
                    'isHasPackage'=> 1,//是否有包装,非必填
                    'packageDesc'=>0,//包装描述,非必填
                    //客户信息实体,必填
                    'asCustomerDto' => array(
                        'customerContactName' =>'邵君',//联系人
                        'customerTel' =>'18951975938',//联系电话
                        'customerMobilePhone' =>'18951975938',//手机号
                        'customerEmail' =>'990024966@qq.com',//Email
                        'customerPostcode' =>'211312',//邮编
                    ),
                    //取件信息实体,必填,原商品如何返回京东或者卖家，如果不为取件方式，默认设置订单中省市县镇信息
                    'asPickwareDto' => array(
                        'pickwareType' => 4,//取件方式 : 4 上门取件 7 客户送货 40客户发货
                        'pickwareProvince' => 12,//取件省
                        'pickwareCity' => 904,//取件市
                        'pickwareCounty' => 3379,//取件县
                        'pickwareVillage' => 0,//取件乡镇
                        'pickwareAddress' => '郁金香路30号(吉美思大厦703室)',//取件街道地址
                    ),
                    //返件信息实体,必填
                    'asReturnwareDto' => array(
                        "returnwareType" => 10,//返件方式 自营配送(10),第三方配送(20);换、修这两种情况必填（默认值）
                        "returnwareProvince" => 12,//返件省 换、修这两种情况必填
                        "returnwareCity" => 904,//返件市
                        "returnwareCounty" => 3379,//返件县
                        "returnwareVillage" => 0,//返件乡镇
                        "returnwareAddress" => '郁金香路30号(吉美思大厦703室)',//返件街道地址, 换、修这两种情况必填
                    ),
                    //申请单明细,必填
                    'asDetailDto' => array(
                        'skuId' =>102818,//商品编号
                        'skuNum' => 1,//申请数量
                    )
            )
            )
     *
     * @return boolean 保存成功之后，根据'success'返回true，
     * 而'result'为NULL，需要再调用3.6 查询得到服务单号
     *
     * array (
    'success' => true,
    'resultMessage' => '成功',
    'resultCode' => '0',
    'result' => NULL,
    'code' => '0',
    ),
     */
    public function afterSaleApply($params,$jdgoodsKind='normal'){
        $api_function = '服务单保存申请';
        $method = 'afterSale/createAfsApply';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        if ($result['success']){
            $result['result'] = true;
        }else{
            $result['result'] = false;
        }
        return $result;
    }

    /**
     * 3.2	填写客户发运信息
     *  需要调用3.6 查询得到服务单号
        并且有需要补充客户发运信息时调用这个接口
     * @param $params =(array('param' =>
     *                          array(
                                    'afsServiceId' => 110000,
                                    'freightMoney' => 1.00,
                                    'expressCompany' => '京东物流',
                                    'deliverDate' => '发货日期', //发货日期
                                    'expressCode '=> '货运单号')))
     * @return array 暂无有效数据
     *
     */
    public function afterSaleSendSku($params,$jdgoodsKind='normal'){
        $api_function = '填写客户发运信息';
        $method = 'afterSale/updateSendSku';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 3.3	校验某订单中某商品是否可以提交售后服务
     *
     * @param $params = array('param' => array('jdOrderId'=>45794690848,'skuId'=>102818))
     * @return int  (代表可以售后的数量)
     *
     */
    public function afterSaleAvailable($params,$jdgoodsKind='normal'){
        $api_function = '校验某订单中某商品是否可以提交售后服务';
        $method = 'afterSale/getAvailableNumberComp';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 3.4	根据订单号、商品编号查询支持的服务类型
     *
     * @param $params array = array('param' => array('jdOrderId'=>45794690848,'skuId'=>102818))
     * @return array (
                    0 =>
                    array (
                        'code' => '10',
                        'name' => '退货',
                    ),
                    1 =>
                    array (
                        'code' => '20',
                        'name' => '换货',
                    ),
                    2 =>
                    array (
                        'code' => '30',
                        'name' => '维修',
                    ),
                )
     *
     */
    public function afterSaleCustomerExpect($params,$jdgoodsKind='normal'){
        $api_function = '根据订单号、商品编号查询支持的服务类型';
        $method = 'afterSale/getCustomerExpectComp';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 3.5	根据订单号、商品编号查询支持的商品返回京东方式
     *
     * @param $params = array('param' => array('jdOrderId'=>45794690848,'skuId'=>102818))
     * @return array (
                    0 =>
                    array (
                    'code' => '40',
                    'name' => '客户发货',
                    ),
                    1 =>
                    array (
                    'code' => '7',
                    'name' => '客户送货',
                    ),
                    2 =>
                    array (
                    'code' => '4',
                    'name' => '上门取件',
                    ),
                )     *
     */
    public function afterSaleWareReturnJd($params,$jdgoodsKind='normal'){
        $api_function = '根据订单号、商品编号查询支持的商品返回京东方式';
        $method = 'afterSale/getWareReturnJdComp';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 3.6	根据客户账号和订单号分页查询服务单概要信息
     *
     * @param $params  = array('param' =>
     *                          array('jdOrderId'=>45794690848,
     *                                 'pageIndex'=>1,
     *                                  'pageSize' =>10))
     * @return array =array (
                'serviceInfoList' =>
                    array (
                        0 =>
                            array (
                            'afsServiceId' => 262169107,
                            'customerExpect' => 10,//服务类型码 退货(10)、换货(20)、维修(30)
                            'customerExpectName' => '退货',
                            'afsApplyTime' => '2016-11-29 14:56:46',//服务单申请时间
                            'orderId' => 45794690848,
                            'wareId' => 102818,//商品编号
                            'wareName' => '声丽（SENICC）ST-808 头戴式电脑耳机 带线控耳麦 网吧专用 黑色',//商品名称
                            'afsServiceStep' => 10,//服务单环节
                            'afsServiceStepName' => '申请阶段',//服务单环节名称
                            'cancel' => 0,
                    ),
                ),
                'totalNum' => 1, //总记录数
                'pageSize' => 10,//每页记录数
                'pageNum' => 1,//总页数
                'pageIndex' => 1,//当前页数
           )
     */
    public function afterSaleServiceList($params,$jdgoodsKind='normal'){
        $api_function = '根据客户账号和订单号分页查询服务单概要信息';
        $method = 'afterSale/getServiceListPage';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 3.7	根据服务单号查询服务单明细信息
     *
     * @param $params =array('param' =>
                            array('afsServiceId'=>45531789,
                            'appendInfoSteps'=> array(1,2)))
     * @return array (
            'afsServiceId' => 262169107,
            'customerExpect' => 10,
            'afsApplyTime' => '2016-11-29 14:56:46',
            'orderId' => 45794690848,
            'isHasInvoice' => 0,
            'isNeedDetectionReport' => 0,//是不是有检测报告   0没有 1有
            'isHasPackage' => 0, //是不是有包装
            'questionPic' => '',//上传图片访问地址
            'afsServiceStep' => 10,//服务单环节
            'afsServiceStepName' => '申请阶段',
            'approveNotes' => NULL,
            'questionDesc' => '想要的不是这种耳机，要求无理由退货',
            'approvedResult' => NULL,//审核结果 int
            'approvedResultName' => NULL,//审核结果名称
            'processResult' => NULL,//处理结果
            'processResultName' => NULL,//处理结果名称
            'serviceCustomerInfoDTO' =>
                array (
                    'customerPin' => 'shwf2016',
                    'customerName' => '邵君',
                    'customerContactName' => '邵君',
                    'customerTel' => '18951975938',
                    'customerMobilePhone' => '18951975938',
                    'customerEmail' => '990024966@qq.com',
                    'customerPostcode' => NULL,
                ),
            'serviceAftersalesAddressInfoDTO' => NULL,
            'serviceExpressInfoDTO' => NULL,
            'serviceFinanceDetailInfoDTOs' => NULL,
            'serviceTrackInfoDTOs' => NULL,
            'serviceDetailInfoDTOs' =>
                array (
                    0 =>
                    array (
                        'wareId' => 102818,
                        'wareName' => '声丽（SENICC）ST-808 头戴式电脑耳机 带线控耳麦 网吧专用 黑色',
                        'wareBrand' => '硕美科（SOMIC）',
                        'afsDetailType' => 10,
                        'wareDescribe' => NULL,
                    ),
                    1 =>
                    array (
                        'wareId' => 102818,
                        'wareName' => NULL,
                        'wareBrand' => '硕美科（SOMIC）',
                        'afsDetailType' => 30,
                        'wareDescribe' => NULL,
                    ),
                ),
            'allowOperations' => NULL,
        ),
     */
    public function afterSaleServiceDetail($params,$jdgoodsKind='normal'){
        $api_function = '根据服务单号查询服务单明细信息';
        $method = 'afterSale/getServiceDetailInfo';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 3.8	取消服务单/客户放弃
     *
     * @param $params array('param' =>
                            array('serviceIdList' => array(262169107),
                                    'approveNotes'=> '测试工作的需要'))
     * @return boolean
     *  true: 已经成功取消或放弃
     * false：
     */
    public function afterSaleAuditCancel($params,$jdgoodsKind='normal'){
        $api_function = '取消服务单';
        $method = 'afterSale/auditCancel';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }


}