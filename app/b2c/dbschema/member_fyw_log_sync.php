<?php 

 
/**
 * @table member_fyw_log_sync;
 * @package Schemas
 * @version $
 * @license Commercial
 */

$db['member_fyw_log_sync']=array (
    'columns' =>
    array (
        'log_id' =>
            array (
                'type' => 'bigint unsigned',
                'required' => true,
                'pkey' => true,
                'extra' => 'auto_increment',
                'editable' => false,
            ),
        'serial_no' =>
            array (
                'type' => 'varchar(50)',
                'required' => true,
                'default' => '',
                'label'=>app::get('b2c')->_('同步会员流水号'),
                'width'=>100,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'params' =>
            array (
                'type' => 'longtext',
                'required' => false,
                'default' => '',
                'label'=>app::get('b2c')->_('同步会员信息'),
                'width'=>100,
                'editable' => false,
            ),
        'result_code' =>
            array (
                'type' => 'varchar(50)',
                'required' => true,
                'default' => '',
                'label'=>app::get('b2c')->_('响应码'),
                'width'=>50,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'success' =>
            array (
                'type' => 'bool',
                'default' => 'false',
                'required' => true,
                'label'=>app::get('b2c')->_('是否成功'),
                'default_in_list'=>true,
                'filtertype' => 'yes',
                'filterdefault' => true,
                'in_list'=>true,
                'editable' => false,
            ),
        'result_message' =>
            array (
                'type' => 'varchar(200)',
                'required' => true,
                'default' => '',
                'label'=>app::get('b2c')->_('响应描述'),
                'width'=>100,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'result' =>
            array (
                'type' => 'longtext',
                'required' => false,
                'default' => '',
                'label'=>app::get('b2c')->_('返回业务数据'),
                'width'=>100,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'total_num' =>
            array (
                'type' => 'number',
                'required' => false,
                'default' => 0,
                'label'=>app::get('b2c')->_('本次同步数量'),
                'width'=>50,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'success_num' =>
            array (
                'type' => 'number',
                'required' => false,
                'default' => 0,
                'label'=>app::get('b2c')->_('成功数量'),
                'width'=>50,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'failure_num' =>
            array (
                'type' => 'number',
                'required' => false,
                'default' => 0,
                'label'=>app::get('b2c')->_('失败数量'),
                'width'=>50,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'failure_log' =>
            array (
                'type' => 'varchar(200)',
                'required' => false,
                'default' => '',
                'label'=>app::get('b2c')->_('失败原因'),
                'width'=>80,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'createtime' =>
            array (
                'type' => 'time',
                'label' => app::get('b2c')->_('调用时间'),
                'width' => 60,
                'editable' => false,
                'filtertype' => 'time',
                'filterdefault' => true,
                'in_list' => true,
                'default_in_list' => true,
                'orderby' => true,
            ),
        'last_modified' =>
            array (
                'label' => app::get('b2c')->_('更新时间'),
                'type' => 'last_modify',
                'width' => 60,
                'editable' => false,
            ),
    ),
    'index' =>
      array (
        'ind_serial_no' =>
        array (
            'columns' =>
            array (
              0 => 'serial_no',
            ),
        ),
      ),
    'comment' => app::get('b2c')->_('福员外会员同步日志表'),
    'engine' => 'innodb',
    'version' => '$Rev: 51321 $'
);
