<?php 

 
/**
* @table member_fyw;
*
* @package Schemas
* @version $
* @license Commercial
*/

$db['member_fyw']=array (
  'columns' => 
  array (
    'member_fyw_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'extra' => 'auto_increment',
      'pkey'=>true,
      'label' => app::get('b2c')->_('序号'),
      'editable' => false,
    ),
    'member_no' =>
    array (
        'type' => 'varchar(100)',
        'required' => true,
        'default' => '',
        'label' => app::get('b2c')->_('会员号'),
        'width' => 120,
        'editable' => false,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
        'orderby'=>true,
    ),
    'member_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'certificate_type' =>
    array (
      'type' => 
      array (
        '0' => app::get('b2c')->_('身份证'),
        '1' => app::get('b2c')->_('护照'),
        '2' => app::get('b2c')->_('军官证'),
        '3' => app::get('b2c')->_('士兵证'),
        '4' => app::get('b2c')->_('回乡证'),
        '5' => app::get('b2c')->_('临时身份证'),
        '6' => app::get('b2c')->_('户口簿'),
        '7' => app::get('b2c')->_('警官证'),
        '8' => app::get('b2c')->_('台胞证'),
        '9' => app::get('b2c')->_('营业执照'),
        '10' => app::get('b2c')->_('其它证件'),
      ),
      'default' => '0',
      'label' => app::get('b2c')->_('证件类型'),
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'certificate_no' =>
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'default' => '',
      'label' => app::get('b2c')->_('证件号'),
      'width' => 180,
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'member_name' =>
    array (
        'type' => 'varchar(100)',
        'required' => true,
        'default' => '',
        'label' => app::get('b2c')->_('姓名'),
        'width' => 80,
        'editable' => false,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'mobile_no' =>
    array (
        'type' => 'varchar(11)',
        'required' => true,
        'default' => '',
        'label' => app::get('b2c')->_('手机号码'),
        'width' => 80,
        'editable' => false,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'company_name' =>
    array (
        'type' => 'varchar(100)',
        'required' => true,
        'default' => '',
        'label' => app::get('b2c')->_('公司名'),
        'width' => 150,
        'editable' => false,
        'filtertype' => 'normal',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'company_code' =>
    array (
        'type' => 'varchar(100)',
        'required' => true,
        'default' => '',
        'label' => app::get('b2c')->_('商社代码'),
        'width' => 80,
        'editable' => false,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'region' =>
    array (
        'type' => 'varchar(100)',
        'required' => true,
        'default' => '',
        'label' => app::get('b2c')->_('地区'),
        'width' => 80,
        'editable' => false,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'status' =>
    array (
        'type' =>
        array (
            'WORK' => app::get('b2c')->_('正常'),
            'STOP' => app::get('b2c')->_('停用'),
            'LEAVE' => app::get('b2c')->_('离职'),
            'RET' => app::get('b2c')->_('退休'),
        ),
        'default' => 'WORK',
        'label' => app::get('b2c')->_('用户状态'),
        'required' => true,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'sync_type' =>
        array (
        'type' => array (
            'ADD' => app::get('b2c')->_('新增'),
            'UPDATE' => app::get('b2c')->_('修改'),
        ),
        'default' => 'ADD',
        'required' => true,
        'label' => app::get('b2c')->_('同步类别'),
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        ),
    'issync' =>
    array (
      'type' =>
      array (
        '0' => app::get('b2c')->_('未同步'),
        '1' => app::get('b2c')->_('已同步'),
      ),
      'default' => '0',
      'required' => true,
      'label' => app::get('b2c')->_('同步状态'),
      'editable' => false,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'createtime' =>
        array (
        'type' => 'time',
        'label' => app::get('b2c')->_('创建时间'),
        'width' => 60,
        'editable' => false,
        'filtertype' => 'time',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
        ),
    'last_modified' =>
    array (
        'label' => app::get('b2c')->_('更新时间'),
        'type' => 'last_modify',
        'width' => 110,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'orderby'=>true,
    ),

  ),
  'index' =>
  array (
    'ind_member_id' =>
      array (
          'columns' =>
              array (
                  0 => 'member_id',
              ),
      ),
    'ind_certificate_no' =>
    array (
      'columns' =>
      array (
        0 => 'certificate_no',
      ),
    ),
  ),
  'comment' => app::get('b2c')->_('福员外用户表'),
  'engine' => 'innodb',
  'version' => '$Rev: 51321 $'
);
