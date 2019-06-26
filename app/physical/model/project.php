<?php
class physical_mdl_project extends dbeav_model{
	var $has_tag = true;
    function pre_recycle($rows){
        foreach($rows as $v){
            $project_ids[] = $v['project_id'];
        }
		
		$o_package = &$this->app->model('package');
		$rows = $o_package->getList('project_ids');
		if( $rows ){
			foreach($rows as $val){
				$project_id_arr = explode(",",$val['project_ids']);
				$arr = array_intersect($project_ids,$project_id_arr);
				if($arr){
					$this->recycle_msg = app::get('physical')->_('该体检项目已被套餐关联');
					return false;
				}
			}
        }

		$o_orders = &$this->app->model('orders');
		$rows = $o_orders->getList('project_ids');
		if( $rows ){
			foreach($rows as $val){
				$project_id_arr = explode(",",$val['project_ids']);
				$arr = array_intersect($project_ids,$project_id_arr);
				if($arr){
					$this->recycle_msg = app::get('physical')->_('该体检项目已被预约单选中');
					return false;
				}
			}
        }

        return true;
    }
}
