<?php

$db['tag']=array (
  'columns' => 
  array (
    'tag_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'tag_name' => 
    array (
      'type' => 'varchar(20)',
      'required' => true,
      'default' => '',
      'label' => app::get('desktop')->_('标签名'),
      'width' => 200,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
    'tag_mode' => 
    array (
      'type' => 
      array (
        'normal' => app::get('desktop')->_('普通标签'),
      ),
      'default' => 'normal',
      'label' => app::get('desktop')->_('标签类型'),
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'app_id' => 
    array (
      'type' => 'varchar(32)',
      'label' => app::get('desktop')->_('应用'),
      'required' => true,
      'width' => 100,
      'in_list' => true,
    ),
    'tag_type' => 
    array (
      'type' => 'varchar(20)',
      'required' => true,
      'default' => '',
      'label' => app::get('desktop')->_('标签对象'),
      'editable' => false,
      'in_list' => true,
    ),
    'tag_abbr' => 
    array (
      'type' => 'varchar(150)',
      'required' => true,
      'default' => '',
      'label' => app::get('desktop')->_('标签备注'),
      'editable' => false,
      'in_list' => true,
    ),
    'store_id' => 
    array (
      'type' => 'table:storemanger@business',
      'label' => '店铺名称',      
      'editable' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'orderby' => true,
      'default' => 0,
    ),
  ),
  'index' => 
  array (
    'ind_type' => 
    array (
      'columns' => 
      array (
        0 => 'tag_type',
      ),
    ),
    'ind_name' => 
    array (
      'columns' => 
      array (
        0 => 'tag_name',
      ),
    ),
  ),
  'version' => '$Rev: 42201 $',
);