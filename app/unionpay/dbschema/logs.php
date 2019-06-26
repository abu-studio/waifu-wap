<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
$db['logs']=array ( 
    'columns' =>
    array (
        'id' =>
        array (
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
        ),
        'dateline' =>
        array (
            'type' => 'time',
            'required' => true,
            'label' => app::get('unionpay')->_('操作时间'),
            'filtertype' => 'yes', 
            'filterdefault' => true,
            'width' => 120,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'operate_type' => 
        array (
          'type' => 
          array (
            'pay' => app::get('unionpay')->_('支付'),
            'refund' => app::get('unionpay')->_('退款'),
            'default' => app::get('unionpay')->_('未知'),
          ),
          'default' => 'default',
          'label' => app::get('unionpay')->_('类型'),
          'width' => 110,
          'filtertype' => 'yes',
          'filterdefault' => true,
          'in_list' => true,
        ),
        'order_id' =>
        array (
            'type' => 'bigint unsigned',
            'required' => true,
            'default' => 0,
           // 'pkey' => true,
            'label' => app::get('unionpay')->_('订单号'),
            'filtertype' => 'yes',
            'filterdefault' => true,
            'width' => 120,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'bill_id' =>
        array (
            'type' => 'bigint unsigned',
            'required' => true,
            'default' => 0,
           // 'pkey' => true,
            'label' => app::get('unionpay')->_('单据号'),
            'filtertype' => 'yes',
            'filterdefault' => true,
            'width' => 120,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'operate_key' =>
        array (
            'type' => 'varchar(255)',
            'label' => app::get('unionpay')->_('关键字'),
            'width' => 200,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'resp_result' =>
        array (
            'type' => 'varchar(255)',
            'label' => app::get('unionpay')->_('结果状态'),
            'width' => 200,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'memo' =>
        array (
            'type' => 'longtext',
            'label' => app::get('unionpay')->_('数据'),
            'width' => 200,
            'in_list' => true,
            'default_in_list' => true,
        ),
    ),
    'index' => 
    array (
        'ind_dateline' => 
        array (
          'columns' => 
          array (
            0 => 'dateline',
          ),
        ),
      ),
);
