<?php

/**
 * 京东售后的申请记录表
 */
$db['afs_log']=array (
  'columns' => 
  array (
    'log_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' => 
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'label' => app::get('jdsale')->_('本地订单号'),
      'default' => 0,
      'width' => 100,
      'editable' => false,
      'default_in_list'=>true,
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
    'jd_order_id' =>
    array (
        'type' => 'bigint unsigned',
        'required' => true,
        'label' => app::get('jdsale')->_('京东订单号'),
        'default' => 0,
        'width' => 100,
        'editable' => false,
        'default_in_list'=>true,
        'in_list' => true,
    ),
    'jd_suborder_id' =>
        array (
            'type' => 'bigint unsigned',
            'label' => app::get('jdsale')->_('京东子订单号'),
            'default' => 0,
            'width' => 100,
            'editable' => false,
            'default_in_list'=>true,
            'in_list' => true,
        ),
    'sku_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'label' => app::get('jdsale')->_('商品编号'),
      'default' => 0,
      'width' => 100,
      'editable' => false,
      'default_in_list'=>true,
      'in_list' => true,
    ),
    'apply_num' =>
    array (
        'type' => 'int unsigned',
        'required' => true,
        'label' => app::get('jdsale')->_('申请数量'),
        'default' => 0,
        'width' => 100,
        'editable' => false,
        'default_in_list'=>true,
        'in_list' => true,
    ),
    'sku_name' =>
    array (
        'type' => 'varchar(200)',
        'required' => true,
        'label' => app::get('jdsale')->_('商品名称'),
        'width' => 300,
        'editable' => false,
        'default_in_list'=>true,
        'in_list' => true,
    ),
    'customerExpect' =>
    array (
        'type' =>
            array (
                10 => '退货',
                20 => '换货',
                30 => '维修',
            ),
        'label' => app::get('jdsale')->_('服务类型'),
        'width' => 100,
        'editable' => false,
        'default_in_list'=>true,
        'in_list' => true,
    ),
    'applyer_id' =>
    array (
      'type' => 'number',
      'label' => app::get('jdsale')->_('申请人ID'),
      'width' => 100,
      'editable' => false,
      'filtertype' => 'normal',
      'default_in_list'=>true,
      'in_list' => true,
    ),
    'applyer_name' =>
    array (
      'type' => 'varchar(100)',
      'label' => app::get('jdsale')->_('申请人'),
      'width' => 100,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'default_in_list'=>true,
      'in_list' => true,
    ),
    'apply_time' =>
    array (
      'type' => 'time',
      'label' => app::get('jdsale')->_('申请时间'),
      'width' => 100,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'default_in_list'=>true,
      'in_list' => true,
    ),
    'result' =>
        array (
            'type' =>
                array (
                    'SUCCESS' => app::get('jdsale')->_('成功'),
                    'FAILURE' => app::get('jdsale')->_('失败'),
                ),
            'required' => true,
            'label' => app::get('jdsale')->_('申请结果'),
            'width' => 100,
            'editable' => false,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'default_in_list'=>true,
            'in_list' => true,
        ),
    'refund_status' =>
        array (
            'type' =>
                array (
                    0 => '未申请退款',
                    1 => '退款申请中,等待京东审核',
                    2 => '京东已退款',
                ),
            'required' => true,
            'default' => '0',
            'label' => app::get('jdsale')->_('退款状态'),
            'width' => 200,
            'editable' => false,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'default_in_list'=>true,
            'in_list' => true,
        ),
    'return_id' =>
        array (
            'type' => 'bigint(20)',
            'label' => app::get('jdsale')->_('退货记录流水号'),
            'width' => 100,
            'editable' => false,
            'filtertype' => 'normal',
            'in_list' => true,
        ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 46974 $',
);
