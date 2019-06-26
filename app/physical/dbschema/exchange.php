<?php 
$db['exchange']=array (
	'columns' =>
	array (
		'id' =>
		array (
			'type' => 'bigint unsigned',
			'required' => true,
			'pkey' => true,
			'extra' => 'auto_increment',
			'label' => app::get('physical')->_('预约单ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => false,
			'default_in_list' => false,
		),
		'goods_id' =>
		array (
			'type' => 'bigint unsigned',
			'required' => true,
			'label' => app::get('physical')->_('商品ID'),
			'width' => 100,
			'hidden' => true,
			'editable' => false,
			'in_list' => false,
			'order'=>10,
		),
		'order_id' => 
			array (
			  'type' => 'bigint unsigned',
			  'label' => app::get('physical')->_('订单号'),
			  'default' => 0,
			  'width' => 110,
			  'editable' => false,
			  'in_list' => false,
			  'default_in_list' => false,
			),
		'card_pass_id' =>
		array (
			'type' => 'bigint unsigned',
			'label' => app::get('physical')->_('卡密ID'),
			'width' => 200,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>10,
		),
		'package_id' =>
		array (
			'type' => 'table:package',
			'required' => true,
			'label' => app::get('physical')->_('所选套餐'),
			'width' => 100,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>20,
		),
		'project_ids' =>
		array (
			'type' => 'varchar(255)',
			'label' => app::get('physical')->_('所选增加体检项目'),
			'width' => 150,
			'editable' => true,
			'in_list' => false,
			'default_in_list' => false,
			'order'=>30,
		),
		'type' =>
		array (
			'type' => array (
				1 => app::get('physical')->_('体检'),
			),
			'required' => true,
			'label' => app::get('physical')->_('服务类型'),
			'width' => 150,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>40,
		),
		'store_id' =>
		array (
			'type' => 'table:store',
			'label' => app::get('physical')->_('门店ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => false,
			'default_in_list' => false,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>50,
		),
		'store_name' =>
		array (
			'type' => 'varchar(200)',
			'label' => app::get('physical')->_('门店名称'),
			'width' => 350,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'custom',
			'filtercustom' => 
			array (
				'has' => '包含',
				'tequal' => '等于',
				'head' => '开头等于',
				'foot' => '结尾等于',
			),
			'searchtype' => 'has',
			'order'=>60,
		),
		'member_id' => 
		array (
		  'type' => 'number',
		  'label' => app::get('physical')->_('会员ID'),
		  'width' => 100,
		  'editable' => false,
		  'filtertype' => 'yes',
		  'filterdefault' => true,
		  'in_list' => false,
		  'default_in_list' => false,
		  'order'=>70,
		),
		'person_name' =>
		array (
			'type' => 'varchar(20)',
			'label' => app::get('physical')->_('体检人'),
			'width' => 100,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'custom',
			'filtercustom' => 
			array (
				'has' => '包含',
				'tequal' => '等于',
				'head' => '开头等于',
				'foot' => '结尾等于',
			),
			'searchtype' => 'has',
			'order'=>80,
		),
		'sex' =>  array(
			'type' =>
			  array (
				1 => app::get('b2c')->_('男'),
				2 => app::get('b2c')->_('女'),
			  ),
			'label' => app::get('physical')->_('性别'),
			'width' => 100,
			'editable' => false,
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
			'default_in_list' => true,
			'order'=>90,
		),
		'marry' =>  array(
			'type' => array (
				1 => app::get('physical')->_('是'),
				2 => app::get('physical')->_('否'),
			),
			'label' => app::get('physical')->_('是否已婚'),
			'width' => 100,
			'editable' => false,
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
			'default_in_list' => true,
			'order'=>100,
		),
		'age' =>  array(
			'type' => 'number',
			'label' => app::get('physical')->_('年龄'),
			'width' => 100,
			'editable' => false,
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
			'default_in_list' => true,
			'order'=>110,
		),
		'c_type' =>  array(
			'type' => array (
				1 => app::get('physical')->_('身份证'),
				2 => app::get('physical')->_('军官证'),
				3 => app::get('physical')->_('团员证'),
			),
			'label' => app::get('physical')->_('证件类型'),
			'width' => 100,
			'editable' => false,
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
			'default_in_list' => true,
			'order'=>120,
		),
		'c_no' =>  array(
			'type' => 'varchar(255)',
			'label' => app::get('physical')->_('证件号码'),
			'width' => 100,
			'editable' => false,
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
			'default_in_list' => true,
			'order'=>130,
		),
		'mobile' => 
		array (
		  'type' => 'varchar(12)',
		  'label' => app::get('physical')->_('联系手机'),
		  'width' => 150,
		  
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>140,
		), 
		'address' => 
		array (
			'type' => 'varchar(255)',
			'label' => app::get('physical')->_('联系地址'),
			'width' => 200,
			
			'filtertype' => 'normal',
			'in_list' => true,
			'default_in_list' => true,
			'order'=>150,
		),
		'order_times' =>  array(
			'type' => 'varchar(255)',
			  'label' => app::get('physical')->_('预约时间'),
			  'width' => 150,
			  
			  'filtertype' => 'normal',
			  'filterdefault' => true,
			  'order'=>160,
		),
		'create_time' =>  array(
			'type' => 'time',
			'required' => true,
			  'label' => app::get('physical')->_('创建时间'),
			  'width' => 150,
			  
			  'filtertype' => 'normal',
			  'filterdefault' => true,
			  'in_list' => true,
			  'default_in_list' => true,
			  'order'=>200,
		),
		'update_time' =>  array(
			'type' => 'time',
			'required' => true,
			  'label' => app::get('physical')->_('最后更新时间'),
			  'width' => 150,
			  
			  'filtertype' => 'normal',
			  'filterdefault' => true,
			  'in_list' => true,
			  'default_in_list' => true,
			  'order'=>210,
		),
	),
	'index'=>array(
		'ind_order_id'=>
		array(
			'columns'=>array(
				0=>'order_id',
			),
		),
		'ind_member_id'=>
		array(
			'columns'=>array(
				0=>'member_id',
			),
		),
	),
  'engine' => 'innodb',
	'comment' => app::get('physical')->_('体检兑换流程临时信息表'),
	'version' => '$Rev: 50514 $',
);
