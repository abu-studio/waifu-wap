<?php
/**
 * 获取账户余额信息的api接口方法
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/11
 * Time: 13:26
 */

class jdsale_api_account extends jdsale_api_base {

    public function __construct($app) {
        parent::__construct($app);

    }

    /**
     * 2.4.1	统一余额查询API接口
     *
     * @params array('payType'=>4)
     * payType支付类型 4：余额 7：网银钱包 101：金采支付
     * @return string 余额值
         *Array
         (
            [biz_price_balance_get_response] => Array
            (
                [success] => 1
                [resultMessage] =>
                [resultCode] => 0000
                [result] => 10000.0000
                [code] => 0
            )
        )
     */
    public function queryBalance($params,$jdgoodsKind='normal'){
        $api_function = '统一余额查询API接口';
        $method = 'price/getBalance';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 2.4.2	查询用户金采余额接口明细 ---不是金采用户
     *
     * @return array
     * Array
        (
        [biz_price_jincaiCredit_query_response] => Array
        (
        [success] =>
        [resultMessage] => 没找到此用户
        [resultCode] => 3407
        [result] =>
        [code] => 0
        )

        )
     *
     */
    public function queryJincaiCredit($jdgoodsKind='normal'){
        $api_function = '查询用户金采余额接口明细';
        $method = 'price/selectJincaiCredit';
        $params = null;
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }

    /**
     * 2.4.3	余额明细查询接口
     *
     * @params 可为null，可以为array？？
     *
     * @return array
     * Array
    (
        [total] => 1
        [pageSize] => 20
        [pageNo] => 1
        [pageCount] => 1
        [data] => Array
            (
            [0] => Array
                (
                [id] => 1064393476
                [accountType] => 1
                [amount] => 10000
                [pin] => shwf2016
                [orderId] => 561975
                [tradeType] => 457
                [tradeTypeName] => 备查款账户返余
                [createdDate] => 2016-11-18 13:38:00
                [notePub] => 定金返款：客户ID：shwf2016, {usableAmount=0.00, remark=2016年11月15日北京招行0206收到上海外服商务管理有限公司10000元,已处理10000.00元,剩余金额0.00元,2016.11.18}接认领人姓名：曹雨 通知做余.
                [tradeNo] => 2130020867
                )
             )
    )
     *
     */
    public function queryBalancedetail($params=null,$jdgoodsKind='normal'){
        $api_function = '余额明细查询接口';
        $method = 'price/getBalanceDetail';
        $result = $this->getBizData($method,$api_function,$params,$jdgoodsKind);
        return $result;
    }


}