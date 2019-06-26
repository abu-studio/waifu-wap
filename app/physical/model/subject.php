<?php
class physical_mdl_subject extends dbeav_model{
	var $has_tag = true;
    function pre_recycle($rows){
        foreach($rows as $v){
            $subject_ids[] = $v['subject_id'];
        }
		
		$o_project = &$this->app->model('project');
		$rows = $o_project->getList('project_id',array('subject_id'=>$subject_ids));
		if( $rows ){
            $this->recycle_msg = app::get('physical')->_('该体检科目已被体检项目使用');
            return false;
        }
        return true;
    }
}
