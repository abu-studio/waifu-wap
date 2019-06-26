<?php
/**
 * Created by PhpStorm.
 * User: yangjun
 * Date: 2017/1/3
 * Time: 15:22
 */
 $db['jd_suborders']=array (
     'columns' =>
         array (
             'order_id' =>
                 array (
                     'type' => 'table:orders@b2c',
                     'required' => true,
                     'pkey' => true,
                     'label' => app::get('jdsale')->_('订单号'),
                     'default' => 0,
                     'editable' => false,
                     'filtertype' => 'custom',
                     'filterdefault' => true,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'jd_order_id' =>
                 array (
                     'type' => 'bigint unsigned',
                     'required' => true,
                     'default' => 0,
                     'label' => app::get('jdsale')->_('京东父订单号'),
                     'is_title' => true,
                     'width' => 110,
                     'searchtype' => 'has',
                     'editable' => false,
                     'filtertype' => 'custom',
                     'filterdefault' => true,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'jd_suborder_id' =>
                 array (
                     'type' => 'bigint unsigned',
                     'required' => true,
                     'pkey' => true,
                     'default' => 0,
                     'label' => app::get('jdsale')->_('京东子订单号'),
                     'is_title' => true,
                     'width' => 110,
                     'searchtype' => 'has',
                     'editable' => false,
                     'filtertype' => 'custom',
                     'filterdefault' => true,
                     'in_list' => true,
                     'default_in_list' => true,
                 ),
             'sku_id' =>
                 array (
                     'type' => 'bigint unsigned',
                     'required' => true,
                     'pkey' => true,
                     'label' => app::get('jdsale')->_('商品编号'),
                     'default' => 0,
                     'width' => 100,
                     'editable' => false,
                     'default_in_list'=>true,
                     'in_list' => true,
                 ),
             'sku_num' =>
                 array (
                     'type' => 'int unsigned',
                     'required' => true,
                     'label' => app::get('jdsale')->_('商品数量'),
                     'default' => 0,
                     'width' => 100,
                     'editable' => false,
                     'default_in_list'=>true,
                     'in_list' => true,
                 ),
             'createtime' =>
                array(
                    'type' => 'time',
                    'label' => app::get('jdsale')->_('生成时间'),
                    'width' => 110,
                    'editable' => false,
                    'filtertype' => 'time',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'orderby' => true,
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
         ),
     'engine' => 'innodb',
     'version' => '$Rev: 42376 $',
 );