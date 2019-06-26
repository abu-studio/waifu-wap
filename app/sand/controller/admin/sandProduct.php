<?php
 

class sand_ctl_admin_sandProduct extends desktop_controller{

    var $workground = 'b2c.wrokground.goods';
	public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    function basic_setting(){
        
        $aOut = $this->get_basic_setting();
		$this->pagedata['goodsselect_filter'] = array('marketable'=>'true','goods_kind|in'=>array('virtual','entity','card'));
        $this->pagedata['setting']= $aOut;
		$this->page('admin/sandproduct.html');
    }
    
    function to_setting(){
        $this->begin();
        $aOut = $this->save_setting($_POST);
        $this->end('success',app::get('b2c')->_('设置成功'));
    }

	//设置提交
    function save_setting($aData){
        app::get('site')->setConf('sand.product', $aData['product']);
    }

	//获取设置
	function get_basic_setting(){
        $aOut['product'] = app::get('site')->getConf('sand.product') ? app::get('site')->getConf('sand.product'): '';
		$aOut['companys'] = app::get('site')->getConf('sand.company') ? app::get('site')->getConf('sand.company'): array();
		asort($aOut['companys']);
        return $aOut;
    }

	function addCompany(){
		$company = strtoupper($_POST['company']);
		$companys = app::get('site')->getConf('sand.company') ? app::get('site')->getConf('sand.company'): array();
		array_unshift($companys,$company);
		$companys =  array_filter($companys);
		app::get('site')->setConf('sand.company', $companys);
	}

	function delCompany(){
		$company = strtoupper($_POST['company']);
		$companys = app::get('site')->getConf('sand.company') ? app::get('site')->getConf('sand.company'): array();
		$key = array_search($company,$companys);
		unset($companys[$key]);
		$companys =  array_filter($companys);
		app::get('site')->setConf('sand.company', $companys);
	}


}	