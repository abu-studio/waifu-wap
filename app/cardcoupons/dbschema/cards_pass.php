<?php


$db['cards_pass']=array (
  'columns' =>
  array (
    'card_pass_id' =>
    array (
      'type' => 'bigint unsigned',
      'extra' => 'auto_increment',
      'pkey' => true,
      'label' => 'ID',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
	  'filtertype' => 'number',
      'filterdefault' => true,
    ),
    'card_name' =>
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      'label' => '卡券名称',
      'is_title' => true,
      'width' => 310,
      'searchtype' => 'has',
      'editable' => true,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'filtercustom' =>
      array (
        'has' => '包含',
        'tequal' => '等于',
        'head' => '开头等于',
        'foot' => '结尾等于',
      ),
      'in_list' => true,
      'default_in_list' => true,
      'order' => '1',
    ),
    'card_id' =>
    array (
      'type' => 'table:cards',
	  'label'=>'关联卡券',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
      'editable' => false,
      'in_list' => true,
    ),
	 'batch' =>
    array (
      'type' => 'varchar(50)',
	  'label'=>'批次',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
      'editable' => false,
      'in_list' => true,
	  'searchtype' => 'has',
	  'default_in_list' => true,
    ),
	'card_no' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('卡券编号'),
      'in_list' => true,
      'default_in_list' => true,
	  'searchtype' => 'has',
      'editable' => true,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'filtercustom' =>
      array (
        'has' => '包含',
        'tequal' => '等于',
        'head' => '开头等于',
        'foot' => '结尾等于',
      ),
    ),
	'card_code' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('cardcoupons')->_('卡券校验码'),
      'editable' => false,
    ),
	'card_pass' =>
    array (
      'type' => 'varchar(256)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('卡密'),
	  'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'filtercustom' =>
      array (
        'has' => '包含',
        'tequal' => '等于',
        'head' => '开头等于',
        'foot' => '结尾等于',
      ),
    ),
	'status' =>
    array (
      'type' =>
		array(
			'-1'=>'已预售',
			'0'=>'未发放',
			'1'=>'已发放',
            '2'=>'已激活',
            '3'=>'已使用',
            '4'=>'已结算',
			'5'=>'已冻结',
            '9'=>'部分兑换'
		),
	  'filtertype' => 'yes',
	  'default'=>'0',
      'filterdefault' => true,
      'required' => true,
      'label' => app::get('cardcoupons')->_('状态'),
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'type' =>
    array (
      'type' =>
		array(
			'entity'=>'实体卡',
			'virtual'=>'电子码',
		),
	  'filtertype' => 'yes',
	  'default'=>'entity',
      'filterdefault' => true,
      'required' => true,
      'label' => app::get('cardcoupons')->_('类型'),
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'source' =>
    array (
      'type' =>
		array(
			'internal'=>'内部卡',
			'external'=>'外部卡',
		),
		'label'=>'来源',
		'in_list' => true,
		'default'=>'internal',
		'required' => true,
    ),
	'disabled' =>
    array (
      'type' =>
		array(
			'false'=>'未失效',
			'true'=>'已失效',
		),
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
      'default' => 'false',
      'label' => app::get('cardcoupons')->_('有效性'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'ex_status' =>
    array (
      'type' =>
		array(
			'update'=>'待审核',
			'false'=>'审核失败',
			'true'=>'审核成功',
		),
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
	  'default'=>'update',
      'label' => app::get('cardcoupons')->_('审核状态'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'passset' =>
    array (
      'type' =>
		array(
			'auto'=>'自动',
			'manual'=>'手动',
			'ftp'=>'文件',
		),
	  'filterdefault' => true,
	  'default' => 'auto',
      'label' => '创建方式',
      'editable' => true,
      'in_list' => true,
    ),
	'from_time' =>
    array (
      'type' =>'time',
      'required' => true,
	  'filtertype' => 'time',
      'label' => app::get('cardcoupons')->_('开始时间'),
      'editable' => true,
      'in_list' => true,
	  'filterdefault' => true,
    ),
	'to_time' =>
    array (
      'type' =>'time',
      'required' => true,
	  'filtertype' => 'time',
      'label' => app::get('cardcoupons')->_('结束时间'),
      'editable' => true,
      'in_list' => true,
	  'filterdefault' => true,
    ),
	'is_send' =>
    array (
      'type' =>
		array(
			'true'=>'是',
			'false'=>'否',
		),
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'required' => true,
      'default' => 'false',
      'label' => app::get('cardcoupons')->_('是否发放默认产品'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'lasttime' =>
    array (
      'type' =>'last_modify',
      'label' => app::get('cardcoupons')->_('最后更新时间'),
      'in_list' => true,
      'orderby' =>true,
    ),
	'createtime' =>
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('创建时间'),
      'editable' => false,
      'in_list' => true,
	  'orderby' =>true,
    ),
	'use_time' =>
    array (
      'type' =>'time',
	  'filtertype' => 'time',
      'label' => app::get('cardcoupons')->_('使用时间'),
      'editable' => false,
      'in_list' => true,
    ),
	'loginlog' =>
    array (
      'type' =>'serialize',
      'label' => app::get('cardcoupons')->_('登录日志'),
      'in_list' => true,
    ),

	'order_id' =>
    array (
      'type' => 'bigint unsigned',
      'default' => 0,
	  'label' => app::get('cardcoupons')->_('来源订单'),
      'editable' => true,
    ),
	'exchange_prefix' =>
    array (
      'type' =>'varchar(12)',
      'label' => app::get('cardcoupons')->_('兑换订单前缀'),
      'editable' => false,
    ),
	'exchange_no' =>
    array (
      'type' => 'varchar(255)',
      'default' => 0,
	  'label' => app::get('cardcoupons')->_('兑换订单'),
      'editable' => true,
    ),
    'exchange_order_id' =>
        array (
          'type' => 'serialize',
          'default' => '',
          'editable' => true,
    ),
   'exchange_num' =>
       array (
              'type' => 'varchar(10)',
              'label' => app::get('cardcoupons')->_('已兑换数量'),
              'default' => '0',
              'editable' => true,
          ),
   ),
  'index'=>array(
	'ind_card_no'=>
	array(
	   'columns'=>
	    array(
			0=>'card_no',
		),
	),
	'ind_card_pass'=>
	array(
	   'columns'=>
	   array(
		   0=>'card_pass',
	  ),
	),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 43884 $',
);
