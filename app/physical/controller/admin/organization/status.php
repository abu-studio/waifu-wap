<?php
class physical_ctl_admin_organization_status extends desktop_controller{

	var $workground = 'physical.workground.physical';

	function __construct($app){
        parent::__construct($app);
		$this->_status=array(
			'1' => app::get('physical')->_('满'),
			'2' => app::get('physical')->_('休'),
			'3' => app::get('physical')->_('闲'),
		);
    }

    function index(){
		$custom_actions[] = array('label'=>app::get('physical')->_('添加节假日'),'href'=>'index.php?app=physical&ctl=admin_organization_status&act=add','target'=>'dialog::{title:\''.app::get('physical')->_('添加节假日').'\',width:500,height:300}');

		$data['title'] = app::get('physical')->_('节假日列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
        $this->finder('physical_mdl_organization_status', $data);
    }

	function add(){
		$obj_organization = &$this->app->model('organization');
		$list = $obj_organization->getList("organization_id,organization_name");
        foreach($list as $key=>$val){
            $organization_list[$val['organization_id']]=$val['organization_name'];
        }
        $this->pagedata['organization_list'] = $organization_list;

		$this->pagedata['_status'] = $this->_status;

        $this->display('admin/organization/status/form.html');
    }

    function edit($id){
		$obj_organization = &$this->app->model('organization');
		$list = $obj_organization->getList("organization_id,organization_name");
        foreach($list as $key=>$val){
            $organization_list[$val['organization_id']]=$val['organization_name'];
        }
        $this->pagedata['organization_list'] = $organization_list;
		
		//预约时段信息
        $obj_organization_status = &$this->app->model('organization_status');
        $organization_status_info = $obj_organization_status->dump($id);
		$this->pagedata['organization_status_info'] = $organization_status_info;

		$this->pagedata['_status'] = $this->_status;

        $this->display('admin/organization/status/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_organization_status&act=index');
		if(strtotime($_POST['start_date']) > strtotime($_POST['end_date'])){
			$this->end(false,app::get('physical')->_('开始时间不能大于结束时间'));
		}
        $obj_organization_status = &$this->app->model('organization_status');
        if(empty($_POST['id'])){
			$data['create_time'] = time();
        }else{
            $data['id']=intval($_POST['id']);
        }
        $data['start_date'] = trim($_POST['start_date']);
        $data['end_date'] = trim($_POST['end_date']);
		$data['organization_id']=intval($_POST['organization_id']);
		$data['status']=$_POST['status'];
		$data['update_time'] = time();
		$data['mome'] = $_POST['mome'];
        $this->end($obj_organization_status->save($data),app::get('physical')->_('保存成功'));
    }
}