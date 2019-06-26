<?php


$db['cards_exchangelog_items']=array (
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
	'card_id' =>
    array (
      'type' => 'table:cards',
	  'label'=>'卡券名称',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
      'editable' => false,
      'in_list' => true,
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
    ),
	'card_pass' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('卡密'),
	  'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'filtercustom' =>
      array (
        'has' => '包含',
        'tequal' => '等于',
        'head' => '开头等于',
        'foot' => '结尾等于',
      ),
    ),
	'card_no' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('卡券编号'),
      'in_list' => true,
      'default_in_list' => true,
	  'searchtype' => 'has',
      'editable' => true,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'filtercustom' =>
      array (
        'has' => '包含',
        'tequal' => '等于',
        'head' => '开头等于',
        'foot' => '结尾等于',
      ),
    ),
	'status' =>
    array (
      'type' =>
		array(
			'0'=>'失败',
			'1'=>'成功',
			'2'=>'已结算',
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
