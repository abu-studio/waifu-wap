<?php
/**
 * 获取订单对账信息的api接口方法
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/11
 * Time: 13:26
 */

class jdsale_api_checkorder extends jdsale_api_base {

    public function __construct($app) {
        parent::__construct($app);

    }

    /**
     * 2.3.1	新建订单查询接口
     * 根据日期和页码，查询新建订单信息
     * @param $params array (
                    'date' => '2016-11-28',
                    'page' => 1,
                    )
     * @return array array (
                        'total' => 1, //订单总数
                        'curPage' => 1,//当前页码
                        'orders' =>
                        array (
                            0 =>
                            array (
                                //订单时间
                                'time' => '2016-11-28 14:08:19',
                                //开票方式(1为随货开票，0为订单预借，2为集中开票 )
                                'invoiceState' => 2,
                                'jdOrderId' => 45739274803,
                                //订单状态 0 是新建  1是妥投   2是拒收
                                'state' => 0,
                                //是否挂起   0为未挂起    1为挂起
                                'hangUpState' => 0,
                                 //订单价格
                                'orderPrice' => 19,
                            ),
                        ),
                        'totalPage' => 1,//总页码数
                    ),
     *
     *
     */
    public function checkNewOrder($params,$jdgoodsKind='normal'){
        $api_function = '新建订单查询接口';
        $method = 'checkOrder/checkNewOrder';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 2.3.2	查询妥投订单接口
     * @param $params  array('date'=>'2016-11-29','page'=>1)
     * @return array (
                'total' => 1, //订单总数
                'curPage' => 1,//当前页码
                'orders' =>
                    array (
                        0 =>
                        array (
                            //订单时间
                            'time' => '2016-11-28 17:07:19',
                            //开票方式(1为随货开票，0为订单预借，2为集中开票 )
                            'invoiceState' => 2,
                            //京东订单编号
                            'jdOrderId' => 45794690848,
                            //订单状态 0 是新建  1是妥投   2是拒收
                            'state' => 1,
                            //是否挂起   0为未挂起    1为挂起
                            'hangUpState' => 0,
                            //订单价格
                            'orderPrice' => 19,
                        ),
                    ),
                'totalPage' => 1,//总页码数
            ),
     *
     */
    public function checkDlokOrder($params,$jdgoodsKind='normal'){
        $api_function = '查询妥投订单接口';
        $method = 'checkOrder/checkDlokOrder';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 2.3.3	查询拒收订单接口
     * @param $params  array('date'=>'2016-11-29','page'=>1)
     * @return array (
                'total' => 1,//订单总数
                'curPage' => 1,//当前页码
                'orders' =>
                    array (
                        0 =>
                        array (
                            //订单时间
                            'time' => '2016-11-28 17:07:19',
                            //开票方式(1为随货开票，0为订单预借，2为集中开票 )
                            'invoiceState' => 2,
                            //京东订单编号
                            'jdOrderId' => 45794690848,
                            //订单状态 0 是新建  1是妥投   2是拒收
                            'state' => 2,
                            //是否挂起   0为未挂起    1为挂起
                            'hangUpState' => 0,
                            //订单价格
                            'orderPrice' => 19,
                        ),
                    ),
                'totalPage' => 1,//总页码数
            ),
     */
    public function checkRefuseOrder($params,$jdgoodsKind='normal'){
        $api_function = '查询拒收订单接口';
        $method = 'checkOrder/checkRefuseOrder';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

}