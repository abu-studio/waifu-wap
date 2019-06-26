<?php
class physical_ctl_admin_project extends desktop_controller{

	var $workground = 'physical.workground.physical';

    function index(){
		$custom_actions[] = array('label'=>app::get('physical')->_('添加项目'),'href'=>'index.php?app=physical&ctl=admin_project&act=add','target'=>'dialog::{title:\''.app::get('physical')->_('添加项目').'\',width:500,height:350}');

		$data['title'] = app::get('physical')->_('体检项目列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
        $this->finder('physical_mdl_project', $data);
    }

	function add(){
		$obj_subject = &$this->app->model('subject');
		$list = $obj_subject->getList("subject_id,subject_name");
        foreach($list as $key=>$val){
            $subject_list[$val['subject_id']]=$val['subject_name'];
        }
        $this->pagedata['subject_list'] = $subject_list;
        $this->display('admin/project/form.html');
    }

    function edit($project_id){
		$obj_subject = &$this->app->model('subject');
		$list = $obj_subject->getList("subject_id,subject_name");
        foreach($list as $key=>$val){
            $subject_list[$val['subject_id']]=$val['subject_name'];
        }
        $this->pagedata['subject_list'] = $subject_list;
        $obj_project = &$this->app->model('project');
        $this->pagedata['project_info'] = $obj_project->dump($project_id);
        $this->display('admin/project/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_project&act=index');
        $obj_project = &$this->app->model('project');
        if(empty($_POST['project_id'])){
			$project_code = $obj_project->dump(array('project_code'=>trim($_POST['project_code']),'project_id'));
			if(is_array($project_code)){
				$this->end(false,app::get('physical')->_('项目编号重复'));
			}
            $project_name = $obj_project->dump(array('project_name'=>trim($_POST['project_name']),'project_id'));
			if(is_array($project_name)){
				$this->end(false,app::get('physical')->_('项目名称重复'));
			}
			if(trim($_POST['medical_code'])){
				$medical_code = $obj_project->dump(array('medical_code'=>trim($_POST['medical_code']),'project_id'));
				if(is_array($medical_code)){
					$this->end(false,app::get('physical')->_('医学编号重复'));
				}
			}
			$data['create_time'] = time();
        }else{
            $data['project_id']=intval($_POST['project_id']);
        }
		$data['subject_id']=intval($_POST['subject_id']);
        $data['project_code'] = trim($_POST['project_code']);
        $data['project_name'] = trim($_POST['project_name']);
		$data['medical_code'] = trim($_POST['medical_code']);
		$data['price'] = floatval($_POST['price']);
		$data['introduction']=$_POST['introduction'];
		$data['update_time'] = time();
        $this->end($obj_project->save($data),app::get('physical')->_('项目保存成功'));
    }
}