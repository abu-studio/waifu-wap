<?php 
 $db['lcorder']=array (
  'columns' => 
  array (
    'order_id' => 
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'default' => 0,
      'pkey' => true,
      'label' => '订单号',
      'is_title' => true,
      'width' => 110,
      'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'order_name'=>
	array(
		'type' => 'varchar(200)'
	)
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 42376 $',
);