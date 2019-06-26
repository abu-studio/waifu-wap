<?php 
$db['project']=array (
	'columns' =>
	array (
		'project_id' =>
		array (
			'type' => 'int(11)',
			'required' => true,
			'pkey' => true,
			'extra' => 'auto_increment',
			'label' => app::get('physical')->_('项目ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => false,
			'default_in_list' => false,
		),
		'project_name' =>
		array (
			'type' => 'varchar(200)',
			'required' => true,
			'label' => app::get('physical')->_('项目名称'),
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
		'project_code' =>
		array (
			'type' => 'varchar(50)',
			'required' => true,
			'label' => app::get('physical')->_('项目编号'),
			'width' => 150,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>20,
		),
		'subject_id' =>
		array (
			'type' => 'table:subject',
			'required' => true,
			'label' => app::get('physical')->_('科目ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>30,
		),
		'medical_code' =>
		array (
			'type' => 'varchar(50)',
			'required' => true,
			'label' => app::get('physical')->_('医学编号'),
			'width' => 150,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>40,
		),
		'price' =>
		array (
			'default' => '0.00',
			'type' => 'money',
			'required' => true,
			'label' => app::get('physical')->_('价格'),
			'width' => 100,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'order'=>50,
		),
		'introduction' => 
		array (
			'type' => 'text',
			'label' => app::get('physical')->_('简介'),
			'width' => 200,
			'editable' => true,
			'filtertype' => 'normal',
			'in_list' => true,
			'default_in_list' => true,
			'order'=>60,
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
			  'order'=>70,
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
			  'order'=>80,
		),
	),
	'index'=>array(
		'ind_project_name'=>
		array(
			'columns'=>array(
				0=>'project_name',
			),
		),
		'ind_project_code'=>
		array(
			'columns'=>array(
				0=>'project_code',
			),
		),
		'ind_medical_code'=>
		array(
			'columns'=>array(
				0=>'medical_code',
			),
		),
	),
  'engine' => 'innodb',
	'comment' => app::get('physical')->_('体检项目表'),
	'version' => '$Rev: 50514 $',
);
