<?php 
$db['store_package_price']=array (
	'columns' =>
	array (
		'store_id' =>
		array (
			'type' => 'table:store',
			'default' => 0,
			'required' => true,
			'editable' => true
		),
		'package_id' =>
		array (
			'type' => 'table:package',
			'default' => 0,
			'required' => true,
			'editable' => true
		),
		'man' =>
		array (
			'default' => '0.00',
			'type' => 'money',
			'required' => true,
			'editable' => true,
		),
		'unmdwoman' =>
		array (
			'default' => '0.00',
			'type' => 'money',
			'required' => true,
			'editable' => true,
		),
		'mdwoman' =>
		array (
			'default' => '0.00',
			'type' => 'money',
			'required' => true,
			'editable' => true,
		),
	),
	'index' =>
	array (
		'index_1' =>
		array (
			'columns' =>
			array (
				0 => 'store_id',
				1 => 'package_id',
			),
		),
	),
  'engine' => 'innodb',
	'comment' => app::get('physical')->_('体检门店套餐关联表'),
	'version' => '$Rev: 50514 $',
);
