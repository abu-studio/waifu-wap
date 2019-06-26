<?php
/**
 * Created by PhpStorm.
 * User: shaojun
 * Date: 2016/12/7
 * Time: 13:24
 */
$db['diffprice']=array (
    'columns' =>
        array (
            'goods_id' =>
                array (
                    'type' => 'table:goods@b2c',
                    'default' => 0,
                    'pkey' => true,
                    'required' => true,
                    'label' => app::get('b2c')->_('��ƷID'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                ),
            'old_price'=>array(
                'type' => 'money',
                'label' => app::get('b2c')->_('前一次价格'),
                'width' => 75,
                'filtertype' => 'number',
                'filterdefault' => true,
                'editable' => false,
                'in_list' => true,
            ),
            'new_price'=>array(
                'type' => 'money',
                'label' => app::get('b2c')->_('后一次价格'),
                'width' => 75,
                'filtertype' => 'number',
                'filterdefault' => true,
                'editable' => false,
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
    'comment' => app::get('b2c')->_('京东价格差表'),
    'engine' => 'innodb',
    'version' => '$Rev: 42376 $',
);
