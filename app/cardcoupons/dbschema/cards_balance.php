<?php


$db['cards_balance']=array (
  'columns' =>
  array (
    'id' =>
    array (
      'type' => 'bigint unsigned',
      'extra' => 'auto_increment',
      'pkey' => true,
      'required' => true,
      'editable' => false,
    ),
	'settlement_no' => array(
            'type' => 'varchar(15)',
            'required' => true,
            'label' => app::get('cardcoupons')->_('结算报表单号'),
            'width' => 140,
            'editable' => false,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
            ),
	'card_id' =>
    array (
      'type' => 'table:cards',
	  'label'=>'卡券名称',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
      'editable' => false,
      'in_list' => true,
	  'default_in_list' => true,
	  'order' => 10,
    ),
	'num' => 
    array (
      'type' => 'number',
      'label' => '卡券数量',
      'editable' => false,
      'filtertype' => 'number',
      'filterdefault' => true,
      'in_list' => true,
	  'default_in_list' => true,
	  'order' => 30,
    ),
	'create_id' => 
    array (
      'type' => 'number',
      'label' => app::get('cardcoupons')->_('创建人ID'),
      'width' => 100,
      'editable' => false,
      'filtertype' => 'normal',
    ),
	'status' =>
    array (
      'type' =>
		array(
			'0'=>'待结算',
			'1'=>'已结算',
		),
	  'filtertype' => 'yes',
	  'default'=>'0',
      'filterdefault' => true,
      'required' => true,
      'label' => app::get('cardcoupons')->_('状态'),
      'editable' => true,
	  'in_list' => true,
    ),
    'create_name' => 
    array (
      'type' => 'varchar(100)',
      'label' => app::get('cardcoupons')->_('创建人名称'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
	'create_time' =>
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('结算时间'),
      'editable' => false,
      'in_list' => true,
	  'orderby' =>true,
	  'default_in_list' => true,
	  'order' => 40,
    ),
  'amount_money' => array(
		'type' => 'money',
		'label' => app::get('cardcoupons')->_('销售总计'),
		'default' => '0',
		'required' => true,
		'editable' => false,
		'in_list' => true,
		'default_in_list' => true,
		'order' => 50,
		),
	'cost_money' => array(
		'type' => 'money',
		'label' => app::get('cardcoupons')->_('结算总计'),
		'default' => '0',
		'required' => true,
		'editable' => false,
		'in_list' => true,
		'default_in_list' => true,
		'order' => 60,
		),
	'remarks1' => 
    array (
      'type' => 'text',
	  'label' => app::get('cardcoupons')->_('创建备注'),
      'editable' => false,
    ),
   ),
  'engine' => 'innodb',
  'version' => '$Rev: 43884 $',
);
