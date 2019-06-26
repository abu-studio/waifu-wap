<?php 
 $db['orders_fyw']=array (
  'columns' => 
  array (
    'order_id' => 
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'label' => app::get('b2c')->_('订单号'),
      'is_title' => true,
      'width' => 120,
      'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'outTradeNo' =>
    array (
        'type' => 'varchar(100)',
        'default' => '',
        'required' => true,
        'label' => app::get('b2c')->_('福员外订单号'),
        'width' => 120,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'subject' =>
    array (
        'type' => 'varchar(500)',
        'default' => '',
        'required' => true,
        'label' => app::get('b2c')->_('订单标题'),
        'width' => 75,
        'editable' => false,
    ),
    'payType' =>
    array (
        'type' =>
            array (
                'point' => app::get('b2c')->_('福点支付'),
                'others' => app::get('b2c')->_('其他支付'),
            ),
        'default' => 'point',
        'required' => true,
        'label' => app::get('b2c')->_('支付方式'),
        'width' => 75,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'totalPointAmt' =>
    array (
        'type' => 'money',
        'default' => '0',
        'required' => true,
        'editable' => false,
        'label' => app::get('b2c')->_('福员外订单价'),
        'width' => 120,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'oriTradeNo' =>
        array (
            'type' => 'bigint unsigned',
            'required' => false,
            'label' => app::get('b2c')->_('原始订单号'),
            'is_title' => true,
            'width' => 120,
            'searchtype' => 'has',
            'editable' => false,
            'filtertype' => 'custom',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
        ),
    'oriTradePointAmt' =>
        array (
            'type' => 'money',
            'default' => '0',
            'required' => false,
            'label' => app::get('b2c')->_('原有订单总积分数'),
            'editable' => false,
        ),
    'fyw_fee' =>
     array (
         'type' => 'decimal(5,2)',
         'default' => '1',
         'required' => true,
         'label' => app::get('b2c')->_('福员外手续费率'),
         'match' => '[0-9\\.]+',
     ),
    'final_amount' => 
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
      'label' => app::get('b2c')->_('订单总金额'),
      'width' => 120,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'order_status' =>
    array(
    'type' =>
        array (
            '0' => app::get('b2c')->_('未完成'),
            '1' => app::get('b2c')->_('普通订单 '),
            '2' => app::get('b2c')->_('退款订单'),
        ),
    'default' => '0',
    'label' => app::get('b2c')->_('订单状态'),
    'required' => true,
    'editable' => false,
    'in_list' => true,
    'default_in_list' => true,
    ),
    'refund_status' =>
    array(
        'type' =>
            array (
                '0' => app::get('b2c')->_(' '),
                '1' => app::get('b2c')->_('全额退款'),
                '2' => app::get('b2c')->_('部分退款'),
            ),
        'default' => '0',
        'label' => app::get('b2c')->_('是否部分退款'),
        'required' => true,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'createtime' =>
    array (
      'type' => 'time',
      'label' => app::get('b2c')->_('福员外下单时间'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'orderby' => true,
    ),
    'local_createtime' =>
    array (
        'type' => 'time',
        'label' => app::get('b2c')->_('本地下单时间'),
        'width' => 110,
        'editable' => false,
        'filtertype' => 'time',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
        'orderby' => true,
    ),
    'last_modified' => 
    array (
      'label' => app::get('b2c')->_('更新时间'),
      'type' => 'last_modify',
      'width' => 110,
      'editable' => false,
    ),
    'member_id' =>
    array (
      'type' => 'number',
      'label' => app::get('b2c')->_('会员id'),
      'width' => 75,
      'editable' => false,
    ),
    'member_no' =>
    array (
        'type' => 'varchar(100)',
        'default' => '',
        'required' => true,
        'label' => app::get('b2c')->_('会员号'),
        'width' => 120,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'order_type' =>
    array (
      'type' =>  array (
          'fyw' => app::get('b2c')->_('福员外订单'),
          'other' => app::get('b2c')->_('支付成功'),
      ),
      'default' => 'fyw',
      'required' => true,
      'label' => app::get('b2c')->_('订单类型'),
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'tradeStatus' =>
    array(
    'type' =>
        array (
        'ING' => app::get('b2c')->_('处理中 '),
        'SUCC' => app::get('b2c')->_('支付成功'),
        'FAIL' => app::get('b2c')->_('支付失败'),
        ),
    'default' => 'ING',
    'label' => app::get('b2c')->_('交易状态'),
    'required' => true,
    'editable' => false,
    'in_list' => true,
    'default_in_list' => true,
    ),
    'is_balance' =>
    array (
        'type' =>
        array (
        1 => '是',
        2 => '否',
        ),
        'comment' => '是否结算',
        'label' => '是否结算',
        'editable' => false,
        'default' => '2',
        'required' => true,
    ),
  ),
  'index' => 
  array (
    'ind_order_id' =>
    array (
      'columns' => 
      array (
        0 => 'order_id',
      ),
    ),
    'ind_createtime' =>
    array (
      'columns' => 
      array (
        0 => 'createtime',
      ),
    ),
  ),
  'comment' => app::get('b2c')->_('福员外订单表'),
  'engine' => 'innodb',
  'version' => '$Rev: 51321 $',
);