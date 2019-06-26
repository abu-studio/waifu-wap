<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

$db['settlement_result'] = array(
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
            'label' => app::get('business')->_('订单号'),
            'width' => 200,
            'editable' => false,
            'in_list' => false,
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
        'real_account' => array(
            'type' => 'money',
            'label' => app::get('business')->_('实际结算金额'),
            'default' => '0',
            'required' => true,
            'filtertype' => 'number',
            'filterdefault' => true,
            'width' => 130,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            ),
         'is_balance' => array (
            'type' => 
              array (
                1 => '已结算',
                2 => '未结算',
                3 => '待结算',
              ),
			'in_list' => true,
			'label'=>'是否结算',
            'comment' => '是否结算',
            'editable' => false,
            'default' => '2',
            'required' => true,
         ),
       ),
    'comment' => app::get('financial')->_('结算数据'),
    'version' => '$Rev: 41329 $',
    );
