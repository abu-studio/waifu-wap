<?php 
$db['sandorder']=array (
  'columns' => 
  array (
	'log_id'=>
	array (
      'type' => 'int(11)',
      'pkey' => true,
      'required' => true,
	  'extra' => 'auto_increment',
      'label' => 'ID',
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'default' => 0,
      'label' => '订单号',
      'editable' => false,
	  'filtertype' => 'yes',
	  'searchtype' => 'yes',
	  'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	 'member_id' => 
    array (
      'type' => 'mediumint(8)',
      'label' => '会员ID',
      'editable' => false,
    ),
	'member_name' => 
    array (
      'type' => 'varchar(100)',
      'label' => '会员用户名',
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
	  'searchtype' => 'yes',
      'default_in_list' => true,
    ),
	'sand_name' => 
    array (
      'type' => 'varchar(100)',
      'label' => '杉德用户名',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'company_no' => 
    array (
      'type' => 'varchar(100)',
      'label' => '商社号',
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
	  'searchtype' => 'yes',
      'default_in_list' => true,
    ),
	'company_name' => 
    array (
      'type' => 'varchar(200)',
      'label' => '商社名称',
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
	  'searchtype' => 'yes',
      'default_in_list' => true,
    ),
   'amount' =>                         
    array (
      'type' => 'money',
	  'editable' => false,
      'label' => '充值金额',
	  'in_list' => true,
      'default_in_list' => true,
    ),
	'sandopen_mobile' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('b2c')->_('杉德开户手机'),
      'width' => 75,
      'editable' => false,
	  'searchtype' => 'head',
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),

   'createtime' =>
	array (
      'type' => 'time',
	  'label' => '创建时间',
      'editable' => false,
	  'filtertype' => 'time',
      'filterdefault' => true,
	  'in_list' => true,
      'default_in_list' => true,
    ),
	'order_time' =>
	array (
      'type' => 'time',
	  'label' => '订单创建时间',
      'editable' => false,
	  'filtertype' => 'time',
      'filterdefault' => true,
	  'in_list' => true,
      'default_in_list' => true,
    ),
	'pay_amount' =>
    array (
      'type' => 'money',
	  'editable' => false,
      'label' => '订单金额',
	  'in_list' => true,
      'default_in_list' => true,
    ),
	'counter_fee' =>
    array (
      'type' => 'money',
	  'editable' => false,
      'label' => '手续费',
	  'in_list' => true,
      'default_in_list' => true,
    ),
   'recharge_time' =>
	 array (
	  'type' => 'time',
	  'label' => '充值时间',
      'editable' => false,
	  'filtertype' => 'time',
      'filterdefault' => true,
	  'in_list' => true,
      'default_in_list' => true,
    ),
   'status' =>
   array(
	 'type'=> array (
      '0'=>'未充值',
	  '1'=>'充值成功',
	  '2'=>'充值失败',
		),
	  'label'=>'状态',
	  'default' => '0',
      'editable' => false,
	  'in_list' => true,
      'default_in_list' => true,
	),
	'operator' =>
	 array (
      'type' => 'varchar(100)',
      'editable' => false,
	  'label' => '操作人',
	  'in_list' => true,
      'default_in_list' => true,
    ),
	'mark_text' => 
	array (
	  'type' => 'longtext',
	  'label' => '充值备注',
	  'width' => 50,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'in_list' => true,
	),
 ),
	'index'=>array(
        'ind_source' =>
            array (
                'columns' =>
                    array (
                        0 => 'order_id',
                    ),
            ),
    ),
	'engine' => 'innodb',
);