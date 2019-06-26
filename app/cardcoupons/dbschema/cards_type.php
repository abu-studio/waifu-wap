<?php

$db['cards_type']=array (
  'columns' => 
  array (
    'card_type_id' => 
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
    'name' => 
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('名称'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'handle' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('句柄值'),
	  'default_in_list' => true,
      'editable' => false,
      'in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'varchar(255)',
      'label' => '备注',
	  'default' => '',
      'width' => 110,
      'hidden' => false,
      'editable' => false,
      'in_list' => true,
    ),
	'createtime' => 
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('创建时间'),
      'editable' => false,
      'in_list' => true,
    ),
	'lasttime' => 
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('最后更新时间'),
      'editable' => false,
      'in_list' => true,
    ),
   ),
  'comment' => app::get('b2c')->_('卡券类型表'),
  'engine' => 'innodb',
  'version' => '$Rev: 2323 $',
);
