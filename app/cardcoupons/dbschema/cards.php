<?php 
 
 
$db['cards']=array (
  'columns' => 
  array (
    'card_id' => 
    array (
      'type' => 'bigint unsigned',
      'extra' => 'auto_increment',
      'pkey' => true,
      'label' => 'ID',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'name' => 
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      'label' => '卡券名称',
      'is_title' => true,
      'width' => 310,
      'searchtype' => 'has',
      'editable' => true,
      'filtertype' => 'has',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'order' => '1',
    ),
    'goods_id' => 
    array (
      'type' => 'table:goods@b2c',
      'required' => true,
	  'hidden'	=>true,
    ),
  'type_id' =>
  array (
      'type' => 'varchar(255)',
      'required' => true,
      'editable' => true,
      'default'	 =>'01'
    ),
	'service_id' =>
    array (
      'type' => 'table:cards_service',
      'editable' => true,
	  'filtertype' => 'yes',
      'in_list' => true,
	  'label'=>'卡券名目',
      'default_in_list' => true,
    ),
    'change_way' =>
    array(
        'type' =>
            array(
                'num'=>'选产品个数',
                'price'=>'按价格设置',
            ),
        'label'=>'兑换方式',
        'filtertype' => 'yes',
        'default' => 'num',
        'required' => true,
        'editable' => true,
    ),
    'change_mode' =>
          array(
              'type' =>
                  array(
                      'once'=>'一次性',
                      'many'=>'多种多套',
                  ),
              'label'=>'选取模式',
              'filtertype' => 'yes',
              'default' => 'once',
              'required' => true,
              'editable' => true,
          ),
	'source' => 
    array (
      'type' =>
		array(
			'internal'=>'内部卡',
			'external'=>'外部卡',
		),
	 'label'=>'来源',
	 'filtertype' => 'yes',
	 'filterdefault' => true,
	 'default' => 'external',
     'required' => true,
	 'hidden'	=>true,
     'editable' => true,
    ),
	'rules' => 
    array (
      'type' => 'varchar(32)',
	  'default' => '1',
      'editable' => true,
    ),
	'solution' => 
    array (
      'type' => 'serialize',
	  'default' => '',
      'editable' => true,
    ),
	'logo' => 
    array (
      'type' => 'varchar(32)',
      'label' => 'logo',
	  'default' => '',
      'width' => 75,
      'hidden' => true,
      'editable' => true,
      'in_list' => false,
    ),
	'style' => 
    array (
      'type' => 'serialize',
      'label' => '背景样式',
      'hidden' => true,
      'editable' => true,
      'in_list' => false,
    ),
	'passset' => 
    array (
      'type' => 
		array(
			'auto'=>'自动',
			'manual'=>'手动',
			'ftp'=>'文件上传',
		),
	  'filtertype' => 'yes',
	  'filterdefault' => true,
	  'default' => 'auto',
      'label' => '卡密创建方式',
    ),
	'memo' => 
    array (
      'type' => 'varchar(255)',
      'label' => '备注',
      'width' => 110,
      'hidden' => false,
      'editable' => true,
      'in_list' => true,
    ),
	'from_time' => 
    array (
      'type' =>'time',
	  'filtertype' => 'time',
      'label' => app::get('cardcoupons')->_('开始时间'),
      'editable' => true,
    ),
	'to_time' => 
    array (
      'type' =>'time',
	  'filtertype' => 'time',
      'label' => app::get('cardcoupons')->_('结束时间'),
      'editable' => true,
      
    ),
    'message' =>
    array(
        'type' => 'varchar(255)',
        'label' => app::get('cardcoupons')->_('提示语'),
        'editable' => true,
    ),
	'msg_templet' =>
    array(
        'type' => 'varchar(255)',
        'label' => app::get('cardcoupons')->_('短信模板'),
        'editable' => true,
    ),
	'default_type' => 
    array (
      'type' => 
	  array(
			'0'=>'无',
			'1'=>'积分',
			'2'=>'商品',
		),
	  'label' => app::get('cardcoupons')->_('默认发放方式'),
      'required' => true,
	  'default'	=>1,
    ),
	'default_score' => 
    array (
      'type' => 'number',
	  'label' => app::get('cardcoupons')->_('默认发放积分'),
      'required' => true,
	  'default'	=>0,
    ),
	'default_goods_id' => 
    array (
      'type' => 'bigint unsigned',
	  'label' => app::get('cardcoupons')->_('默认发放商品'),
      'required' => true,
	  'default'	=>0,
    ),
	'default_goods_num' => 
    array (
      'type' => 'number',
	  'label' => app::get('cardcoupons')->_('默认发放商品数量'),
      'required' => true,
	  'default'	=>0,
    ),
	'createtime' => 
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('创建时间'),
      'editable' => true,
      'in_list' => true,
	  'filterdefault' => true,
	  'orderby' =>true,
    ),
	'lasttime' => 
    array (
      'type' =>'last_modify',
      'required' => true,
      'label' => app::get('cardcoupons')->_('最后更新时间'),
      'editable' => true,
      'in_list' => true,
	  'orderby' =>true,
    ),
   ),
   'index'=>array(
	'ind_source' => 
    array (
      'columns' => 
      array (
        0 => 'source',
      ),
    ),
   ),
  'comment' => app::get('cardcoupons')->_('卡券原型表'),
  'engine' => 'innodb',
  'version' => '$Rev: 43884 $',
);
