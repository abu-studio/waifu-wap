<?php
$db['attendactivity'] = array(
    'columns'=>array(
        'id'=>array(
            'type'=>'mediumint(8)',
            'extra'=>'auto_increment',
            'pkey'=>'true',
            'label'=>__('序号'),
            'in_list'=>true,
        ),
        'name'=>array (
            'type' => 'varchar(255)',
            'required'=>true,
            'default'=>'null',
            'label'=>__('捆绑销售名称'),
            'in_list' => true,
            'default_in_list' => true,
            'editable' => true,
            'fuzzySearch' => 1,
        ),
        'gid'=>array(
            'type'=>'varchar(255)',
            'required'=>true,
            'label'=>__('活动商品名称'),
            'editable'=>false,
            'locked' => 1,
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'aid'=>array(
            'type'=>'table:activity@package',
            'required'=>true,
            'label'=>__('所属活动'),
            'editable'=>false,
            'locked' => 1,
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'member_id'=>array(
            'type'=>'table:account@pam',
            'required'=>true,
            'label'=>__('申请人'),
            'editable'=>false,
            'locked' => 1,
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'store_id'=>array(
            'type'=>'table:storemanger@business',
            'required'=>true,
            'label'=>__('申请店铺'),
            'editable'=>false,
            'locked' => 1,
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'amount'=>array(
            'type'=>'money',
            'default'=>0,
            'label'=>__('价格'),
            'editable'=>false,
            'hidden'=>true,
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'store' => array(
            'type'=>'mediumint(8)',
            'label'=>__('参加活动的商品数量'),
            'default'=>'0',
            'editable'=>false,
            'filtertype'=>'number',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'freez'=>array(
            'type'=>'mediumint(8)',
            'label'=>__('参加活动的冻结数量'),
            'default'=>'0',
            'editable'=>false,
            'filtertype'=>'number',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'presonlimit'=>array(
            'type'=>'mediumint(8)',
            'label'=>__('每人限购'),
            'editable'=>false,
            'filtertype'=>'number',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'weight'=>array(
            'type'=>'varchar(10)',
            'default'=>'0',
            'label'=>__('重量'),
            'editable' => true,
            'in_list' => true,
            'default_in_list'=>true,
        ),
        'score'=>array(
            'type'=>'float unsigned',// number->float unsigned
            'default'=>'0',
            'label'=>__('积分'),
            'editable' => true,
            'in_list' => true,
        ),
        'status'=>array(
            'type'=>array(
                1=>__('待审核'),
                2=>__('审核通过'),
                3=>__('审核不通过'),
            ),
            'default'=>'1',
            'label'=>__('活动状态'),
            'editable'=>false,
            'in_list'=>true,
            'default_in_list' => true,
        ),
        'buy_count'=>array (
            'type' => 'int unsigned',
            'default' => 0,
            'label'=>__('总销量'),
            'required' => true,
            'editable' => false,
        ),
        'intro'=>array(
           'type'=>'longtext',
           'label'=>__('描述'),
           'editable'=>false,
           'in_list'=>true,
           'default_in_list' => true,
        ),
        'image'=>array(
            'type'=>'varchar(32)',
            'default'=>'',
            'label'=>__('图片'),
        ),
        'last_midifity'=>array(
            'type' => 'time',
            'label'=>__('最后修改时间'),
            'editable' => false,
            'required' => false
        ),
        'remark'=>array(
           'type'=>'longtext',
           'label'=>__('备注'),
           'editable'=>false,
           'in_list'=>true,
           'default_in_list' => true,
        ),
        'freight_bear'=>array (
          'type' => array (
            'business' => '商家',
            'member' => '会员',
          ),
          'required' => true,
          'default' => 'member',
          'label' => '运费承担',
          'width' => 110,
          'editable' => false,
          'in_list' => true,
          'default_in_list' => true,
        ),
    ),

);