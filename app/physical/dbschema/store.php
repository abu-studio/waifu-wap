<?php 
$db['store']=array (
	'columns' =>
	array (
		'store_id' =>
		array (
			'type' => 'int(11)',
			'required' => true,
			'pkey' => true,
			'extra' => 'auto_increment',
			'label' => app::get('physical')->_('门店ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => false,
			'default_in_list' => false,
		),
		'store_name' =>
		array (
			'type' => 'varchar(200)',
			'required' => true,
			'label' => app::get('physical')->_('门店名称'),
			'width' => 350,
			'editable' => true,
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
			'order'=>10,
		),
		'store_code' =>
		array (
			'type' => 'varchar(50)',
			'required' => true,
			'label' => app::get('physical')->_('门店编号'),
			'width' => 150,
			'editable' => true,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'has',
			'searchtype' => 'has',
			'order'=>20,
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
			'order'=>30,
		),
		'organization_id' =>
		array (
			'type' => 'table:organization',
			'required' => true,
			'label' => app::get('physical')->_('机构ID'),
			'width' => 100,
			'editable' => false,
			'in_list' => true,
			'default_in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
			'order'=>40,
		),
		'image' =>
			array (
			  'type' => 'varchar(255)',
			  'label' => app::get('physical')->_('图片'),
			  'width' => 75,
			  'hidden' => true,
			  'editable' => false,
			  'in_list' => false,
			  'order'=>50,
			),
		'url' =>
			array (
			  'type' => 'varchar(255)',
			  'label' => app::get('physical')->_('链接'),
			  'width' => 75,
			  'hidden' => true,
			  'editable' => false,
			  'in_list' => false,
			  'order'=>50,
			),
		'area' => 
		array (
			'type' => 'region',
			'required' => true,
			'label' => app::get('physical')->_('地区'),
			'width' => 150,
			'editable' => false,
			'filtertype' => 'yes',
			'filterdefault' => true,
			'in_list' => true,
			'default_in_list' => false,
			'order'=>60,
		),
		'address' => 
		array (
			'type' => 'varchar(255)',
			'label' => app::get('physical')->_('详细地址'),
			'width' => 200,
			'editable' => true,
			'filtertype' => 'normal',
			'in_list' => true,
			'default_in_list' => true,
			'order'=>70,
		),
		'mobile' => 
		array (
		  'type' => 'varchar(12)',
		  'label' => app::get('physical')->_('手机号码'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>80,
		), 
		'phone' => 
		array (
		  'type' => 'varchar(30)',
		  'label' => app::get('physical')->_('座机号码'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>90,
		),   
		'email' => 
		array (
		  'type' => 'email',
		  'label' => app::get('physical')->_('电子邮箱'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>100,
		), 
		'fax' => 
		array (
		  'type' => 'varchar(30)',
		  'label' => app::get('physical')->_('传真号码'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>110,
		),
		'postcode' => 
		array (
		  'type' => 'varchar(7)',
		  'label' => app::get('physical')->_('邮政编码'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>120,
		), 
		'open' => 
		array (
		  'type' => 'varchar(6)',
		  'label' => app::get('physical')->_('开始营业时间'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>130,
		),
		'close' => 
		array (
		  'type' => 'varchar(6)',
		  'label' => app::get('physical')->_('结束营业时间'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>140,
		),
		'longitude' => 
		array (
		  'type' => 'decimal(10,6)',
		  'label' => app::get('physical')->_('地理经度'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>150,
		),
		'latitude' => 
		array (
		  'type' => 'decimal(10,6)',
		  'label' => app::get('physical')->_('地理纬度'),
		  'width' => 150,
		  'editable' => true,
		  'filtertype' => 'normal',
		  'filterdefault' => true,
		  'in_list' => true,
		  'default_in_list' => true,
		  'order'=>160,
		),
		'bus_lines' => 
		array (
			'type' => 'text',
			'label' => app::get('physical')->_('公交线路'),
			'width' => 200,
			'editable' => true,
			'filtertype' => 'normal',
			'in_list' => true,
			'default_in_list' => true,
			'order'=>170,
		),
		'subway_lines' => 
		array (
			'type' => 'text',
			'label' => app::get('physical')->_('地铁线路'),
			'width' => 200,
			'editable' => true,
			'filtertype' => 'normal',
			'in_list' => true,
			'default_in_list' => true,
			'order'=>180,
		),
		'introduction' => 
		array (
			'type' => 'text',
			'label' => app::get('physical')->_('门店简介'),
			'width' => 200,
			'editable' => true,
			'filtertype' => 'normal',
			'in_list' => false,
			'default_in_list' => false,
			'order'=>190,
		),
		'status' => 
		array (
			'type' =>array(
				'1'=>'营业',
				'2'=>'装修',
				'3'=>'整顿',
				'4'=>'闭店',
			),
			'default'=>'1',
			'label' => app::get('physical')->_('门店状态'),
			'width' => 200,
			'editable' => true,
			'filtertype' => 'normal',
			'in_list' => false,
			'default_in_list' => false,
			'order'=>190,
			'required' => true,
		),
		'weekday' => 
		array (
		  'type' => 'varchar(15)',
		  'label' => app::get('physical')->_('工作日'),
		  'width' => 150,
		  'editable' => true,
		  'order'=>200,
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
			  'order'=>220,
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
			  'order'=>230,
		),
	),
	'index'=>array(
		'ind_store_code'=>
		array(
			'columns'=>array(
				0=>'store_code',
			),
		),
		'ind_mobile'=>
		array(
			'columns'=>array(
				0=>'mobile',
			),
		),
		'ind_email'=>
		array(
			'columns'=>array(
				0=>'email',
			),
		),
	),
  'engine' => 'innodb',
	'comment' => app::get('physical')->_('门店表'),
	'version' => '$Rev: 50514 $',
);
