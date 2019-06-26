<?php
/**
*通过service方法增加高级筛选项
*
*
**/
 
class b2c_finder_extend_goods{
    function get_extend_colums(){
            $db['goods']=array (
              'columns' => 
              array (
                'store_id' => 
                array (
                  'type' => 'table:storemanger@business',//表示数据来源
                  'required' => true,
                  'default' => 0,
                  'label' => app::get('b2c')->_('店铺名称'),
                  'width' => 75,
                  'editable' => true,
                  'filtertype' => 'yes',
                  'filterdefault' => true,
                  'in_list' => true,
                )));
			$db['goods']=array (
              'columns' => 
              array (
             'gross_profit' => 
                array (
                  'type' => 'decimal(20,3)',
                  'required' => true,
                  'default' => 0,
                  'label' => app::get('b2c')->_('销售毛利率'),
                  'width' => 75,
                  'editable' => true,
                  'filtertype' => 'number',
				  'filterdefault' => true,
                  'in_list' => true,
                )));	  
        return $db;
    }
}

