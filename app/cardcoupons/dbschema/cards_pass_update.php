<?php


$db['cards_pass_update']=array (
  'columns' =>
  array (
    'id' =>
    array (
      'type' => 'decimal(20,0)',
      'extra' => 'auto_increment',
      'pkey' => true,
      'label' => 'ID',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
	  'filtertype' => 'number',
      'filterdefault' => true,
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
	'type' =>
    array (
      'type' =>
		array(
			'1'=>'有效期申请',
            '2'=>'有效性申请',
            '3'=>'解冻冻结申请',
		),
	  'filtertype' => 'yes',
	  'default'=>'1',
      'filterdefault' => true,
      'required' => true,
      'label' => app::get('cardcoupons')->_('申请修改类型'),
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
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
	'card_pass_ids' =>
    array (
      'type' => 'text',
	  'label'=>'关联卡密',
      'required' => true,
      'editable' => false,
    ),
	'from_time' =>
    array (
      'type' =>'time',
	  'filtertype' => 'time',
      'label' => app::get('cardcoupons')->_('开始时间'),
      'editable' => true,
      'in_list' => true,
	  'filterdefault' => true,
    ),
	'to_time' =>
    array (
      'type' =>'time',
	  'filtertype' => 'time',
      'label' => app::get('cardcoupons')->_('结束时间'),
      'editable' => true,
      'in_list' => true,
	  'filterdefault' => true,
    ),
	'disabled' =>
    array (
      'type' =>
		array(
			'false'=>'启用',
			'true'=>'失效',
		),
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'label' => app::get('cardcoupons')->_('有效性'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'status' =>
    array (
      'type' =>
		array(
			'1'=>'解冻',
			'5'=>'冻结',
		),
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'label' => app::get('cardcoupons')->_('冻结状态'),
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
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
