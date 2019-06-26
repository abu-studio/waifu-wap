<?php
class physical_ctl_admin_store_status extends desktop_controller{

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
		$custom_actions[] = array('label'=>app::get('physical')->_('添加预约时段'),'href'=>'index.php?app=physical&ctl=admin_store_status&act=add','target'=>'dialog::{title:\''.app::get('physical')->_('添加预约时段').'\',width:500,height:300}');

		$data['title'] = app::get('physical')->_('预约时段列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
        $this->finder('physical_mdl_store_status', $data);
    }

	function add(){
		$obj_store = &$this->app->model('store');
		$list = $obj_store->getList("store_id,store_name");
        foreach($list as $key=>$val){
            $store_list[$val['store_id']]=$val['store_name'];
        }
        $this->pagedata['store_list'] = $store_list;

		$this->pagedata['_status'] = $this->_status;

        $this->display('admin/store/status/add.html');
    }

	function Toadd(){
        $this->begin('index.php?app=physical&ctl=admin_store_status&act=index');
		if(strtotime($_POST['start_date']) > strtotime($_POST['end_date'])){
			$this->end(false,app::get('physical')->_('开始时间不能大于结束时间'));
		}
        $obj_store_status = &$this->app->model('store_status');
		$store_ids=$_POST['store_ids'];
		$n = 0;
		$num = count($store_ids);

		$db=kernel::database();
		//事物处理开始
		$transaction_status = $db->beginTransaction();

		foreach($store_ids as $k=>$v){
			$data['start_date'] = trim($_POST['start_date']);
			$data['end_date'] = trim($_POST['end_date']);
			$data['status']=$_POST['status'];
			$data['store_id']=$v;
			$data['create_time'] = time();
			$data['update_time'] = time();
			$rs = $obj_store_status->save($data);
			if($rs){
				$n++;
			}
			unset($data);
		}
        if($n == $num){
			$db->commit($transaction_status);
			$this->end(true,app::get('physical')->_('预约时段保存成功'));
		}else{
			$db->rollback();
			$this->end(false,app::get('physical')->_('预约时段保存失败'));
		}
    }

    function edit($id){
		$obj_store = &$this->app->model('store');
		$list = $obj_store->getList("store_id,store_name");
        foreach($list as $key=>$val){
            $store_list[$val['store_id']]=$val['store_name'];
        }
        $this->pagedata['store_list'] = $store_list;
		
		//预约时段信息
        $obj_store_status = &$this->app->model('store_status');
        $store_status_info = $obj_store_status->dump($id);
		$this->pagedata['store_status_info'] = $store_status_info;

		$this->pagedata['_status'] = $this->_status;

        $this->display('admin/store/status/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_store_status&act=index');
		if(strtotime($_POST['start_date']) > strtotime($_POST['end_date'])){
			$this->end(false,app::get('physical')->_('开始时间不能大于结束时间'));
		}
        $obj_store_status = &$this->app->model('store_status');
        if(empty($_POST['id'])){
			$data['create_time'] = time();
        }else{
            $data['id']=intval($_POST['id']);
        }
        $data['start_date'] = trim($_POST['start_date']);
        $data['end_date'] = trim($_POST['end_date']);
		$data['store_id']=intval($_POST['store_id']);
		$data['status']=$_POST['status'];
		$data['update_time'] = time();
		$rs = $obj_store_status->save($data);
		if($rs){
			$this->end(true,app::get('physical')->_('预约时段保存成功'));
		}else{
			$this->end(false,app::get('physical')->_('预约时段保存失败'));
		}
    }
}