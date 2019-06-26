<?php
/**
 * @table image;
 * @package Schemas
 *
 *
 */
$db['image']=array (
    'columns' =>
        array (
            'image_id' =>
                array (
                    'type' => 'number',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'label' => app::get('jdsale')->_('京东商品图片ID'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            'goods_id' => array (
                'type' => 'bigint(20)',
                'required' => true,
                'default' => 0,
                'editable' => false,
            ),
            'image_path' =>
                array (
                    'type' => 'varchar(500)',
                    'required' => true,
                    'is_title' => true,
                    'default' => '',
                    'label' => app::get('jdsale')->_('品牌名称'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            'order_sort' =>
                array (
                    'type' => 'number',
                    'label' => app::get('jdsale')->_('排序'),
                    'width' => 110,
                    'editable' => false,
                    'default' => 0,
                    'in_list' => true,
                ),
            'goods_kind' =>
                array (
                    'type' =>
                        array (
                            'virtual' => '实体商品',
                            'entity' => '虚拟商品',
                            'card' => '卡券商品',
                            'jdbook' => '京东图书商品',
                            'jdgoods' => '京东普通商品',
                        ),
                    'default' => 'jdgoods',
                    'required' => true,
                    'label' => '商品种类',
                    'width' => 110,
                    'editable' => false,
                    'hidden' => true,
                    'in_list' => false,
                ),

        ),
    'comment' => app::get('jdsale')->_('京东商品图片(附图)表'),
    'index' =>
        array (
            'ind_goods_id' =>
                array (
                    'columns' =>
                        array (
                            0 => 'goods_id',
                        ),
                ),

        ),
    'engine' => 'innodb',
    'version' => '$Rev: 41329 $',
);