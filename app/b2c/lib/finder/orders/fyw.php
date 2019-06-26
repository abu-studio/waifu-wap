<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/14
 * Time: 20:27
 */
class b2c_finder_orders_fyw{
    var $detail_basic = '商品';
    var $column_fyw_fee_amount = '手续费';
    var $column_order_real_amount = '订单实际支付金额';
    var $column_refund_amount = '退款金额';
    var $column_refund_num = '退款数量';

    function __construct($app){
        $this->app = $app;
    }

    //基本信息
    function detail_basic($order_id){
        $render = $this->app->render();
        $mdl_order_fyw_items = app::get('b2c')->model('order_fyw_items');
        $data_tmp =$mdl_order_fyw_items->getList('*',array('order_id'=>$order_id));

        $render->pagedata['goodsItems'] = $data_tmp;
        return $render->fetch('admin/order/fyw/items.html');
    }

    function column_fyw_fee_amount($row){
        $fyw_fee_amount = $row['final_amount']-$row['totalPointAmt'];
        $arr_cur = '￥'.$fyw_fee_amount;
        return $arr_cur;
    }

    function column_order_real_amount($row){
        $refundList = $this->hasRefund($row);
        if (empty($refundList)){
            return;
        }
        $amount = 0;
        foreach($refundList as $k=>$v){
            $amount +=$v['final_amount'];
        }
        $real_amount = $row['final_amount']-$amount;
        return '￥'.$real_amount;
    }

    function column_refund_amount($row){
        $refundList = $this->hasRefund($row);
        if (empty($refundList)){
            return ;
        }
        $amount = 0;
        foreach($refundList as $k=>$v){
            $amount +=$v['final_amount'];
        }
        return '￥'.$amount;
    }

    function column_refund_num($row){
        $refundList = $this->hasRefund($row);
        if (empty($refundList)){
            return ;
        }
        $num = 0;
        $mdl_order_fyw_items = app::get('b2c')->model('order_fyw_items');
        foreach($refundList as $k=>$v){
            $fyw_items = $mdl_order_fyw_items->getList('goodsNum',array('order_id'=>$v['order_id']));
            foreach($fyw_items as $k2=>$v2){
                $num +=$v2['goodsNum'];
            }
        }
        return $num;
    }

    private function hasRefund($row){
        if($row['order_id']){
            $mdl_order = $this->app->model('orders_fyw');
            $refund_filter = array('oriTradeNo'=>$row['order_id'],
                                   'tradeStatus'=>'SUCC');
            $refundList = $mdl_order->getList('*',$refund_filter);;

            return $refundList;
        }else{
            return array();
        }

    }
}