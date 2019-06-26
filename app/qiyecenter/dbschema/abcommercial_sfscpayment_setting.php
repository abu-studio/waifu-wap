<?php
$db['abcommercial_sfscpayment_setting']=array (
  'columns' => 
  array (
    'id' =>
        array (
          'type' => 'number',
          'required' => true,
          'pkey' => true,
          'extra' => 'auto_increment',
          'label' => 'ID',
          'width' => 110,
          'editable' => false,
          'in_list' => false,
          'default_in_list' => false,
        ),
    'm_id' =>
      array (
          'type' => 'number',
          'is_title' => true,
          'required' => true,
          'label' => app::get('b2c')->_('用户会员id'),
          'width' => 110,
          'editable' => true,
          'in_list' => true,
          'default_in_list' => true,
      ),
      'target_type' => array (
          'required' => true,
          'label' => app::get('b2c')->_('关联对象类型'),
          'width' => 75,
          'type' => 'varchar(10)',
          'comment' => '商社：I00102 部门：I00103 群组：I00104',
          'editable' => true,
          'filtertype' => 'bool',
          'filterdefault' => 'true',
          'in_list' => true,
          'default_in_list' => false,
      ),
      'target_id' =>
        array (
          'required' => true,
          'label' => app::get('b2c')->_('关联对象id'),
          'width' => 75,
          'type' => 'varchar(50)',
          'editable' => true,
          'filtertype' => 'bool',
          'filterdefault' => 'true',
          'in_list' => true,
          'default_in_list' => false,
        ),
  ),
  'index' => array(
    'ind_m_target_id' => array(
        'columns' => array(
            0 => 'm_id',
            1 => 'target_type',
        )
    )
  ),
  'comment' => app::get('b2c')->_('默认支付配置表'),
  'engine' => 'innodb',
  'version' => '$Rev: 445231 $',
);
