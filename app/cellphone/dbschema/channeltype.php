<?php
$db['channeltype']=
    array (
       'columns' =>
       array (
          'type_id' =>
          array (
             'type' => 'number',
             'required' => true,
             'extra' => 'auto_increment',
             'pkey' => true,
             'label'=>'序号',
             'filtertype'=>true,
              'searchtype'=>true,
             ),
          'type_name' =>
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
           
            'image_id' =>
          array (
             'type' => 'varchar(100)',
             'in_list'=>false,
             'default_in_list'=>false,
             'label'=>'图片',
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
       
       'createtime' =>
          array (
             'in_list'=>true,
             'default_in_list' => true,
             'label' => '录入时间',
             'type' => 'time',
			'required' => true,
             ),

          ),
       );


?>