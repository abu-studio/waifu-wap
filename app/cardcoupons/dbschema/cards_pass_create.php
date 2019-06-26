<?php


$db['cards_pass_create']=array (
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
	'source' =>
    array (
      'type' =>
		array(
			'internal'=>'内部卡',
			'external'=>'外部卡',
		),
		'default'=>'internal',
		'required' => true,
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
	'start_card_no' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('起始卡券号'),
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
	'end_card_no' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('截至卡券号'),
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
	'num' => 
    array (
      'type' => 'number',
      'label' => '数量',
      'editable' => false,
      'filtertype' => 'number',
      'filterdefault' => true,
      'in_list' => true,
    ),
	'ex_status' =>
    array (
      'type' =>
		array(
			'update'=>'待审核',
			'false'=>'审核失败',
			'true'=>'审核成功',
		),
	  'filtertype' => 'yes',
	  'default'=>'update',
      'filterdefault' => true,
      'required' => true,
      'label' => app::get('cardcoupons')->_('审核状态'),
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
	'create_time' =>
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('创建时间'),
      'editable' => false,
      'in_list' => true,
	  'orderby' =>true,
    ),
	'remarks1' => 
    array (
      'type' => 'text',
	  'label' => app::get('cardcoupons')->_('创建备注'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
	'op_id' => 
    array (
      'type' => 'number',
      'label' => app::get('cardcoupons')->_('审核人ID'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'op_name' => 
    array (
      'type' => 'varchar(100)',
      'label' => app::get('cardcoupons')->_('审核人名称'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'update_time' => 
    array (
      'type' => 'time',
      'label' => app::get('cardcoupons')->_('审核时间'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
	  'orderby' =>true,
    ),
	'remarks2' => 
    array (
      'type' => 'text',
	  'label' => app::get('cardcoupons')->_('审核备注'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
   ),
  'engine' => 'innodb',
  'version' => '$Rev: 43884 $',
);
