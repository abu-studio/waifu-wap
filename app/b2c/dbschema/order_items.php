<?php 

 
$db['order_items']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' => 
    array (
      'type' => 'table:orders',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'obj_id' => 
    array (
      'type' => 'table:order_objects',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'product_id' => 
    array (
      'type' => 'table:products',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'sdfpath' => 'products/product_id'
    ),
    'goods_id' => 
    array (
      'type' => 'table:goods',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'type_id' => 
    array (
      'type' => 'number',
      'editable' => false,
    ),
    'bn' => 
    array (
      'type' => 'varchar(40)',
      'editable' => false,
      'is_title' => true,
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'cost' => 
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'price' => 
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
	'g_price' => 
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
	  'label' => app::get('b2c')->_('会员价原价'),
      'editable' => false,
    ),
    'amount' => 
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'score' =>
    array (
      'type' => 'number',
      'label' => app::get('b2c')->_('积分'),
      'width' => 30,
      'editable' => false,
    ),
    'weight' => 
    array (
      'type' => 'number',
      'editable' => false,
    ),
    'nums' => 
    array (
      'type' => 'float',
      'default' => 1,
      'required' => true,
      'editable' => false,
      'sdfpath' => 'quantity',
    ),
    'sendnum' => 
    array (
      'type' => 'float',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'addon' => 
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'item_type' => 
    array (
      'type' => 
      array (
        'product' => app::get('b2c')->_('商品'),
        'pkg' => app::get('b2c')->_('捆绑商品'),
        'gift' => app::get('b2c')->_('赠品商品'),
        'adjunct'=>app::get('b2c')->_('配件商品'),
		'entity'=>app::get('b2c')->_('卡券-实体卡'),
		'virtual'=>app::get('b2c')->_('卡券-电子码'),
		'jdgoods'=>app::get('b2c')->_('京东商品'),
        'jdbook'=>'京东图书',
      ),
      'default' => 'product',
      'required' => true,
      'editable' => false,
    ),
    'vcat_id'=>
    array (
    'type' => 'int',
    'default' => '-1',
    'label' => app::get('b2c')->_('和风虚拟分类ID'),
    'width' => 110,
    'editable' => false,
    ),
  ),
  'index' => 
  array (
    'ind_item_bn' =>
    array (
        'columns' =>array(
            0 => 'bn',
        ),
        'type' => 'hash',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 51321 $',
);
