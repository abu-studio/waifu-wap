<?php  
$db['order_log']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' => 
    array (
		'type' => 'table:orders',
		'required' => true,
		'default' => 0,
		'editable' => false,
    ),
    'op_id' => 
    array (
      'type' => 'number',//'table:users@desktop',
      'label' => app::get('physical')->_('操作员'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'op_name' => 
    array (
      'type' => 'varchar(100)',
      'label' => app::get('physical')->_('操作人名称'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'alttime' => 
    array (
      'type' => 'time',
	  'required' => true,
      'label' => app::get('physical')->_('操作时间'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
	'record' => 
    array (
      'type' => 'varchar(255)',
	  'required' => true,
      'label' => app::get('physical')->_('操作记录'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
	'remarks' => 
    array (
      'type' => 'text',
	  'label' => app::get('physical')->_('备注'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
    'result' => 
    array (
      'type' => 
      array (
        '1' => app::get('physical')->_('成功'),
        '2' => app::get('physical')->_('失败'),
      ),
      'required' => true,
      'label' => app::get('physical')->_('操作结果'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
    ),
  ),
  'engine' => 'innodb',
  'comment' => app::get('physical')->_('体检预约单操作日志表'),
  'version' => '$Rev: 46974 $',
);
