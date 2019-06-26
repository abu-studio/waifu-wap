<?php
$db['cmpay_offlinenotify_log'] = array (
    'columns' =>
    array (
        'log_id' =>
        array (
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'comment' => app::get('cardcoupons')->_('日志id'),
			'label' => app::get('cardcoupons')->_('日志id'),
        ),
        'payno' =>
        array (
            'type' => 'varchar(50)',
            'default' => '',
            'required' => true,
            'label' => app::get('cardcoupons')->_('流水号'),
            'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
            'comment' => app::get('cardcoupons')->_('流水号'),
        ),
        'returncode' =>
        array (
            'type' => 'varchar(50)',                    
            'label' => app::get('cardcoupons')->_('返回码'),
            'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'comment' => app::get('cardcoupons')->_('返回码'),
        ),
        'message' =>
        array (
            'type' => 'varchar(50)',
            'label' => app::get('cardcoupons')->_('返回码描述'),
            'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'comment' => app::get('cardcoupons')->_('返回码描述'),
        ),
        'type' =>
        array (
            'type' => 'varchar(50)',
            'default' => '',
            'required' => true,
            'label' => app::get('cardcoupons')->_('接口类型'),
            'width' => 100,
            'default_in_list' => true,
            'in_list' => true,
			'comment' => app::get('cardcoupons')->_('接口类型'),
        ),
        'amount' =>
        array (
            'type' => 'money',            
            'label' => app::get('cardcoupons')->_('支付金额'),
			'comment' => app::get('cardcoupons')->_('支付金额'),
        ),
        'amtitem' =>
        array (
            'type' => 'varchar(25)',
            'default' => '',
            'label' => app::get('cardcoupons')->_('金额明细'),
			'comment' => app::get('cardcoupons')->_('金额明细'),
        ),
        'bankabbr' =>
        array (
          'type' => 'varchar(50)',
          'editable' => false,
		  'label' => app::get('cardcoupons')->_('支付银行'),
          'comment' => app::get('cardcoupons')->_('支付银行'),
        ),
		'mobile' =>
		array(
			'type' => 'varchar(20)',
			'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'label' => app::get('cardcoupons')->_('支付手机号'),
			'comment' => app::get('cardcoupons')->_('支付手机号'),
		),
		'cmp_order_id' =>
		array(
			'type' => 'varchar(30)',
			'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'label' => app::get('cardcoupons')->_('商户订单号'),
			'comment' => app::get('cardcoupons')->_('商户订单号'),
		),
		'paydate' =>
		array(
			'type' => 'varchar(30)',
			'label' => app::get('cardcoupons')->_('支付时间'),
			'comment' => app::get('cardcoupons')->_('支付时间'),
		),
		'accountdate' =>
		array(
			'type' => 'varchar(30)',
			'label' => app::get('cardcoupons')->_('会计时间'),
			'comment' => app::get('cardcoupons')->_('会计时间'),
		),
		'reserved1' =>
		array(
			'type' => 'varchar(50)',
			'label' => app::get('cardcoupons')->_('保留字段1'),
			'comment' => app::get('cardcoupons')->_('保留字段1'),
		),
		'reserved2' =>
		array(
			'type' => 'varchar(50)',
			'label' => app::get('cardcoupons')->_('保留字段2'),
			'comment' => app::get('cardcoupons')->_('保留字段2'),
		),
		'status' =>
		array(
			'type' => 'varchar(30)',
			'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'label' => app::get('cardcoupons')->_('支付结果'),
			'comment' => app::get('cardcoupons')->_('支付结果'),
		),
		'orderdate' =>
		array(
			'type' => 'varchar(50)',
			'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'label' => app::get('cardcoupons')->_('订单提交日期'),
			'comment' => app::get('cardcoupons')->_('订单提交日期'),
		),
		'fee' =>
		array(
			'type' => 'money',
			'default' => '0',
			'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'label' => app::get('cardcoupons')->_('费用'),
			'comment' => app::get('cardcoupons')->_('费用'),
		),
		'order_id' =>
		array(
			'type' => 'bigint unsigned',
			'default' => 0,
			'label' => app::get('cardcoupons')->_('关联自有平台订单号'),
            'comment' => app::get('cardcoupons')->_('关联自有平台订单号'),
			'is_title' => true,
			'width' => 110,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
		),
        'is_send' =>
        array(
            'type' =>
            array(
                'N' => '否',
                'Y' => '是',
            ),
            'default' => 'N',
            'label' => app::get('cardcoupons')->_('是否发送短信给客户'),
			'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
            'comment' => app::get('cardcoupons')->_('是否发送短信给客户'),
        ),
		'last_modified' => 
		array (
		  'label' => app::get('cardcoupons')->_('最后更新时间'),
		  'type' => 'last_modify',
		  'width' => 110,
		  'editable' => false,
		  'in_list' => true,
		  'default_in_list' => true,
		),
    ),
    'comment' => app::get('cardcoupons')->_('荷包支付用户消息表'),
);
