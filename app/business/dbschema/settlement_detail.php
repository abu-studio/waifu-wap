<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

$db['settlement_detail'] = array(
    'columns' => array(
        'id' => array(
            'type' => 'bigint unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => app::get('business')->_('ID'),
            'width' => 110,
            'editable' => false,
            'in_list' => false,
            'hidden' => true,
            'default_in_list' => false,
            ),
        'settlement_no' => array(
            'type' => 'varchar(15)',
            'required' => true,
            'label' => app::get('business')->_('结算单号'),
            'width' => 140,
            'editable' => false,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
            ),
        'order_id' => array(
            'type' => 'varchar(20)',
            'label' => '订单号',
            'width' => 200,
            'editable' => false,
            'in_list' => false,
            ),
		'company_no' => array(
			'type' => 'varchar(100)',
			'label' => '商社号',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
		),
		'company_name' => array(
			'type' => 'varchar(200)',
			'label' => '商社名称',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
		),
        'goods_id' => array(
            'type' => 'varchar(20)',
            'label' => '商品ID',
            'width' => 200,
            'hidden' => true,
            'editable' => false,
            'in_list' => false,
            ),
		'goods_name' => array(
			'type' => 'varchar(200)',
			'label' => '商品名称',
			'width' => 310,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'bn' => array(
			'type' => 'varchar(200)',
			'label' => '商品编号',
			'width' => 110,
			'searchtype' => 'head',
			'editable' => false,
			'in_list' => true,
		),
		'type' => array(
			'type' => 'varchar(200)',
			'label' => '支付类型',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'cost' => array(
			'type' => 'money',
			'default' => '0',
			'label' => '成本价',
			'width' => 75,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'price' => array(
			'type' => 'money',
			'default' => '0',
			'label' => '销售价',
			'width' => 75,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'count' => array(
			'type' => 'decimal(20,2)',
			'label' => '发货数量',
			'default' => 0,
			'width' => 30,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'cost_freight' => array(
			'type' => 'money',
			'label' => app::get('business')->_('订单运费'),
			'default' => '0',
			'required' => true,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
        'order_money' => array(
            'type' => 'money',
            'label' => app::get('business')->_('订单金额'),
            'default' => '0',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'sfsc_money' => array(
            'type' => 'money',
            'label' => app::get('business')->_('福点金额'),
            'default' => '0',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'third_money' => array(
            'type' => 'money',
            'label' => app::get('business')->_('第三方金额'),
            'default' => '0',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
        ),
		'account' => array(
			'type' => 'money',
			'label' => app::get('business')->_('结算金额'),
			'default' => '0',
			'required' => true,
			'filtertype' => 'number',
			'filterdefault' => true,
			'width' => 130,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'shipping' => array(
			'type' => 'varchar(100)',
			'label' => '配送方式',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'ship_name' => array(
			'type' => 'varchar(50)',
			'label' => '收货人',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'ship_addr' => array(
			'type' => 'text',
			'label' => '收货地址',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'ship_zip' => array(
			'type' => 'varchar(20)',
			'label' => '邮编',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'ship_mobile' => array(
			'type' => 'varchar(50)',
			'label' => '收货人手机',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
    ),
    'comment' => app::get('financial')->_('店铺结算明细'),
    'version' => '$Rev: 41329 $',
);