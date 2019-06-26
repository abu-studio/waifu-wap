<?php
/**
 * @table goods_cat;
 * @package Schemas
 *
 *
 */
$db['goods_cat']=array (
    'columns' =>
        array (
            'cat_id' =>
                array (
                    'type' => 'number',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'label' => app::get('jdsale')->_('分类ID'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            'jd_cat_id' =>
                array (
                    'type' => 'number',
                    'label' => app::get('jdsale')->_('京东分类ID'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'parent_id'=>true,
                ),
            'parent_id' =>
                array (
                    'type' => 'number',
                    'label' => app::get('jdsale')->_('父分类ID'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'parent_id'=>true,
                ),
            'cat_path' =>
                array (
                    'type' => 'varchar(100)',
                    'default' => ',',
                    'label' => app::get('jdsale')->_('分类路径(从根至本结点的路径,逗号分隔,首部有逗号)'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                ),
            'is_leaf' =>
                array (
                    'type' => 'bool',
                    'required' => true,
                    'default' => 'false',
                    'label' => app::get('jdsale')->_('是否叶子结点（true：是；false：否）'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                ),
            'cat_name' =>
                array (
                    'type' => 'varchar(100)',
                    'required' => true,
                    'is_title' => true,
                    'default' => '',
                    'label' => app::get('jdsale')->_('分类名称'),
                    'width' => 110,
                    'searchtype' => 'has',
					'filtertype' => 'yes',
					'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            'cat_kind' => array(
                'type' =>
                    array (
                        'jdgoods' => '京东普品',
                        'jdbook' => '京东图书',
                    ),
                'required' => true,
                'is_title' => true,
                'default' => 'jdgoods',
                'label' => app::get('jdsale')->_('类别范畴'),
                'width' => 110,
                'searchtype' => 'has',
                'filtertype' => 'yes',
                'filterdefault' => true,
                'editable' => false,
                'in_list' => true,
                'default_in_list' => true,
            ),
            'disabled' =>
                array (
                    'type' => 'bool',
                    'default' => 'false',
                    'required' => true,
                    'label' => app::get('jdsale')->_('是否屏蔽（true：是；false：否）'),
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                ),
            'p_order' =>
                array (
                    'type' => 'number',
                    'label' => app::get('jdsale')->_('排序'),
                    'width' => 110,
                    'editable' => false,
                    'default' => 0,
                    'in_list' => true,
                ),

            'child_count' =>
                array (
                    'type' => 'number',
                    'default' => 0,
                    'required' => true,
                    'editable' => false,
                ),

        ),
    'comment' => app::get('jdsale')->_('京东商品分类表'),
    'index' =>
        array (
            'ind_cat_path' =>
                array (
                    'columns' =>
                        array (
                            0 => 'cat_path',
                        ),
                ),
            'ind_disabled' =>
                array (
                    'columns' =>
                        array (
                            0 => 'disabled',
                        ),
                ),
        ),
    'engine' => 'innodb',
    'version' => '$Rev: 41329 $',
);