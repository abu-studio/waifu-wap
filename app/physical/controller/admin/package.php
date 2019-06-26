<?php
class physical_ctl_admin_package extends desktop_controller{

	var $workground = 'physical.workground.physical';

    function index(){
		$custom_actions[] = array('label'=>app::get('physical')->_('添加套餐'),'href'=>'index.php?app=physical&ctl=admin_package&act=add','target'=>'dialog::{title:\''.app::get('physical')->_('添加套餐').'\',width:500,height:350}');

		$data['title'] = app::get('physical')->_('体检套餐列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
        $this->finder('physical_mdl_package', $data);
    }

	function add(){
		$obj_package_type = &$this->app->model('package_type');
		$list = $obj_package_type->getList("type_id,type_name");
        foreach($list as $key=>$val){
            $type_list[$val['type_id']]=$val['type_name'];
        }
        $this->pagedata['type_list'] = $type_list;

        $this->display('admin/package/form.html');
    }

    function edit($package_id){
		$obj_package_type = &$this->app->model('package_type');
		$list = $obj_package_type->getList("type_id,type_name");
        foreach($list as $key=>$val){
            $type_list[$val['type_id']]=$val['type_name'];
        }
        $this->pagedata['type_list'] = $type_list;

        $obj_package = &$this->app->model('package');
        $package_info = $obj_package->dump($package_id);
		$this->pagedata['package_info'] = $package_info;

		$project_arr=explode(",",$package_info['project_ids']);
		$project_info = array();
        foreach($project_arr as $key => $val){
            $project_info['info'][] = array('id'=>$val);
        }
        $project_info['linkid'] = $package_info['project_ids'];
        $project_info['info'] = json_encode($project_info['info']);
        $this->pagedata['project_info'] = $project_info;

        $this->display('admin/package/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_package&act=index');
        $obj_package = &$this->app->model('package');
        if(empty($_POST['package_id'])){
			$package_code = $obj_package->dump(array('package_code'=>trim($_POST['package_code']),'package_id'));
			if(is_array($package_code)){
				$this->end(false,app::get('physical')->_('套餐编号重复'));
			}
            $package_name = $obj_package->dump(array('package_name'=>trim($_POST['package_name']),'package_id'));
			if(is_array($package_name)){
				$this->end(false,app::get('physical')->_('套餐名称重复'));
			}
			$data['create_time'] = time();
        }else{
            $data['package_id']=intval($_POST['package_id']);
        }
		$data['type_id']=intval($_POST['type_id']);
        $data['package_code'] = trim($_POST['package_code']);
        $data['package_name'] = trim($_POST['package_name']);
		$data['image']=$_POST['image'];
		$data['price'] = floatval($_POST['price']);
		$data['mktprice'] = floatval($_POST['mktprice']);
        $data['project_ids'] = $_POST['project_ids'];

		$data['update_time'] = time();
        $this->end($obj_package->save($data),app::get('physical')->_('套餐保存成功'));
    }

	function store_attach($package_id){
        $this->pagedata['package_id'] = $package_id;
		$filter=array('package_id'=>$package_id);
		$package=kernel::single('physical_mdl_package')->dump($filter,'*');
		$this->pagedata['filter'] = array("type"=>1);
		$obj_attach =kernel::single('physical_mdl_store_package_attach');
		$obj_price = kernel::single('physical_mdl_store_package_price');
		$store_id=$obj_attach->getList('*',$filter);
		$store_price=$obj_price->getList('*',$filter);
		/*foreach($package['stores'] as $key=>$value){
			$store_info['store_id'][]=$value['store_id'];
		}
		$store_info['store_price']=$package['cost_price'];*/
		foreach($store_id as $key=>$value){
			$store_info['store_id'][]=$value['store_id'];
		}
		foreach($store_price as $key=>$value){
			$package['cost_price'][$value['store_id']]=$value;
		}
		$package['store_info']=$store_info;
		$this->pagedata['package'] =$package;
		$this->pagedata['related_return_url'] ='index.php?app=physical&ctl=admin_package&act=select_store_attach&p[0]='.$package_id;
		
		/*
		$obj_attach = &$this->app->model('store_package_attach');
		$list = $obj_attach->getList("store_id",array("package_id"=>$package_id));
		$store_info = array();
        foreach($list as $key => $val){
			$store_info['linkid'][] = $val['store_id'];
            $store_info['info'][] = array('id'=>$val['store_id']);
        }
        $store_info['linkid'] = implode(",",$store_info['linkid']);
        $store_info['info'] = json_encode($store_info['info']);
        $this->pagedata['store_info'] = $store_info;
		*/
        $this->display('admin/package/attach.html');
    }
	function select_store_attach($package_id){
		if(!$package_id){
			$package_id=$_GET['p'][0];
		}
		$render = kernel::single('base_render');
		$store=kernel::single('physical_mdl_store')->getList('store_id,store_name',array('store_id'=>$_POST['data']));
		$store_price=kernel::single('physical_mdl_store_package_price')->getList('*',array('store_id'=>$_POST['data'],'package_id'=>$package_id));
		$store_info=array();
		$package['store']=$store;
		foreach($store_price as $key=>$value){
			$package['cost_price'][$value['store_id']]=$value;
		}
		$this->pagedata['package'] =$package;
		$render->pagedata['desktop_res_url'] = app::get('desktop')->res_url;
		header('Content-Type:text/html; charset=utf-8');
        echo $render->fetch('admin/package/attach/ajax_store.html','physical');exit;
	}

	function add_attach(){
		$this->begin();
		//$obj_store_package =kernel::single('physical_mdl_package');
		$obj_attach =kernel::single('physical_mdl_store_package_attach');
		$obj_price = kernel::single('physical_mdl_store_package_price');
		$data['package_id']=$_POST['package_id'];
		/*foreach($_POST['store_id'] as $key=>$value){
			$data['stores'][$key]['package_id']=$_POST['package_id'];
			$data['stores'][$key]['store_id']=$value;
			$data['cost_price'][$key]['package_id']=$_POST['package_id'];
			$data['cost_price'][$key]['store_id']=$value;
			$data['cost_price'][$key]['man']=$_POST['price']['man'][$value];
			$data['cost_price'][$key]['unmdwoman']=$_POST['price']['unmdwoman'][$value];
			$data['cost_price'][$key]['mdwoman']=$_POST['price']['mdwoman'][$value];
		}
		error_log('data:'.var_export($data,1)."\n",3,ROOT_DIR.'/log.txt');
		$result=$obj_store_package->save($data);
		*/
		/*
		
		$obj_attach->delete(array("package_id"=>$_POST['package_id']));
		if($_POST['store_ids']){
			$store_arr=explode(",",$_POST['store_ids']);
			$n = 0 ;
			foreach($store_arr as $key => $val){
				$data[$n]['package_id'] = $_POST['package_id'];
				$data[$n]['store_id'] = $val;
				$obj_attach->save($data[$n]);
				$n++;
			}
		}
		*/
		$obj_attach->delete(array("package_id"=>$_POST['package_id']));
		$obj_price->delete(array("package_id"=>$_POST['package_id']));
		foreach($_POST['store_id'] as $key=>$value){
			$data['stores']['package_id']=$_POST['package_id'];
			$data['stores']['store_id']=$value;
			$obj_attach->insert($data['stores']);
			$data['cost_price']['package_id']=$_POST['package_id'];
			$data['cost_price']['store_id']=$value;
			$data['cost_price']['man']=$_POST['price']['man'][$value];
			$data['cost_price']['unmdwoman']=$_POST['price']['unmdwoman'][$value];
			$data['cost_price']['mdwoman']=$_POST['price']['mdwoman'][$value];
			$obj_price->insert($data['cost_price']);
		}

		$this->end(true,app::get('physical')->_('关联门店成功'));
	}
}