<?php
$db['rdp_log'] = array (
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
        'request_data' => array (
            'type' => 'longtext',            
            'label' => app::get('cardcoupons')->_('请求参数'),
            'width' => 80,
            'default_in_list' => true,
            'in_list' => true,
			'comment' => app::get('cardcoupons')->_('请求参数'),
        ),
		'result_data' => 
		array (
		  'type' => 'longtext',		    
		  'label' => app::get('cardcoupons')->_('返回参数'),
		  'width' => 110,
		  'editable' => false,
		  'filtertype' => 'yes',
		  'filterdefault' => true,
		  'in_list' => true,
		  'comment' => app::get('cardcoupons')->_('返回参数'),
		),
		'last_modified' => 
		array (
		  'label' => app::get('cardcoupons')->_('插入时间'),
		  'type' => 'int unsigned',
		  'width' => 110,
		  'editable' => false,
		  'in_list' => true,
		  'default_in_list' => true,
		  'comment' => app::get('cardcoupons')->_('插入时间'),
		),
		'update_modified' => 
		array (
		  'label' => app::get('cardcoupons')->_('修改时间'),
		  'type' => 'int unsigned',
		  'width' => 110,
		  'editable' => false,
		  'in_list' => true,
		  'default_in_list' => true,
		  'comment' => app::get('cardcoupons')->_('修改时间'),
		),
    ),
    'comment' => app::get('cardcoupons')->_('rdp平台连接日志表'),
);
