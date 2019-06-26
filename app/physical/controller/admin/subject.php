<?php
class physical_ctl_admin_subject extends desktop_controller{

	var $workground = 'physical.workground.physical';

    function index(){
		$custom_actions[] = array('label'=>app::get('physical')->_('添加科目'),'href'=>'index.php?app=physical&ctl=admin_subject&act=add','target'=>'dialog::{title:\''.app::get('physical')->_('添加科目').'\',width:500,height:200}');

		$data['title'] = app::get('physical')->_('体检科目列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
        $this->finder('physical_mdl_subject', $data);
    }

	function add(){
        $this->display('admin/subject/form.html');
    }

    function edit($subject_id){
        $obj_subject = &$this->app->model('subject');
        $this->pagedata['subject_info'] = $obj_subject->dump($subject_id);
        $this->display('admin/subject/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_subject&act=index');
        $obj_subject = &$this->app->model('subject');
        if(empty($_POST['subject_id'])){
			$subject_code = $obj_subject->dump(array('subject_code'=>trim($_POST['subject_code']),'subject_id'));
			if(is_array($subject_code)){
				$this->end(false,app::get('physical')->_('科目编号重复'));
			}
            $subject_name = $obj_subject->dump(array('subject_name'=>trim($_POST['subject_name']),'subject_id'));
			if(is_array($subject_name)){
				$this->end(false,app::get('physical')->_('科目名称重复'));
			}
			if(trim($_POST['medical_code'])){
				$medical_code = $obj_subject->dump(array('medical_code'=>trim($_POST['medical_code']),'subject_id'));
				if(is_array($medical_code)){
					$this->end(false,app::get('physical')->_('医学编号重复'));
				}
			}
			$data['create_time'] = time();
        }else{
            $data['subject_id']=intval($_POST['subject_id']);
        }
        $data['subject_code'] = trim($_POST['subject_code']);
        $data['subject_name'] = trim($_POST['subject_name']);
		$data['medical_code'] = trim($_POST['medical_code']);
		$data['update_time'] = time();
        $this->end($obj_subject->save($data),app::get('physical')->_('科目保存成功'));
    }
}