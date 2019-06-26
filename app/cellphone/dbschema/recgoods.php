<?php
$db['recgoods']=
    array (
       'columns' =>
       array (
          'id' =>
          array (
             'type' => 'number',
             'required' => true,
             'extra' => 'auto_increment',
             'pkey' => true,
			 'label'=>'序号',
			 'comment' => app::get('cellphone')->_('序号'),
             'editable' => false,
             'in_list' => false,
             'default_in_list' => false,
             ),
         'goods_id'=>
      array (
       'type' => 'table:goods@b2c',
       'label' => app::get('b2c')->_('商品名'),
       'width' => 110,
	   'editable' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
       ),
			'd_order' =>
       array (
      'type' => 'number',
      'default' => 1,
      'required' => true,
      'label' => app::get('b2c')->_('排序'),
      'width' => 50,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => false,
        ),

        'is_active' => array(
            'type'=>'bool',
            'label'=>__('开启状态'),
            'default'=>'false',
            'editable'=>false,
			 'in_list' => true,
             'default_in_list' => true,
        ),
        'disabled'=>array(
			'type'=>'bool',
			'default'=>'false',
		),
      
          ),
       );


?>