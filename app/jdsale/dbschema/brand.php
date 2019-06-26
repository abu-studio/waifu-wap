<?php
/**
 * @table brand;
 * @package Schemas
 *
 *
 */
$db['brand']=array (
    'columns' =>
        array (
            'brand_id' =>
                array (
                    'type' => 'number',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'label' => app::get('jdsale')->_('品牌ID'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            'brand_name' =>
                array (
                    'type' => 'varchar(200)',
                    'required' => true,
                    'is_title' => true,
                    'default' => '',
                    'label' => app::get('jdsale')->_('品牌名称'),
                    'width' => 110,
					'searchtype' => 'has',
					'filtertype' => 'yes',
					'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),

        ),
    'comment' => app::get('jdsale')->_('京东品牌表'),
    'index' =>
        array (
            'ind_brand_name' =>
                array (
                    'columns' =>
                        array (
                            0 => 'brand_name',
                        ),
                ),

        ),
    'engine' => 'innodb',
    'version' => '$Rev: 41329 $',
);