<?php

 
class b2c_finder_extend_sales_rule_order{
    function get_extend_colums(){
			$db['sales_rule_order']=array (
              'columns' => 
              array (
                'store_id' => 
                array (
                  'type' => 'table:storemanger@business',//表示数据来源
                  'required' => false,
                  'default' => 0,
                  'label' => app::get('b2c')->_('店铺名称'),
                  'width' => 75,
                  'editable' => false,
                  'filtertype' =>'has',
				  'filterdefault' => true,

                ),
				 'status' => 
                array (
                  'type' => 'bool',//表示数据来源
                  'required' => false,
                  'default' =>'false',
                  'label' => app::get('b2c')->_('启用状态'),
                  'width' => 75,
                  'editable' => true,
                  'filtertype' => 'has',
                  'filterdefault' => true,
                  'in_list' => true,

                )
				));
            
        return $db;
    }
}

