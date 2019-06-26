<?php 
$db['member_invoice']=array ( 
  'columns' => 
  array (
    'invoice_id' => 
    array (
      'type' => 'int(10)', 
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'member_id' => 
    array (
      'type' => 'table:members',
      'default' => 0,
      'required' => true,
      'editable' => false,
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
    ),
    'invoice_title' =>
    array (
      'type' => 'varchar(50)',
	  'label' => app::get('b2c')->_('发票抬头'),
      'editable' => false,
    ),
    'invoice_default' =>
    array (
      'type' => 'intbool',
      'default' => '0',
      'required' => true,
	  'label' => app::get('b2c')->_('是否默认'),
      'editable' => false,
    ),
    'company_name' =>
    array (
      'type' => 'varchar(100)',
      'label' => app::get('b2c')->_('单位名称'),
      'editable' => false,
    ),
    'vat_number' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('b2c')->_('纳税人识别号'),
      'editable' => false,
    ),
    'vat_addr' =>
    array (
      'type' => 'varchar(100)',
      'label' => app::get('b2c')->_('注册地址'),
      'editable' => false,
    ),
    'vat_tel' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('b2c')->_('注册号码'),
      'editable' => false,
    ),
    'vat_bank' =>
    array (
        'type' => 'varchar(50)',
        'label' => app::get('b2c')->_('开户银行'),
        'editable' => false,
    ),
    'bank_number' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('b2c')->_('银行账号'),
      'editable' => false,
    ),
	'last_modify' =>
    array (
      'type' => 'last_modify',
      'label' => app::get('b2c')->_('更新时间'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'orderby' => true,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 42752 $',
);
