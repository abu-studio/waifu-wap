<?php
$db['order_status_cycle']=array (
  'columns' => 
  array (
  'payment_id' =>
      array (
          'type' => 'varchar(20)',
          'pkey' => true,
          'required' => true,
          'label' => '支付单号',
          'is_title' => true,
          'width' => 110,
          'searchtype' => 'has',
          'editable' => false,
          'filtertype' => 'custom',
          'filterdefault' => true,
          'in_list' => true,
          'default_in_list' => true,
      ),
    'order_id' =>
    array (
        'type' => 'bigint unsigned',
        'required' => true,
        'label' => '订单号',
        'is_title' => true,
        'width' => 110,
        'searchtype' => 'has',
        'editable' => false,
        'filtertype' => 'custom',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
    ),
    't_begin_pay' =>
      array (
          'type' => 'time',
          'label' => '支付开始时间',
          'width' => 110,
          'editable' => false,
          'filtertype' => 'time',
          'filterdefault' => true,
          'in_list' => true,
      ),
	'cycle_status' =>
    array (
      'type' => 
      array (
        0 => '未处理',
        1 => '处理中',
		2 => '处理完成',
      ),
      'comment' => '是否轮循处理了',
      'editable' => false,
      'default' => '0',
      'required' => true,
    ),

  ),
  'comment' => app::get('ectools')->_('订单后台轮循处理状态'),
  'index' => 
  array (
    'ind_status' =>
    array (
      'columns' => 
      array (
        0 => 'cycle_status',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 43384 $',
);
