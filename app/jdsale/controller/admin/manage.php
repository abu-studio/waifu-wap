<?php
/**
*京东设置类
*
**/ 
class jdsale_ctl_admin_manage extends desktop_controller{
	
	var $workground = 'jdsale.workground.order';
	public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    function index(){
        
        $aOut = $this->get_basic_setting();
        $this->pagedata['setting']= $aOut;
		$this->page('admin/store_set.html');
    }
    
    function to_setting(){
        $this->begin();
        $aOut = $this->save_setting($_POST);
        $this->end('success',app::get('b2c')->_('设置成功'));
    }

	//设置提交
    function save_setting($aData){
        app::get('site')->setConf('jdsale.shopId', $aData['shopId']);
        //lpc 保存京东图书店铺id
        app::get('site')->setConf('jdbook.shopId', $aData['bookshopId']);
    }

	//获取设置
	function get_basic_setting(){
        $aOut['shopId'] = app::get('site')->getConf('jdsale.shopId') ? app::get('site')->getConf('jdsale.shopId'): '';
        //lpc
        $aOut['bookshopId'] = app::get('site')->getConf('jdbook.shopId') ? app::get('site')->getConf('jdbook.shopId'): '';
        return $aOut;
    }
	
}
