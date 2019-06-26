<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class business_mdl_settlement extends dbeav_model{
	 
	 var $defaultOrder = 'create_time desc';

     function __construct($app){
        parent::__construct($app);
     }
     
     //创建新的结算单号
     public function GetBillNo(){
	    $i = rand(0,999);
        do{
            if(999==$i){
                $i=0;
            }
            $i++;
            $bill_id = date('ymdHis').str_pad($i,3,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT settlement_no from sdb_business_settlement where settlement_no ='.$bill_id);
        }while($row);
        return $bill_id;
     }

	 function get_batch_id(){
		$i = rand(0,99999);
        do{
            if(99999==$i){
                $i=0;
            }
            $i++;
            $batch_id = date('ymdH').str_pad($i,5,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT settlement_no from sdb_business_settlement where batch_id ='.$batch_id);
        }while($row);
        return $batch_id;
	}
       /**
     * 重写搜索的下拉选项方法
     * @param null
     * @return null
     */
    public function searchOptions(){
        $columns = array();
        foreach($this->_columns() as $k=>$v){
            if(isset($v['searchtype']) && $v['searchtype']){
                $columns[$k] = $v['label'];
            }
        }

        /** 添加店铺名称搜索 **/
        $columns['store_name'] = app::get('b2c')->_('店铺名称');
        /** end **/
        return $columns;
    }
    
      function _filter($filter,$tbase=''){
        //店铺名称模糊搜索
        if (isset($filter) && $filter && is_array($filter) && array_key_exists('store_name', $filter))
        {
            $obj_business_storemanger = app::get('business')->model('storemanger');
            $store_filter = array(
                'store_name|has'=>$filter['store_name'],
            );
            $row_store = $obj_business_storemanger->getList('*',$store_filter);
            $arr_store_id = array();
            if ($row_store)
            {
                foreach ($row_store as $str_store)
                {
                    $arr_store_id[] = $str_store['store_id'];
                }
                $filter['store_id|in'] = $arr_store_id;
            }
            unset($filter['store_name']);
        }
        //如果filter条件是直接可以只用的则在条件中增加 str_where参数,直接返回
        if(isset($filter['str_where']) && $filter['str_where']){
            return $filter['str_where'];
        }
        $info_object = kernel::service('sensitive_information');
        if(is_object($info_object)) $info_object->opinfo($filter,'b2c_mdl_members',__FUNCTION__);
        $filter = parent::_filter($filter);
        return $filter;
    }
}
