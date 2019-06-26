<?php
class physical_ctl_admin_store extends desktop_controller{

	var $workground = 'physical.workground.physical';

	function __construct($app){
        parent::__construct($app);
        $this->_hours=array(
			"00"=>"00",
			"01"=>"01",
			"02"=>"02",
			"03"=>"03",
			"04"=>"04",
			"05"=>"05",
			"06"=>"06",
			"07"=>"07",
			"08"=>"08",
			"09"=>"09",
			"10"=>"10",
			"11"=>"11",
			"12"=>"12",
			"13"=>"13",
			"14"=>"14",
			"15"=>"15",
			"16"=>"16",
			"17"=>"17",
			"18"=>"18",
			"19"=>"19",
			"20"=>"20",
			"21"=>"21",
			"22"=>"22",
			"23"=>"23",
		);
		$this->_minutes=array(
			"00"=>"00",
			"05"=>"05",
			"10"=>"10",
			"15"=>"15",
			"20"=>"20",
			"25"=>"25",
			"30"=>"30",
			"35"=>"35",
			"40"=>"40",
			"45"=>"45",
			"50"=>"50",
			"55"=>"55",
		);
		$this->_week=array(
			"1"=>"周一",
			"2"=>"周二",
			"3"=>"周三",
			"4"=>"周四",
			"5"=>"周五",
			"6"=>"周六",
			"0"=>"周日",
		);
		$this->status=array(
				'1'=>'营业',
				'2'=>'装修',
				'3'=>'整顿',
				'4'=>'闭店',
		);
    }

    function index(){
		$custom_actions[] = array('label'=>app::get('physical')->_('添加门店'),'href'=>'index.php?app=physical&ctl=admin_store&act=add','target'=>'dialog::{title:\''.app::get('physical')->_('添加门店').'\',width:500,height:500}');

		$data['title'] = app::get('physical')->_('门店列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
		$data['use_buildin_recycle'] = false;
        $this->finder('physical_mdl_store', $data);
    }

	function add(){
		$obj_organization = &$this->app->model('organization');
		$list = $obj_organization->getList("organization_id,organization_name,organization_code");
		$store_count=$this->_edit();
		$store_code_array=array();
        foreach($list as $key=>$val){
            $organization_list[$val['organization_id']]=$val['organization_name'];
			$store_next=$store_count[$val['organization_id']]?$store_count[$val['organization_id']]:1;
			$store_code_array[$val['organization_id']]=$val['organization_code'].str_pad($store_next,5,'0',STR_PAD_LEFT);
        }
		
		$store_info['store_code']=$store_code_array[$list[0]['organization_id']];
		$this->pagedata['store_code_array'] = json_encode($store_code_array);
		$obj=kernel::single('base_mdl_kvstore');
		$kv_image_set=$obj->getList('*',array('key'=>'physical.image.set','prefix'=>'system'));
		$store_info['image']=$kv_image_set[0]['value']['store']['default_image'];
		
		$this->pagedata['store_info'] = $store_info;
		
		$this->pagedata['organization_list'] = $organization_list;
		$this->pagedata['status'] = $this->status;
		$this->pagedata['_hours'] = $this->_hours;
		$this->pagedata['_minutes'] = $this->_minutes;
		$this->pagedata['_week'] = $this->_week;

		foreach($this->_week as $key=>$val){
			$weekdays[$key]["val"] = $val;
		}
		$this->pagedata['weekdays'] = $weekdays;

        $this->display('admin/store/form.html');
    }
	function _edit(){
		$data=kernel::database()->select('select store_id,store_code ,organization_id from sdb_physical_store ORDER BY store_id desc');
		$return=array();
		$store_ids=array();
		foreach($data as $key=>$value){
			if(!in_array($value['organization_id'],$store_ids)){
				$count=substr($value['store_code'],-5);
				$return[$value['organization_id']]=$count+1;
				$store_ids[]=$value['organization_id'];
			}
		}
		return $return;
	}
    function edit($store_id){
		$obj_organization = &$this->app->model('organization');
		$list = $obj_organization->getList("organization_id,organization_name,organization_code");
		//门店信息
        $obj_store = &$this->app->model('store');
        $store_info = $obj_store->dump($store_id);
		//门店编号
		$store_count=$this->_edit();
		$store_code_array=array();
        foreach($list as $key=>$val){
            $organization_list[$val['organization_id']]=$val['organization_name'];
			if($val['organization_id']==$store_info['organization_id']){
				$store_code_array[$val['organization_id']]=$store_info['store_code'];
			}else{
				$store_next=$store_count[$val['organization_id']]?$store_count[$val['organization_id']]:1;
				$store_code_array[$val['organization_id']]=$val['organization_code'].str_pad($store_next,5,'0',STR_PAD_LEFT);
			}
        }
		$this->pagedata['store_code_array'] = json_encode($store_code_array);
        $this->pagedata['organization_list'] = $organization_list;

		$this->pagedata['_hours'] = $this->_hours;
		$this->pagedata['_minutes'] = $this->_minutes;
		$this->pagedata['_week'] = $this->_week;
		
		

		$open_arr = explode(":",$store_info["open"]);
		$store_info["open_hour"] = $open_arr[0];
		$store_info["open_minute"] = $open_arr[1];

		$close_arr = explode(":",$store_info["close"]);
		$store_info["close_hour"] = $close_arr[0];
		$store_info["close_minute"] = $close_arr[1];

		foreach($this->_week as $key=>$val){
			$weekdays[$key]["val"] = $val;
		}
		$weekday_arr = explode(",",$store_info["weekday"]);
		foreach($weekday_arr as $val){
			$weekdays[$val]["is"] = 1;
		}
		$this->pagedata['weekdays'] = $weekdays;
		
		$this->pagedata['store_info'] = $store_info;
		$this->pagedata['status'] = $this->status;
        $this->display('admin/store/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_store&act=index');
        $obj_store = &$this->app->model('store');
        if(empty($_POST['store_id'])){
			$data['create_time'] = time();
			$oldstore['store_id']='-1';
        }else{
            $data['store_id']=intval($_POST['store_id']);
			$oldstore = $obj_store->dump(array('store_id'=>trim($_POST['store_id']),'store_id'));
			if($oldstore){
				if($oldstore['status']!=$_POST['status']){
					if(!trim($_POST['log'])){
						$this->end(false,app::get('physical')->_('改变状态，必须填写备注'));
					}				
				}else{
					$_POST['log']='';
				}
			}else{
				$this->end(false,app::get('physical')->_('编辑的门店不存在'));
			}	
        }
		if(!$_POST['weekday']){
			$this->end(false,app::get('physical')->_('请选择工作日'));
		}
		$store_code = $obj_store->dump(array('store_code'=>trim($_POST['store_code']),'store_id'));
		if($store_code&&$store_code['store_id']!=$oldstore['store_id']){
			$this->end(false,app::get('physical')->_('门店编号重复'));
		}
		$store_name = $obj_store->dump(array('store_name'=>trim($_POST['store_name']),'store_id'));
		if($store_name&&$store_name['store_id']!=$oldstore['store_id']){
			$this->end(false,app::get('physical')->_('门店名称重复'));
		}
        $data['store_code'] = trim($_POST['store_code']);
        $data['store_name'] = trim($_POST['store_name']);
		$data['organization_id']=intval($_POST['organization_id']);
		$data['image']=$_POST['image'];
		$data['status']=$_POST['status'];
		
		

        //机构信息
		$obj_organization = &$this->app->model('organization');
		$organization_info = $obj_organization->dump(array('organization_id'=>intval($_POST['organization_id']),'type'));
		if($_POST['default_image']){
			$data['image']=$organization_info['logo'];
		}
		$data['url']	=$_POST['url'];
		$data['type']=$organization_info['type'];
		$data['area']=$_POST['area'];
		$data['address'] = trim($_POST['address']);
		$data['mobile'] = trim($_POST['mobile']);
		$data['phone'] = trim($_POST['phone']);
		$data['email'] = trim($_POST['email']);
		$data['fax'] = trim($_POST['fax']);
		$data['postcode'] = trim($_POST['postcode']);
		if($_POST['open_hour']){
			$open_hour = $_POST['open_hour'];
			$open_minute = $_POST['open_minute']?$_POST['open_minute']:"00";
			$data['open'] = $open_hour.":".$open_minute;
		}
		if($_POST['close_hour']){
			$close_hour = $_POST['close_hour'];
			$close_minute = $_POST['close_minute']?$_POST['close_minute']:"00";
			$data['close'] = $close_hour.":".$close_minute;
		}
		$data['longitude'] = $_POST['longitude']?floatval($_POST['longitude']):null;
		$data['latitude'] = $_POST['latitude']?floatval($_POST['latitude']):null;
		$data['bus_lines']=$_POST['bus_lines'];
		$data['subway_lines']=$_POST['subway_lines'];
		$data['introduction']=$_POST['introduction'];

		$data['weekday']=implode(",",$_POST['weekday']);

		$data['update_time'] = time();
		$save_result=$obj_store->save($data);
		if($save_result){
			$msg='门店保存成功';
			if($obj_operatorlogs = kernel::service('operatorlog')){
					if(method_exists($obj_operatorlogs,'inlogs') && $_POST['log']) {
						$memo = '编辑门店:门店ID('.$_POST['store_id'].')门店名称('.$_POST['store_name'].')门店状态由('.$this->status[$oldstore['status']].'改为'.$this->status[$_POST['status']].')操作员备注('.$_POST['log'].')';
						$obj_operatorlogs->inlogs($memo, '门店', 'physical');
					}
			}
		}else{
			$msg='保存失败，请检查后重试';
		}
        $this->end($save_result,app::get('physical')->_($msg));
    }

	function package_attach($store_id){
        $this->pagedata['store_id'] = $store_id;

		$obj_attach = &$this->app->model('store_package_attach');
		$list = $obj_attach->getList("package_id",array("store_id"=>$store_id));
		$package_info = array();
        foreach($list as $key => $val){
			$package_info['linkid'][] = $val['package_id'];
            $package_info['info'][] = array('id'=>$val['package_id']);
        }
        $package_info['linkid'] = implode(",",$package_info['linkid']);
        $package_info['info'] = json_encode($package_info['info']);
        $this->pagedata['package_info'] = $package_info;

        $this->display('admin/store/attach.html');
    }
	
	function add_attach(){
		$this->begin('index.php?app=physical&ctl=admin_store&act=index');
		$obj_attach = &$this->app->model('store_package_attach');
		$obj_attach->delete(array("store_id"=>$_POST['store_id']));
        if($_POST['package_ids']){
			$package_arr=explode(",",$_POST['package_ids']);
			$n = 0 ;
			foreach($package_arr as $key => $val){
				$data[$n]['store_id'] = $_POST['store_id'];
				$data[$n]['package_id'] = $val;
				$obj_attach->save($data[$n]);
				$n++;
			}
		}

		$this->end(true,app::get('physical')->_('关联套餐成功'));
	}
}