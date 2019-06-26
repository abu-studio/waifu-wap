<?php 

 
/**
 * @table cron_log;
 * @package Schemas
 * @version $
 * @license Commercial
 */

$db['cron_log']=array (
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
        'cron_kind'       => array(
            'type'            => array(
                'jdbook'   => '京东图书定时任务',
                'jdgoods'  => '京东特卖定时任务',
            ),
            'searchtype' => 'has',
            'default'         => 'jdgoods',
            'required'        => true,
            'label'           => '定时任务类型',
            'width'           => 75,
            'editable'        => false,
            'filtertype'      => 'yes',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'cron_name' =>
        array (
            'type' => 'varchar(100)',
            'required' => true,
            'default' => '',
            'label'=>app::get('jdsale')->_('定时执行任务名称'),
            'width'=>200,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list'=>true,
            'editable' => false,
        ),
        'function_name' =>
        array (
            'type' => 'varchar(200)',
            'required' => true,
            'default' => '',
            'label'=>app::get('jdsale')->_('业务功能'),
            'width'=>200,
            'default_in_list'=>true,
            'in_list'=>true,
            'editable' => false,
        ),
        'success' =>
            array (
                'type' => 'bool',
                'default' => 'false',
                'required' => true,
                'label'=>app::get('jdsale')->_('是否成功'),
                'default_in_list'=>true,
                'filtertype' => 'yes',
                'filterdefault' => true,
                'in_list'=>true,
                'editable' => false,
            ),
        'result' =>
            array (
                'type' => 'longtext',
                'required' => true,
                'default' => '',
                'label'=>app::get('jdsale')->_('结果描述'),
                'width'=>300,
                'default_in_list'=>true,
                'in_list'=>true,
                'editable' => false,
            ),
        'createtime' =>
            array (
                'type' => 'time',
                'label' => '执行时间',
                'width' => 100,
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
    'engine' => 'innodb',
    'version' => '$Rev: 41329 $'
);
