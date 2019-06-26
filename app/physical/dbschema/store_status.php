<?php 
$db['store_status']=array (
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
		'store_id' =>
		array (
			'type' => 'table:store',
			'required' => true,
			'label' => app::get('physical')->_('门店ID'),
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
			'editable' => true,
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
	),
  'engine' => 'innodb',
	'comment' => app::get('physical')->_('门店预约时段设置表'),
	'version' => '$Rev: 50514 $',
);
