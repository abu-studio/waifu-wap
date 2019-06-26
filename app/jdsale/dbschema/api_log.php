<?php

/**
 * @table api_log;
 * @package Schemas
 * @version $
 * @license Commercial
 */

$db['api_log'] = array(
    'columns' => array(
        'log_id'         => array(
            'type'     => 'bigint unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'api_kind'       => array(
            'type'            => array(
                'jdbook'   => '京东图书日志',
                'jdgoods'  => '京东特卖日志',
            ),
            'searchtype' => 'has',
            'default'         => 'jdgoods',
            'required'        => true,
            'label'           => '日志类型',
            'width'           => 75,
            'editable'        => false,
            'filtertype'      => 'yes',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'api_method'     => array(
            'type'            => 'varchar(100)',
            'required'        => true,
            'default'         => '',
            'label'           => app::get('jdsale')->_('接口定义名称'),
            'width'           => 100,
            'default_in_list' => true,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'editable'        => false,
        ),
        'api_function'   => array(
            'type'            => 'varchar(100)',
            'required'        => true,
            'default'         => '',
            'label'           => app::get('jdsale')->_('功能名称'),
            'width'           => 100,
            'default_in_list' => true,
            'in_list'         => true,
            'editable'        => false,
        ),
        'params'         => array(
            'type'            => 'longtext',
            'required'        => false,
            'default'         => '',
            'label'           => app::get('jdsale')->_('调用业务参数'),
            'width'           => 100,
            'default_in_list' => true,
            'in_list'         => true,
            'editable'        => false,
        ),
        'code'           => array(
            'type'            => 'varchar(50)',
            'required'        => true,
            'default'         => '',
            'label'           => app::get('jdsale')->_('系统code值'),
            'width'           => 50,
            'default_in_list' => true,
            'in_list'         => true,
            'editable'        => false,
        ),
        'result_code'    => array(
            'type'            => 'varchar(50)',
            'required'        => true,
            'default'         => '',
            'label'           => app::get('jdsale')->_('响应码'),
            'width'           => 40,
            'default_in_list' => true,
            'in_list'         => true,
            'editable'        => false,
        ),
        'success'        => array(
            'type'            => 'bool',
            'default'         => 'false',
            'required'        => true,
            'label'           => app::get('jdsale')->_('是否成功'),
            'default_in_list' => true,
            'filtertype'      => 'yes',
            'filterdefault'   => true,
            'in_list'         => true,
            'editable'        => false,
        ),
        'result_message' => array(
            'type'            => 'varchar(100)',
            'required'        => true,
            'default'         => '',
            'label'           => app::get('jdsale')->_('响应描述'),
            'width'           => 100,
            'default_in_list' => true,
            'in_list'         => true,
            'editable'        => false,
        ),
        'result'         => array(
            'type'            => 'longtext',
            'required'        => false,
            'default'         => '',
            'label'           => app::get('jdsale')->_('返回业务数据'),
            'width'           => 100,
            'default_in_list' => true,
            'in_list'         => true,
            'editable'        => false,
        ),
        'createtime'     => array(
            'type'            => 'time',
            'label'           => '调用时间',
            'width'           => 50,
            'editable'        => false,
            'filtertype'      => 'time',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
            'orderby'         => true,
        ),
    ),
    'index'   => array(
        'ind_createtime' => array(
            'columns' => array(
                0 => 'createtime',
            ),
        ),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev: 41329 $',
);
