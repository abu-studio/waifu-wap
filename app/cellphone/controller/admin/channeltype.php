<?php


 class cellphone_ctl_admin_channeltype extends desktop_controller{
     
	 
	 function __construct($app){
        parent::__construct($app);
        $this->router = app::get('desktop')->router();
        $this->_request = kernel::single('base_component_request');
    }


	function index(){
	
	$this->finder('cellphone_mdl_channeltype',
		array('actions' =>array(
                  array(
                    'label' => app::get('cellphone')->_('添加类型'),
                    'icon' => 'add.gif',
                    'href' => 'index.php?app=cellphone&ctl=admin_channeltype&act=add',
                   // 'target' => "_blank",
                    ),
                        ),
		    	'title'=>'频道类型列表',    
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_tagedit'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'allow_detail_popup'=>true,
                'use_view_tab'=>false,));
	}


	 function add(){
	 $this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channeltype','act'=>'index'));
	 $this->page('admin/channeltype/add.html');
	  }
     

     function edit(){
     
        $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channeltype','act'=>'index')));
        $type_id = $this->_request->get_get('type_id');
        $channeltype = $this->app->model('channeltype');
        $data = $channeltype->getList('*',array('type_id'=>$type_id));
        $this->pagedata['item'] = $data[0];
        $this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channeltype','act'=>'index'));
        $this->page('admin/channeltype/add.html');
        

     }

     function toAdd(){
         $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channeltype','act'=>'index'))); 
         $data = $this->get_data();
         $channeltype = $this->app->model('channeltype');
         if($data['type_id']){
            $re = $channeltype->update($data,array('type_id'=>$data['type_id']));
         }else{
          $re = $channeltype->save($data);
         }
         if($re){
            $this->end(true,'保存成功');
         }
         else{
            $this->end(false,'保存失败');
         }
      
     }

     //
    public function get_data(){
      $data = $this->_request->get_post();
      if(!$data['type_name']){
         $this->end(false,'频道类型不为空');
      }
      if(!$data['createtime']){
         $this->end(false,'录入时间不为空');
      }
     
      if(!$data['d_order']){
         $this->end(false,'排序不能为空');
      }
      if($data['d_order']&&!is_numeric($data['d_order'])){
         $this->end(false,'排序必须为数字');
      }
     if(!$data['image_id']){
           $this->end(false,'请上传图片');   
      }
     $data['createtime'] = strtotime($data['createtime']);
     $item['type_id']= $data['type_id'];
     $item['type_name']= $data['type_name'];  
     $item['createtime']= $data['createtime'];
     $item['d_order']= $data['d_order'];
     $item['image_id']= $data['image_id'];
    

       return $item; 
    }

}