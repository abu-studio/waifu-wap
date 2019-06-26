<?php

/**
 * 京东账户的余额明细记录
 */
$db['balance']=array (
  'columns' => 
  array (
    'balance_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'trade_no' =>
    array (
        'type' => 'bigint unsigned',
        'label' => app::get('aftersales')->_('交易流水号'),
        'required' => true,
        'width' => 100,
        'editable' => false,
		'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
        'default_in_list'=>true,
        'in_list' => true,
    ),
    'order_id' => 
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'label' => app::get('aftersales')->_('京东订单号'),
      'default' => 0,
      'width' => 100,
      'editable' => false,
	  'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
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
    'trade_type' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'label' => app::get('aftersales')->_('交易类型'),
      'default' => 0,
      'width' => 100,
	  'searchtype' => 'has',
	  'filtertype' => 'normal',
      'filterdefault' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'trade_type_name' =>
    array (
        'type' => 'varchar(200)',
        'required' => true,
        'label' => app::get('aftersales')->_('交易类型名称'),
        'width' => 200,
		'searchtype' => 'has',
		'filtertype' => 'has',
		'filterdefault' => true,
        'editable' => false,
        'in_list' => true,
    ),
    'amount' =>
    array (
        'type' => 'money',
        'required' => true,
        'label' => app::get('aftersales')->_('金额'),
        'default' => 0,
        'width' => 100,
        'editable' => false,
        'default_in_list'=>true,
        'in_list' => true,
    ),
    'note_pub' =>
    array (
      'type' => 'longtext',
      'label' => app::get('aftersales')->_('交易明细'),
      'width' => 300,
      'editable' => false,
      'default_in_list'=>true,
      'in_list' => true,
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'label' => app::get('aftersales')->_('交易时间'),
      'width' => 150,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'default_in_list'=>true,
      'in_list' => true,
    ),
  ),
  'index' => 
  array (
    'ind_trade_no' => 
    array (
      'columns' => 
      array (
        0 => 'trade_no',
      ),
    ),
	'ind_order_id' => 
    array (
      'columns' => 
      array (
        0 => 'order_id',
      ),
    ),
	'ind_trade_type' => 
    array (
      'columns' => 
      array (
        0 => 'trade_type',
      ),
    ),
  ),

  'engine' => 'innodb',
  'version' => '$Rev: 46974 $',
);
