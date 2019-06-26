<?php
/**
* 水电煤默认商品设置
*
**/ 
class b2c_ctl_admin_apply extends desktop_controller{
				
	//店铺设置
	function index(){		
		$kv_obj=kernel::single('base_mdl_kvstore');
		if($_POST['jiaofei_set']=='jiaofei_set'){
			$_POST['jiaofei']['key']='jiaofei.store.set';
			$_POST['jiaofei']['prefix']='system';
			$_POST['jiaofei']['dateline']=time();
			$kv_obj->save($_POST['jiaofei']);
		}
		$jiaofei=$kv_obj->getList('*',array('key'=>'jiaofei.store.set'));
		$this->pagedata['goodsselect_filter'] = array('marketable'=>'true','goods_kind|in'=>array('virtual','entity','card'));
		$this->pagedata['jiaofei']=$jiaofei[0];
		$this->page('admin/apply/jiaofei_set.html');
	}
	
	function water(){		
		$kv_obj=kernel::single('base_mdl_kvstore');
		if($_POST['jiaofei_set']=='jiaofei_set'){
			//xhk 2015/11/05 后台配置缴费规则
			$_POST['water_rules']['key']='water.rules.set';
			$_POST['water_rules']['prefix']='system';
			$_POST['water_rules']['dateline']=time();
			$kv_obj->save($_POST['water_rules']);
			$_POST['water_guide']['key']='water.guide.set';
			$_POST['water_guide']['prefix']='system';
			$_POST['water_guide']['dateline']=time();
			$kv_obj->save($_POST['water_guide']);
		}
		$water_rules=$kv_obj->getList('*',array('key'=>'water.rules.set'));
		$this->pagedata['water_rules']=$water_rules[0];
		$water_guide=$kv_obj->getList('*',array('key'=>'water.guide.set'));
		$this->pagedata['water_guide']=$water_guide[0];
		$this->page('admin/apply/water_set.html');
	}
	
	function electricity(){		
		$kv_obj=kernel::single('base_mdl_kvstore');
		if($_POST['jiaofei_set']=='jiaofei_set'){
			//xhk 2015/11/05 后台配置缴费规则
			$_POST['electricity_rules']['key']='electricity.rules.set';
			$_POST['electricity_rules']['prefix']='system';
			$_POST['electricity_rules']['dateline']=time();
			$kv_obj->save($_POST['electricity_rules']);
			$_POST['electricity_guide']['key']='electricity.guide.set';
			$_POST['electricity_guide']['prefix']='system';
			$_POST['electricity_guide']['dateline']=time();
			$kv_obj->save($_POST['electricity_guide']);
		}
		$electricity_rules=$kv_obj->getList('*',array('key'=>'electricity.rules.set'));
		$this->pagedata['electricity_rules']=$electricity_rules[0];
		$electricity_guide=$kv_obj->getList('*',array('key'=>'electricity.guide.set'));
		$this->pagedata['electricity_guide']=$electricity_guide[0];
		$this->page('admin/apply/electricity_set.html');
	}
	function gas(){		
		$kv_obj=kernel::single('base_mdl_kvstore');
		if($_POST['jiaofei_set']=='jiaofei_set'){
			//xhk 2015/11/05 后台配置缴费规则
			$_POST['gas_rules']['key']='gas.rules.set';
			$_POST['gas_rules']['prefix']='system';
			$_POST['gas_rules']['dateline']=time();
			$kv_obj->save($_POST['gas_rules']);
			$_POST['gas_guide']['key']='gas.guide.set';
			$_POST['gas_guide']['prefix']='system';
			$_POST['gas_guide']['dateline']=time();
			$kv_obj->save($_POST['gas_guide']);
		}
		$gas_rules=$kv_obj->getList('*',array('key'=>'gas.rules.set'));
		$this->pagedata['gas_rules']=$gas_rules[0];
		$gas_guide=$kv_obj->getList('*',array('key'=>'gas.guide.set'));
		$this->pagedata['gas_guide']=$gas_guide[0];
		$this->page('admin/apply/gas_set.html');
	}
	
	function recharge(){		
		$kv_obj=kernel::single('base_mdl_kvstore');
		if($_POST['jiaofei_set']=='jiaofei_set'){
			//xhk 2015/11/05 后台配置缴费规则
			//水电煤
			$_POST['recharge_rules']['key']='recharge.rules.set';
			$_POST['recharge_rules']['prefix']='system';
			$_POST['recharge_rules']['dateline']=time();
			$kv_obj->save($_POST['recharge_rules']);
		}
		$recharge_rules=$kv_obj->getList('*',array('key'=>'recharge.rules.set'));
		$this->pagedata['recharge_rules']=$recharge_rules[0];
		$this->page('admin/apply/recharge_set.html');
	}
}