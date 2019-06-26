<?php 

 
$db['order_fyw_items']=array (
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
      'type' => 'bigint unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label' => app::get('b2c')->_('订单ID'),
    ),
    'goodsName' =>
    array (
      'type' => 'varchar(500)',
      'required' => true,
      'default' => '',
      'label' => app::get('b2c')->_('商品名称'),
      'editable' => false,
    ),
    'goodsId' =>
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'default' => '',
      'label' => app::get('b2c')->_('商品id'),
      'editable' => false,
    ),
    'goodsImgUrl' =>
    array (
        'type' => 'varchar(500)',
        'required' => true,
        'default' => '',
        'label' => app::get('b2c')->_('商品图片URL'),
        'editable' => false,
    ),
    'goodsPointPrice' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'label' => app::get('b2c')->_('商品积分单价'),
      'editable' => false,
    ),
    'purchasePrice' =>
    array (
        'type' => 'money',
        'default' => '0',
        'required' => true,
        'editable' => false,
        'label' => app::get('b2c')->_('商品采购单价'),
        'width' => 120,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'fyw_fee' =>
    array (
        'type' => 'decimal(5,2)',
        'default' => '1',
        'required' => true,
        'label' => app::get('b2c')->_('福员外手续费率'),
        'width' => 60,
        'match' => '[0-9\\.]+',
        'width' => 80,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'price' =>
    array (
        'type' => 'number',
        'required' => true,
        'default' => 0,
        'label' => app::get('b2c')->_('商品实际单价'),
        'editable' => false,
    ),
    'goodsNum' =>
    array (
      'type' => 'float',
      'default' => 1,
      'required' => true,
      'label' => app::get('b2c')->_('购物数量'),
      'editable' => false,
    ),
    'total_amount' =>
    array (
        'type' => 'float',
        'default' => 1,
        'required' => true,
        'label' => app::get('b2c')->_('合计金额'),
        'editable' => false,
    ),
  ),
  'index' => 
  array (
    'ind_order_id' =>
    array (
        'columns' =>array(
            0 => 'order_id',
        ),
        'type' => 'hash',
    ),
  ),
  'comment' => app::get('b2c')->_('福员外订单商品明细表'),
  'engine' => 'innodb',
  'version' => '$Rev: 51321 $',
);
