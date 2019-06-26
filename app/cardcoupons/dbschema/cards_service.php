<?php 

$db['cards_service']=array (
  'columns' => 
  array (
    'card_service_id' => 
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
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('名称'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
	  'searchtype' => 'has',
	  'filtertype' => 'has',
      'filterdefault' => true,
    ),
    'handle' => 
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => app::get('cardcoupons')->_('句柄值'),
	  'default_in_list' => true,
      'editable' => false,
      'in_list' => true,
    ),
    'cardkind' =>
        array (
            'type' =>array(
				'01'=>'悠福礼品卡-01',
				'02'=>'悠福体检卡-02',
				'03'=>'悠福复合卡-03',
			),
            'required' => true,
            'label' => app::get('cardcoupons')->_('卡券类型'),
            'default_in_list' => true,
            'editable' => false,
            'in_list' => true,
			'filterdefault' => true,
			'filtertype' => 'yes',
        ),
      'memo' =>
    array (
      'type' => 'varchar(255)',
      'label' => '备注',
	  'default' => '',
      'width' => 110,
      'hidden' => false,
      'editable' => false,
      'in_list' => true,
    ),
	'createtime' => 
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('创建时间'),
      'editable' => false,
      'in_list' => true,
    ),
	'lasttime' => 
    array (
      'type' =>'time',
      'required' => true,
      'label' => app::get('cardcoupons')->_('最后更新时间'),
      'editable' => false,
      'in_list' => true,
    ),
   ),

  'engine' => 'innodb',
  'version' => '$Rev: 43884 $',
);
