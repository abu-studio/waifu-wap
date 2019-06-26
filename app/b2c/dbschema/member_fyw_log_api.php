<?php 

 
/**
 * @table member_fyw_log_api;
 * @package Schemas
 * @version $
 * @license Commercial
 */

$db['member_fyw_log_api']=array (
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
        'api_method' =>
        array (
            'type' => 'varchar(100)',
            'required' => true,
            'default' => '',
            'label'=>app::get('b2c')->_('接口定义名称'),
            'width'=>80,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list'=>true,
            'editable' => false,
        ),
        'post_data' =>
        array (
            'type' => 'longtext',
            'required' => false,
            'default' => '',
            'label'=>app::get('b2c')->_('外部接口提交数据'),
            'width'=>100,
        ),
        'return_data' =>
        array (
            'type' => 'longtext',
            'required' => false,
            'default' => '',
            'label'=>app::get('b2c')->_('返回数据'),
            'width'=>100,
        ),
        'params' =>
        array (
            'type' => 'longtext',
            'required' => false,
            'default' => '',
            'label'=>app::get('b2c')->_('调用业务参数'),
            'width'=>100,
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
                'width'=>50,
                'default_in_list'=>true,
                'filtertype' => 'yes',
                'filterdefault' => true,
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
        'result_code' =>
            array (
                'type' => 'varchar(50)',
                'required' => true,
                'default' => '',
                'label'=>app::get('b2c')->_('响应码'),
                'width'=>80,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'result_message' =>
            array (
                'type' => 'varchar(100)',
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
                'width'=>150,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'createtime' =>
            array (
                'type' => 'time',
                'label' => app::get('b2c')->_('调用时间'),
                'width' => 120,
                'editable' => false,
                'filtertype' => 'time',
                'filterdefault' => true,
                'in_list' => true,
                'default_in_list' => true,
                'orderby' => true,
            ),
    ),
    'index' =>
      array (
        'ind_createtime' =>
        array (
            'columns' =>
            array (
              0 => 'createtime',
            ),
        ),
      ),
    'comment' => app::get('b2c')->_('福员外接口调用日志表'),
    'engine' => 'innodb',
    'version' => '$Rev: 51321 $'
);
