<?php
class physical_ctl_admin_organization extends desktop_controller{

	var $workground = 'physical.workground.physical';

    function index(){
		$custom_actions[] = array('label'=>app::get('physical')->_('添加机构'),'href'=>'index.php?app=physical&ctl=admin_organization&act=add','target'=>'dialog::{title:\''.app::get('physical')->_('添加机构').'\',width:500,height:400}');

		$data['title'] = app::get('physical')->_('机构列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
        $this->finder('physical_mdl_organization', $data);
    }

	function add(){
		$obj=kernel::single('base_mdl_kvstore');
		$kv_image_set=$obj->getList('*',array('key'=>'physical.image.set','prefix'=>'system'));
		$organization_info['logo']=$kv_image_set[0]['value']['org']['default_image'];
        $types=array("1"=>"体检");
        $this->pagedata['types'] = $types;
        $this->pagedata['organization_info'] = $organization_info;
        $this->display('admin/organization/form.html');
    }

    function edit($organization_id){
        $types=array("1"=>"体检");
        $this->pagedata['types'] = $types;
        $obj_organization = &$this->app->model('organization');
        $this->pagedata['organization_info'] = $obj_organization->dump($organization_id);
        $this->display('admin/organization/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_organization&act=index');
        $obj_organization = &$this->app->model('organization');
        if(empty($_POST['organization_id'])){
			$organization_code = $obj_organization->dump(array('organization_code'=>trim($_POST['organization_code']),'organization_id'));
			if(is_array($organization_code)){
				$this->end(false,app::get('physical')->_('机构编号重复'));
			}
            $organization_name = $obj_organization->dump(array('organization_name'=>trim($_POST['organization_name']),'organization_id'));
			if(is_array($organization_name)){
				$this->end(false,app::get('physical')->_('机构名称重复'));
			}
			$data['create_time'] = time();
        }else{
            $data['organization_id']=intval($_POST['organization_id']);
        }
		$data['type'] = intval($_POST['type']);
        $data['organization_code'] = trim($_POST['organization_code']);
        $data['organization_name'] = trim($_POST['organization_name']);
		$data['logo']=$_POST['logo'];
		$data['url'] = trim($_POST['url']);
		$data['update_time'] = time();
        $this->end($obj_organization->save($data),app::get('physical')->_('机构保存成功'));
    }
}