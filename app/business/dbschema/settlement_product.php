<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

$db['settlement_product'] = array(
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
			'type' => array(
				'product' => '商品',
				'entity' => '实体卡',
				'virtual' => '电子码',
			),
			'label' => '类型',
			'width' => 75,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'count' => array(
			'type' => 'decimal(20,2)',
			'label' => '销售数量',
			'default' => 0,
			'width' => 30,
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
		'total_cost' => array(
			'type' => 'money',
			'default' => '0',
			'label' => '成本合计',
			'width' => 75,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'total_cost' => array(
			'type' => 'money',
			'default' => '0',
			'label' => '成本合计',
			'width' => 75,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'start' => array(
			'type' => 'time',
			'label' => '开始日期',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
		'end' => array(
			'type' => 'time',
			'label' => '结束日期',
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
    ),
    'comment' => app::get('financial')->_('结算明细'),
    'version' => '$Rev: 41329 $',
);
