<?php
/**
 * Created by PhpStorm.
 * User: shaojun
 * Date: 2016/12/5
 * Time: 15:22
 */
 $db['jdorders']=array (
     'columns' =>
         array (
             'order_id' =>
                 array (
                     'type' => 'table:orders@b2c',
                     'required' => true,
                     'pkey' => true,
                     'label' => '订单号',
                     'default' => 0,
                     'editable' => false,
                     'filtertype' => 'custom',
                     'filterdefault' => true,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'jdorders_id' =>
                 array (
                     'type' => 'bigint unsigned',
                     'required' => true,
                     'default' => 0,
                     'label' => '京东订单号',
                     'is_title' => true,
                     'width' => 110,
                     'searchtype' => 'has',
                     'editable' => false,
                     'filtertype' => 'custom',
                     'filterdefault' => true,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'createtime' =>
                array(
                    'type' => 'time',
                    'label' => '下单时间',
                    'width' => 110,
                    'editable' => false,
                    'filtertype' => 'time',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'orderby' => true,
                ),
             'jdstatus' =>
                 array (
                     'type' =>
                         array (
                             'npaid' => '未支付京东',
                             'ypaid' => '已支付京东',
                             'close' => '订单已关闭',
                         ),
                     'default' => 'npaid',
                     'required' => true,
                     'label' => '京东支付状态',
                     'width' => 75,
                     'editable' => false,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'paidtime' =>
                 array(
                     'type' => 'time',
                     'label' => '支付时间',
                     'width' => 110,
                     'editable' => false,
                     'filtertype' => 'time',
                     'filterdefault' => true,
                     'in_list' => true,
                     'default_in_list' => true,
                     'orderby' => true,
                 ),
             'jd_order_price' =>
                 array (
                     'type' => 'money',
                     'required' => true,
                     'label' => app::get('aftersales')->_('订单协议价格'),
                     'default' => 0,
                     'width' => 100,
                     'editable' => false,
                     'default_in_list'=>true,
                     'in_list' => true,
                 ),
             'order_state' =>
                 array (
                     'type' =>
                         array (
                             '9' => '订单审核中',
                             '0' => '新建',
                             '1' => '妥投',
                             '2' => '拒收',
                             '8' => '订单已关闭',
                         ),
                     'default' => '9',
                     'required' => true,
                     'label' => '订单状态',
                     'width' => 75,
                     'editable' => false,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'check_status' =>
                 array (
                     'type' =>
                         array (
                             '0' => '未对账',
                             '1' => '已对账',
                             '2' => '异常',
                         ),
                     'default' => '0',
                     'label' => '对账状态',
                     'width' => 100,
                     'editable' => false,
                     'in_list' => true,
                 ),
             'order_kind' =>
                 array (
                     'type' =>
                         array (
                             'entity' => '虚拟物品订单',
                             'virtual' => '实体物品订单',
                             'b2c_card' => '卡券销售订单',
                             'card' => '卡券兑换订单',
                             'jdbook'   => '京东图书订单',
                             'jdorder'   => '京东特卖订单',
                         ),
                     'searchtype' => 'has',
                     'default' => 'jdorder',
                     'required' => true,
                     'label' => '订单类型',
                     'width' => 75,
                     'editable' => false,
                     'filtertype' => 'yes',
                     'filterdefault' => true,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'check_info' =>
                 array (
                     'type' =>'varchar(200)',
                     'label' => '异常信息',
                     'width' => 200,
                     'editable' => false,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'check_time' =>
                 array (
                     'type' =>'time',
                     'label' => '对账时间',
                     'width' => 110,
                     'editable' => false,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
         ),
     'engine' => 'innodb',
     'version' => '$Rev: 42376 $',
 );