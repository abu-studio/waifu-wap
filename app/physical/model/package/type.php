<?php
class physical_mdl_package_type extends dbeav_model{
	var $has_tag = true;
    function pre_recycle($rows){
        foreach($rows as $v){
            $type_ids[] = $v['type_id'];
        }
		
		$o_package = &$this->app->model('package');
		$rows = $o_package->getList('package_id',array('type_id'=>$type_ids));
		if( $rows ){
            $this->recycle_msg = app::get('physical')->_('该类型已被套餐使用');
            return false;
        }
        return true;
    }
}
