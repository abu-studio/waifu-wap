<?php
$db['cmpay_log'] = array (
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
        'request_type' =>
        array (
            'type' =>
            array(
                'N' => '被动',
                'Y' => '主动',
            ),
            'default' => 'Y',
            'required' => true,
            'label' => app::get('cardcoupons')->_('请求方式'),
            'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
            'comment' => app::get('cardcoupons')->_('请求方式'),
        ),
        'returncode' => array (
            'type' => 'longtext',
            'default' => '',
            'required' => true,
            'label' => app::get('cardcoupons')->_('返回数据'),
            'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'comment' => app::get('cardcoupons')->_('返回数据'),
        ),
		'result' => 
		array (
		  'type' => array (
			'SUCCESS' => app::get('cardcoupons')->_('成功'),
			'FAILURE' => app::get('cardcoupons')->_('失败'),
		  ),
		  'required' => true,
		  'default' => 'FAILURE',
		  'label' => app::get('cardcoupons')->_('操作结果'),
		  'width' => 110,
		  'editable' => false,
		  'filtertype' => 'yes',
		  'filterdefault' => true,
		  'in_list' => true,
		),
		'result_describe' => array(
			'type' => 'longtext',
			'required'=> false,
			'label'=> app::get('cardcoupons')->_('处理结果'),
			'comment' => app::get('cardcoupons')->_('处理结果'),
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
		),
		'cmpapi' => array(
			'type' => 'varchar(35)',
			'required'=> false,
			'label'=> app::get('cardcoupons')->_('调用接口名称'),
			'comment' => app::get('cardcoupons')->_('调用接口名称'),
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
		),
		'last_modified' => 
		array (
		  'label' => app::get('cardcoupons')->_('插入时间'),
		  'type' => 'last_modify',
		  'width' => 110,
		  'editable' => false,
		  'in_list' => true,
		  'default_in_list' => true,
		),
    ),
    'comment' => app::get('cardcoupons')->_('荷包支付接口日志表'),
);
