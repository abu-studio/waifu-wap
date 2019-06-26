<?php
$db['columntype']=
    array (
       'columns' =>
       array (
          'columntype_id' =>
          array (
             'type' => 'number',
             'required' => true,
             'extra' => 'auto_increment',
             'pkey' => true,
             'label'=>'序号',
             'filtertype'=>true,
              'searchtype'=>true,
             ),
          'columntype_name' =>
          array (
             'type' => 'varchar(100)',
             'in_list'=>true,
             'is_title'=>true,
             'default_in_list'=>true,
             'label'=>'栏目名称',
             'filtertype'=>true,
             'is_title'=>true,
             'searchtype'=>true,
             'searchtype' => 'has',
             'required' => true,
             ),
          'columntype_description' =>
          array (
             'type' => 'varchar(100)',
             'in_list'=>true,
             'default_in_list'=>true,
             'label'=>'栏目描述',
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
          'columntype_createtime' =>
          array (
             'in_list'=>true,
             'default_in_list' => true,
             'label' => '录入时间',
             'type' => 'time',
			'required' => true,
             ),
         'cat_id' =>
          array (
             'type' => 'number',
             'in_list'=>false,
             'default_in_list'=>false,
             'required' => true,
             'default'=>1,
             ),
		'type' =>
		array (
		  'type' => array(
					"concentration"=>__('精选'),
					"column"=>__('楼层'),
				),
		  'label' => app::get('cellphone')->_('类型'),
		  'width' => 150,
		  'editable' => false,
		),
		'image_id' =>
		  array (
			 'type' => 'varchar(100)',
			 'in_list'=>false,
			 'default_in_list'=>false,
			 'label'=>'图片',
			 
			 ),

          ),
       );


?>