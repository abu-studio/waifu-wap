<?php 
$db['order_invoice']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'int(10)', 
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
   'invoice_id' =>
      array (
          'type' => 'table:member_invoice',
          'default' => 0,
          'required' => true,
          'editable' => false,
      ),
    'member_id' => 
    array (
        'type' => 'table:members',
        'default' => 0,
        'required' => true,
        'editable' => false,
    ),
	'order_id' => 
    array (
        'type' => 'table:orders',
        'label' => app::get('b2c')->_('订单编号'),
        'required' => true,
        'default' => 0,
        'editable' => false,
        'in_list' => true,
    ),
	'is_send' =>
	array(
		'type' => array(
			'yes' => '已处理',
			'no' => '未处理',
		),
        'default' => 'no',
        'required' => true,
        'label' => app::get('b2c')->_('发票处理'),
        'in_list' => true,
        'default_in_list' => true, //默认显示在列表项中
        'filtertype' => 'normal',  //高级筛选的过滤类型//设置为normal按type的来生成过滤
        'filterdefault' => 'true',  //默认在高级筛选中显示
	),
    'send_message' => array(
        'type' => 'varchar(50)',
        'label' => app::get('b2c')->_('快递号'),
        'editable' => false,
        'in_list' => true,
    ),
    'invoice_type' =>
    array (
        'type' => array(
			'vat' => app::get('b2c')->_('增值税票'),
			'com' => app::get('b2c')->_('普票'),
	    ),
        'default' => 'com',
	    'required' => true,
	    'label' => app::get('b2c')->_('发票类型'),
	    'width' => 75,
        'editable' => false,
        'in_list' => true,
    ),
    'invoice_title' =>
    array (
        'type' => 'varchar(50)',
	    'label' => app::get('b2c')->_('发票抬头'),
        'editable' => false,
        'in_list' => true,
    ),
    'company_name' =>
    array (
        'type' => 'varchar(100)',
        'label' => app::get('b2c')->_('单位名称'),
        'editable' => false,
        'in_list' => true,
    ),
    'vat_number' =>
    array (
        'type' => 'varchar(50)',
        'label' => app::get('b2c')->_('纳税人识别号'),
        'editable' => false,
        'in_list' => true,
    ),
    'vat_addr' =>
    array (
        'type' => 'varchar(100)',
        'label' => app::get('b2c')->_('注册地址'),
        'editable' => false,
        'in_list' => true,
    ),
    'vat_tel' =>
    array (
        'type' => 'varchar(50)',
        'label' => app::get('b2c')->_('注册号码'),
        'editable' => false,
        'in_list' => true,
    ),
    'vat_bank' =>
    array (
        'type' => 'varchar(50)',
        'label' => app::get('b2c')->_('开户银行'),
        'editable' => false,
        'in_list' => true,
    ),
    'bank_number' =>
    array (
        'type' => 'varchar(50)',
        'label' => app::get('b2c')->_('银行账号'),
        'editable' => false,
        'in_list' => true,
    ),
	'name' => 
    array (
        'is_title' => true,
        'type' => 'varchar(50)',
	    'label' =>  app::get('b2c')->_('姓名'),
        'editable' => false,
        'in_list' => true,
    ),
	'area' => 
    array (
        'type' => 'varchar(50)',
	    'label' =>  app::get('b2c')->_('区域'),
        'editable' => false,
        'in_list' => true,
    ),
    'addr' => 
    array (
      'type' => 'varchar(255)',
	  'label' =>  app::get('b2c')->_('地址'),
      'editable' => false,
      'in_list' => true,
    ),
    'zip' => 
    array (
        'type' => 'varchar(20)',
	    'label' =>  app::get('b2c')->_('邮编'),
        'editable' => false,
        'in_list' => true,
    ),
    'tel' => 
    array (
        'type' => 'varchar(50)',
	    'label' =>  app::get('b2c')->_('电话'),
        'editable' => false,
        'in_list' => true,
    ),
    'mobile' => 
    array (
        'type' => 'varchar(50)',
        'label' =>  app::get('b2c')->_('手机号码'),
        'editable' => false,
        'in_list' => true,
    ),
    'application_date' =>
    array (
      'type' => 'time',
      'label' => app::get('b2c')->_('申请日期'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'last_modify' =>
    array (
      'type' => 'last_modify',
      'label' => app::get('b2c')->_('更新时间'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'commercial_id' => 
    array (
        'is_title' => true,
        'type' => 'varchar(50)',
        'label' =>  app::get('b2c')->_('商社编号'),
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        // 'filtertype' => 'normal',
        // 'filterdefault' => 'true', 
    ),
    'commercial_name' => 
    array (
        'is_title' => true,
        'type' => 'varchar(50)',
        'label' =>  app::get('b2c')->_('商社名称'),
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true, 
        // 'filtertype' => 'normal',
        // 'filterdefault' => 'true',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 42752 $',
);
