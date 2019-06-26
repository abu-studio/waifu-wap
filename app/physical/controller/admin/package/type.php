<?php
class physical_ctl_admin_package_type extends desktop_controller{

	var $workground = 'physical.workground.physical';

    function index(){
		$custom_actions[] = array('label'=>app::get('physical')->_('添加套餐类型'),'href'=>'index.php?app=physical&ctl=admin_package_type&act=add','target'=>'dialog::{title:\''.app::get('physical')->_('添加套餐类型').'\',width:500,height:150}');

		$data['title'] = app::get('physical')->_('体检套餐类型列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
        $this->finder('physical_mdl_package_type', $data);
    }

	function add(){
        $this->display('admin/package/type/form.html');
    }

    function edit($package_type_id){
        $obj_package_type = &$this->app->model('package_type');
        $this->pagedata['package_type_info'] = $obj_package_type->dump($package_type_id);
        $this->display('admin/package/type/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_package_type&act=index');
        $obj_package_type = &$this->app->model('package_type');
        if(empty($_POST['type_id'])){
            $package_type_name = $obj_package_type->dump(array('type_name'=>trim($_POST['type_name']),'type_id'));
			if(is_array($package_type_name)){
				$this->end(false,app::get('physical')->_('类型名称重复'));
			}
			$data['create_time'] = time();
        }else{
            $data['type_id']=intval($_POST['type_id']);
        }
        $data['type_name'] = trim($_POST['type_name']);
		$data['update_time'] = time();
        $this->end($obj_package_type->save($data),app::get('physical')->_('类型保存成功'));
    }
}