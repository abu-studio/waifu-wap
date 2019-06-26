<?php 

 
$db['abcommercial_lv']=array (
  'columns' => 
  array (
    'commercial_lv_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
    ),
    'dj_id' =>
    array (
      'required' => true,
      'label' => app::get('b2c')->_('商社编号id'),
      'width' => 75,
      'type' => 'varchar(50)',
      'editable' => true,
      'filtertype' => 'bool',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'm_id' => 
    array (
      'type' => 'number',
      'is_title' => true,
      'required' => true,
      'label' => app::get('b2c')->_('用户会员id'),
      'width' => 110,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
    ), 
  ),
  'comment' => app::get('b2c')->_('商社表'),
  'engine' => 'innodb',
  'version' => '$Rev: 445231 $',
);
