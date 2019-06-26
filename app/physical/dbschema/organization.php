<?php 
$db['organization']=array (
	'columns' =>
	array (
		'organization_id' =>
		array (
			'type' => 'number',
			'required' => true,
			'pkey' => true,
			'extra' => 'auto_increment',
			'label' => app::get('physical')->_('机构ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => false,
			'default_in_list' => false,
		),
		'organization_name' =>
		array (
			'type' => 'varchar(200)',
			'required' => true,
			'label' => app::get('physical')->_('机构名称'),
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
			'order'=>10,
		),
		'organization_code' =>
		array (
			'type' => 'varchar(50)',
			'required' => true,
			'label' => app::get('physical')->_('机构编号'),
			'width' => 150,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'has',
			'searchtype' => 'has',
			'order'=>20,
		),
		'type' =>
		array (
			'type' => array (
				1 => app::get('physical')->_('体检'),
			),
			'required' => true,
			'label' => app::get('physical')->_('服务类型'),
			'width' => 150,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>30,
		),
		'logo' =>
			array (
			  'type' => 'varchar(255)',
			  'label' => app::get('physical')->_('图片'),
			  'width' => 100,
			  'editable' => true,
			  'order'=>40,
			),
		'url' =>
			array (
			  'type' => 'varchar(255)',
			  'label' => app::get('physical')->_('网址'),
			  'width' => 200,
			  'editable' => true,
				'in_list' => true,
				'default_in_list' => true,
			  'order'=>50,
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
			  'order'=>60,
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
			  'order'=>70,
		),
	),
  'engine' => 'innodb',
	'comment' => app::get('physical')->_('机构表'),
	'version' => '$Rev: 50514 $',
);
