<?php 
$db['subject']=array (
	'columns' =>
	array (
		'subject_id' =>
		array (
			'type' => 'number',
			'required' => true,
			'pkey' => true,
			'extra' => 'auto_increment',
			'label' => app::get('physical')->_('科目ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => false,
			'default_in_list' => false,
		),
		'subject_name' =>
		array (
			'type' => 'varchar(200)',
			'required' => true,
			'label' => app::get('physical')->_('科目名称'),
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
		'subject_code' =>
		array (
			'type' => 'varchar(50)',
			'required' => true,
			'label' => app::get('physical')->_('科目编号'),
			'width' => 150,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>20,
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
			'order'=>30,
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
			  'order'=>40,
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
			  'order'=>50,
		),
	),
	'index'=>array(
		'ind_subject_code'=>
		array(
			'columns'=>array(
				0=>'subject_code',
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
	'comment' => app::get('physical')->_('体检科目表'),
	'version' => '$Rev: 50514 $',
);
