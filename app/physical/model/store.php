<?php
class physical_mdl_store extends dbeav_model {
	var $has_tag = true;
	function pre_recycle($rows){
        foreach($rows as $v){
            $store_ids[] = $v['store_id'];
        }
		
		$o_store_status = &$this->app->model('store_status');
		$rows = $o_store_status->getList('id',array('store_id'=>$store_ids));
		if( $rows ){
            $this->recycle_msg = app::get('physical')->_('该门店有预约时段设置');
            return false;
        }

		$o_attach = &$this->app->model('store_package_attach');
		$rows = $o_attach->getList('attach_id',array('store_id'=>$store_ids));
		if( $rows ){
            $this->recycle_msg = app::get('physical')->_('该门店已被套餐关联');
            return false;
        }

        return true;
    }
    function getInfobyid($store_id) {
        $store_id = trim($store_id);
        $row = $this -> db -> selectrow("SELECT a.*,b.organization_name,b.url as org_url from sdb_physical_store as a LEFT JOIN sdb_physical_organization as b ON b.organization_id =  a.organization_id WHERE  a.store_id ='{$store_id}' ");
        if ($row) {
            return $row;
        }
    }
	function get_attach_list($store_id) {
        $store_id = trim($store_id);
        $row = $this -> db -> select("SELECT a.*,b.package_name,b.package_code,c.type_name from sdb_physical_store_package_attach as a LEFT JOIN sdb_physical_package as b ON b.package_id =  a.package_id LEFT JOIN sdb_physical_package_type as c ON b.type_id =  c.type_id WHERE  a.store_id ='{$store_id}' ");
        if ($row) {
            return $row;
        }
    }

}
