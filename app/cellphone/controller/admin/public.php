<?php


  class cellphone_ctl_admin_public extends desktop_controller{
     
	 
	 function __construct($app){
        parent::__construct($app);
        $this->router = app::get('desktop')->router();
        $this->_request = kernel::single('base_component_request');
    }

    function index(){ 
		$this->pagedata['article_id'] = $this->app->getConf('cellphone.art_id');
		$this->page('admin/public/add.html');
		
	}

	 function toAdd(){
    
		 $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_public','act'=>'index'))); 
		 $data = $_POST;
		 if($data['article_id']){
			$this->app->setConf('cellphone.art_id',$data['article_id']);
			$this->end(true,'保存成功');
		 }else{
			$this->end(false,'保存失败');
		 }
	  
	 }

	 

}



