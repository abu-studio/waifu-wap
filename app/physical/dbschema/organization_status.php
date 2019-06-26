<?php 
$db['organization_status']=array (
	'columns' =>
	array (
		'id' =>
		array (
			'type' => 'int(11)',
			'required' => true,
			'pkey' => true,
			'extra' => 'auto_increment',
			'label' => app::get('physical')->_('ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => false,
			'default_in_list' => false,
		),
		'organization_id' =>
		array (
			'type' => 'table:organization',
			'required' => true,
			'label' => app::get('physical')->_('机构'),
			'width' => 300,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>10,
		),
		'start_date' =>  array(
			'type' => 'date',
			'required' => true,
			  'label' => app::get('physical')->_('开始日期'),
			  'width' => 150,
			  
			  'filtertype' => 'normal',
			  'filterdefault' => true,
			  'order'=>20,
		),
		'end_date' =>  array(
			'type' => 'date',
			'required' => true,
			  'label' => app::get('physical')->_('结束日期'),
			  'width' => 150,
			  
			  'filtertype' => 'normal',
			  'filterdefault' => true,
			  'order'=>30,
		),
		'status' =>  array(
			'type' => array (
				1 => app::get('physical')->_('满'),
				2 => app::get('physical')->_('休'),
				3 => app::get('physical')->_('闲'),
			),
			'required' => true,
			'label' => app::get('physical')->_('预约状态'),
			'width' => 100,
			'editable' => false,
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
			'default_in_list' => true,
			'order'=>40,
		),
		'create_time' =>  array(
			'type' => 'time',
			'required' => true,
			  'label' => app::get('physical')->_('创建时间'),
			  'width' => 150,
			  
			  'filtertype' => 'normal',
			  'filterdefault' => true,
			  'in_list' => true,
			  'default_in_list' => true,
			  'order'=>50,
		),
		'update_time' =>  array(
			'type' => 'time',
			'required' => true,
			  'label' => app::get('physical')->_('最后更新时间'),
			  'width' => 150,
			  
			  'filtertype' => 'normal',
			  'filterdefault' => true,
			  'in_list' => true,
			  'default_in_list' => true,
			  'order'=>60,
		),
		'mome' =>
		array (
			'type' => 'varchar(200)',
			'label' => app::get('physical')->_('备注'),
			'width' => 350,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'custom',
			'filtercustom' => 
			array (
				'has' => '包含',
				'tequal' => '等于',
				'head' => '开头等于',
				'foot' => '结尾等于',
			),
			'searchtype' => 'has',
			'order'=>70,
		),
	),
  'engine' => 'innodb',
	'comment' => app::get('physical')->_('机构节假日表'),
	'version' => '$Rev: 50514 $',
);
