<?php


$db['cards_balance_items']=array (
  'columns' =>
  array (
    'id' =>
    array (
      'type' => 'bigint unsigned',
      'extra' => 'auto_increment',
      'pkey' => true,
      'label' => 'ID',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'settlement_no' =>
	array(
            'type' => 'varchar(15)',
            'required' => true,
            'editable' => false,
    ),
	'card_name' =>
    array (
	  'type' => 'varchar(50)',
	  'label'=>'卡券名称',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
      'editable' => false,
      'in_list' => true,
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
	'card_pass' =>
    array (
      'type' => 'varchar(256)',
      'required' => true,
    ),
	'card_no' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
    ),
	'create_id' => 
    array (
      'type' => 'number',
      'label' => app::get('cardcoupons')->_('创建人ID'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
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
      'in_list' => true,
    ),
	'time' =>
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('创建时间'),
      'editable' => false,
      'in_list' => true,
	  'orderby' =>true,
    ),
	'item_money' => array(
		'type' => 'money',
		'label' => app::get('cardcoupons')->_('销售金额'),
		'default' => '0',
		'required' => true,
		'editable' => false,
		'in_list' => true,
		'default_in_list' => true,
		),
	'cost_money' => array(
		'type' => 'money',
		'label' => app::get('cardcoupons')->_('成本金额'),
		'default' => '0',
		'required' => true,
		'editable' => false,
		'in_list' => true,
		'default_in_list' => true,
		),
	'memo' => 
    array (
      'type' => 'text',
	  'label' => app::get('cardcoupons')->_('备注'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
   ),
  'engine' => 'innodb',
  'version' => '$Rev: 43884 $',
);
