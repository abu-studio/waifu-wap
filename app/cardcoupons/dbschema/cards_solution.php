<?php

$db['cards_solution']=array (
  'columns' => 
  array (
    'card_id' => 
    array (
      'type' => 'table:cards',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'key_type' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'editable' => false,
	  'default'	 =>'01',
    ),
	'services_id' => 
    array (
      'type' => 'bigint unsigned',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'goods_id' => 
    array (
      'type' => 'bigint unsigned',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
   ),
  'index'=>
	array(
	'ind_services_id'=>
	array(
	 'columns'=>
	 array(
		0=>'services_id',
	  ),
	),
	 'ind_goods_id'=>
	array(
	 'columns'=>
	 array(
		0=>'goods_id',
	  ),
	 ),
	),
  'comment'=>app::get('cardcoupons')->_('卡券套餐内容表'),
  'engine' => 'innodb',
  'version' => '$Rev: 2323 $',
);
