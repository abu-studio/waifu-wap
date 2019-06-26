<?php

/**
 * 操作订单信息的api接口方法
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/22
 * Time: 18:56
 */
class jdsale_api_orders extends jdsale_api_base {
    
    public function __construct($app){
        parent::__construct($app);
    }

    /**
     * 2.1	统一下单接口
     * @param $param = array(
            //我方订单号
            'thirdOrder'=>'2016112222787994',
            //京东sku
            'sku'=>array(array('skuId'=>100077,'num'=>1)),
            //收货人
            'name'=>'邵君',
            //江苏省
            'province'=>'12',
            //南京市
            'city'=>'904',
            //雨花台区
            'county'=>'3379',
            // 0 任何
            'town'=>'0',
            //用户填写详细地址
            'address'=>'郁金香路30号(吉美思大厦703室)',
            //邮编----非必填写
            'zip'=>'211312',
            //座机号----非必填写
            'phone'=>'',
            //手机号
            'mobile'=>'18951975938',
            //邮箱
            'email'=>'990024966@qq.com',
            //备注
            'remark'=>'',
            //开票方式(1为随货开票，0为订单预借，2为集中开票 )
            'invoiceState'=>'2',
            //1普通发票 2增值税发票
            'invoiceType'=>'1',
            //4个人，5单位
            'selectedInvoiceTitle'=>'4',
            //发票抬头  (如果selectedInvoiceTitle=5则此字段Y)
            'companyName'=>'',
            //1:明细，3：电脑配件，19:耗材，22：办公用品
            //备注:若增值发票则只能选1 明细
            'invoiceContent'=>'1',
            //1：货到付款，2：邮局付款，4：在线支付（余额支付），5：公司转账，6：银行转账，7：网银钱包， 101：金采支付
            'paymentType'=>'5',
            //预存款【即在线支付（余额支付）】下单固定1 使用余额
            //非预存款下单固定0 不使用余额
            'isUseBalance'=>'0',
            //是否预占库存，0是预占库存（需要调用确认订单接口），1是不预占库存
            'submitState'=>'0',
            //增值票收票人姓名
            //备注：当invoiceType=2 且invoiceState=1时则此字段必填
            'invoiceName'=>'',
            //增值票收票人电话
            //备注：当invoiceType=2且invoiceState=1 时则此字段必填
            'invoicePhone'=>'',
            //增值票收票人所在省(京东地址编码)
            //备注：当invoiceType=2且invoiceState=1 时则此字段必填
            'invoiceProvice'=>'',
            //增值票收票人所在市(京东地址编码)
            //备注：当invoiceType=2 且invoiceState=1时则此字段必填
            'invoiceCity'=>'',
            //增值票收票人所在区/县(京东地址编码)
            //备注：当invoiceType=2 且invoiceState=1时则此字段必填
            'invoiceCounty'=>'',
            //增值票收票人所在地址
            //备注：当invoiceType=2 且invoiceState=1时则此字段必填
            'invoiceAddress'=>'',
            //备注：当invoiceType=2 且invoiceState=1时则此字段必填
            //大家电配送日期
            //默认值为-1，0表示当天，1表示明天，2：表示后天; 如果为-1表示不使用大家电预约日历
            'reservingDate'=>'-1',
            //该字段我们这边不需要
            'needInstall'=>'',
            //该字段我们这边不需要
            'promiseDate'=>'',
            //该字段我们这边不需要
            'promiseTimeRange'=>'',
            //该字段我们这边不需要
            'promiseTimeRangeCode'=>'',
            //该字段我们这边不需要
            'doOrderPriceMode'=>'',
            //该字段我们这边不需要
            'orderPriceSnap'=>'',
            //该字段我们这边不需要
            'extContent'=>'',
        );
     * @return array = Array
                        (
                            [biz_order_unite_submit_response] => Array
                                                            (
                                                                [success] => 1
                                                                [resultMessage] => 下单成功！
                                                                [resultCode] => 0001
                                                                [result] => Array
                                                                        (
                                                                            [jdOrderId] => 45531762489
                                                                            [freight] => 6
                                                                            [orderPrice] => 14.75
                                                                            [orderNakedPrice] => 12.61
                                                                            [sku] => Array
                                                                                    (
                                                                                        [0] => Array
                                                                                            (
                                                                                                [skuId] => 100077
                                                                                                [num] => 1
                                                                                                [category] => 2603
                                                                                                [price] => 14.75
                                                                                                [name] => 施德楼（Staedtler）M317-9 黑色 油性记号笔 光盘笔 1.0mm 单只装
                                                                                                [tax] => 17
                                                                                                [taxPrice] => 2.14
                                                                                                [nakedPrice] => 12.61
                                                                                                [type] => 0
                                                                                                [oid] => 0
                                                                                            )

                                                                                    )

                                                                            [orderTaxPrice] => 2.14
                                                                        )
                                                                [code] => 0
                        )
            )
     */
    public function getOrderSubmit($params,$jdgoodsKind='normal'){
        $api_function = '统一下单接口';
        $method = 'order/submitOrder';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 2.2.1	确认预占库存订单接口
     * 根据京东订单号，确认预占库存，统一下单接口中的输入参数中，字段submitState=0时，为预占库存订单，
     * 预占库存订单需要调用“确认预占库存订单接口”接口对订单进行确认，确认后的订单才能生效。
     * @param $params array = array('jdOrderId'=>45794690848)
     * @return boolean
     *   true or false
     */
    public function confirmOccupyStock($params,$jdgoodsKind='normal'){
        $api_function = '确认预占库存订单接口';
        $method = 'order/confirmOrder';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }
    /**
     * 2.2.2	发起支付接口
     * @param $params = array('jdOrderId'=>'2302507708');
     * @return 如果success 为true则代表发起支付成功/success 为false，则代表因为某种原因发起支付失败了

     */
    public function getOrderDoPay($params,$jdgoodsKind='normal'){
        $api_function = '发起支付接口';
        $method = 'order/doPay';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 2.2.3	取消未确认订单接口
     * @param $params array('jdOrderId'=>'45531762489');
     * @return array = Array (
     *                      [biz_order_cancelorder_response] => Array (
     *                          [success] => 1
     *                          [resultMessage] => 取消订单成功
     *                          [resultCode] => 0002
     *                          [result] => 1
     *                          [code] => 0
     *                      )
     *                  )
     *
     * 若是不预占库存的订单将直接生产，不能取消
     * array (
            'biz_order_cancelorder_response' =>
            array (
                'success' => false,
                'resultMessage' => '该订单已经生产，不能取消订单！',
                'resultCode' => '3204',
                'result' => false,
                'code' => '0',
            ),
        )
     */
    public function getCancelOrder($params,$jdgoodsKind='normal'){
        //api调用频率限制...
        $jdFrequency = ROOT_DIR . '/data/logs/jd_frequency.txt';
        if(file_exists($jdFrequency)){
            $modifyTime = filemtime($jdFrequency);
            if(time()-(10 * 60) < $modifyTime){
                return array('result' => 0);
            }
        }


        $api_function = '取消未确认订单接口';
        $method = 'order/cancel';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        if('2010' == $result['resultCode']){
            touch($jdFrequency);
            @chgrp($jdFrequency,'www');
            @chown($jdFrequency,'www');
        }else if('3203' == $result['resultCode']){
            $result['result'] = 1;
        }

        return $result;
    }

    /**
     * 2.2.4	查询京东订单信息接口
     * @param $params = array('jdOrderId'=>'45531762489');
     * @return array Array(
            [biz_order_jdOrder_query_response] => Array(
                    [success] => 1
                    [resultMessage] =>
                    [resultCode] => 0000
                    [result] => Array(
                        [pOrder] => 0
                        [orderState] => 0 //订单状态  0为取消订单  1为有效
                        [jdOrderId] => 45531762489
                        [state] => 0
                        [freight] => 6
                        [submitState] => 0 //0为未确认下单订单   1为确认下单订单
                        [orderPrice] => 14.75
                        [orderNakedPrice] => 12.61
                        [sku] => Array(
                            [0] => Array(
                                [skuId] => 100077
                                [num] => 1
                                [category] => 2603
                                [price] => 14.75
                                [name] => 施德楼（Staedtler）M317-9 黑色 油性记号笔 光盘笔 1.0mm 单只装
                                [tax] => 17
                                [taxPrice] => 2.14
                                [nakedPrice] => 12.61
                                [type] => 0
                                [oid] => 0
                            )
                        )
                        [type] => 2 //订单类型   1是父订单   2是子订单
                        [orderTaxPrice] => 2.14
                    )
                [code] => 0
            )
        )
     */
    public function getOrderJdOrder($params,$jdgoodsKind='normal'){
        $api_function = '查询京东订单信息接口';
        $method = 'order/selectJdOrder';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 2.2.5	订单反查接口
     * @param $params = array('thirdOrder'=>'2016112822787991');
     * @return $string  '45739274803'
     *
     * array (
    'success' => true,
    'resultMessage' => '',
    'resultCode' => '0000',
    'result' => '45739274803',
    'code' => '0',
    ),
     */
    public function getOrderJdOrderIDByThridOrderID($params,$jdgoodsKind='normal'){
        $api_function = '订单反查接口';
        $method = 'order/selectJdOrderIdByThirdOrder';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 2.2.6	询配送信息接口
     * @param $params = array('jdOrderId'=>'45794690848')
     *
     * @return array =array (
        'orderTrack' =>
            array (
                0 =>
                array (
                    'content' => '您提交了订单，请等待系统确认',
                    'msgTime' => '2016-11-28 17:07:19',
                    'operator' => '客户',
                ),
                1 =>
                array (
                    'content' => '您的订单已经进入京东2号库准备出库',
                    'msgTime' => '2016-11-28 17:12:38',
                    'operator' => '系统',
                ),
                2 =>
                array (
                    'content' => '您的订单预计11月29日送达您手中',
                    'msgTime' => '2016-11-28 17:17:20',
                    'operator' => '系统',
                ),
                3 =>
                array (
                    'content' => '您的订单已经打印完毕',
                    'msgTime' => '2016-11-28 17:43:25',
                    'operator' => '系统',
                ),
                4 =>
                array (
                    'content' => '您的订单已经拣货完成',
                    'msgTime' => '2016-11-28 18:21:18',
                    'operator' => '耿文菊',
                ),
                5 =>
                array (
                    'content' => '扫描员已经扫描',
                    'msgTime' => '2016-11-28 18:57:48',
                    'operator' => '韩冬梅',
                ),
                6 =>
                array (
                    'content' => '打包成功',
                    'msgTime' => '2016-11-28 19:00:05',
                    'operator' => '京东打包员',
                ),
                7 =>
                array (
                    'content' => '您的订单在京东【南京分拨中心】分拣完成',
                    'msgTime' => '2016-11-28 19:17:22',
                    'operator' => '刘娟',
                ),
                8 =>
                array (
                    'content' => '您的订单在京东【南京分拨中心】发货完成，准备送往京东【南京玉兰路超级站】',
                    'msgTime' => '2016-11-28 19:17:52',
                    'operator' => '刘娟',
                ),
                9 =>
                array (
                    'content' => '您的订单在京东【南京玉兰路超级站】验货完成，正在分配配送员',
                    'msgTime' => '2016-11-29 07:37:47',
                    'operator' => '陆京遇',
                ),
                10 =>
                array (
                    'content' => '京东配送员【李宁】已出发，联系电话【18502587221，感谢您的耐心等待，参加评价还能赢取京豆呦】',
                    'msgTime' => '2016-11-29 08:13:16',
                    'operator' => '李宁',
                ),
            ),
        'jdOrderId' => 45794690848,
        ),
     */
    public function getOrderTrack($params,$jdgoodsKind='normal'){
        $api_function = '查询配送信息接口';
        $method = 'order/orderTrack';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }




}