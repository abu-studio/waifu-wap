<?php
class physical_mdl_organization extends dbeav_model{
	var $has_tag = true;
    function pre_recycle($rows){
        foreach($rows as $v){
            $organization_ids[] = $v['organization_id'];
        }
		
		$o_store = &$this->app->model('store');
		$rows = $o_store->getList('store_id',array('organization_id'=>$organization_ids));
		if( $rows ){
            $this->recycle_msg = app::get('physical')->_('该机构已被门店使用');
            return false;
        }
        return true;
    }
}
