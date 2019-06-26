<?php


$db['cards_exchangelog']=array (
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
	 'batch' =>
    array (
      'type' => 'varchar(50)',
	  'label'=>'批次',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
      'editable' => false,
      'in_list' => true,
	  'searchtype' => 'has',
	  'default_in_list' => true,
	  'order' => 20,
    ),
	'num' => 
    array (
      'type' => 'number',
      'label' => '数量',
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
      'in_list' => true,
    ),
	'status' =>
    array (
      'type' =>
		array(
			'0'=>'未生成结算报表',
			'1'=>'已生成结算报表',
		),
	  'filtertype' => 'yes',
	  'default'=>'0',
      'filterdefault' => true,
      'required' => true,
      'label' => app::get('cardcoupons')->_('状态'),
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
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
	'create_time' =>
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('创建时间'),
      'editable' => false,
      'in_list' => true,
	  'orderby' =>true,
	  'default_in_list' => true,
	  'order' => 40,
    ),
	'remarks1' => 
    array (
      'type' => 'text',
	  'label' => app::get('cardcoupons')->_('创建备注'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
   ),
  'engine' => 'innodb',
  'version' => '$Rev: 43884 $',
);
