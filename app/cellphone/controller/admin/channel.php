<?php

 class cellphone_ctl_admin_channel extends desktop_controller{
     
	 
	 function __construct($app){
        parent::__construct($app);
        $this->router = app::get('desktop')->router();
        $this->_request = kernel::single('base_component_request');
    }


     function index(){
	 
	 $this->finder('cellphone_mdl_channel',
		    array('actions' =>array(
                  array(
                    'label' => app::get('cellphone')->_('添加商品'),
                    'icon' => 'add.gif',
                    'href' => 'index.php?app=cellphone&ctl=admin_channel&act=add',
                   // 'target' => "_blank",
                    ),
                  ),
		    	'title'=>'频道商品列表',    
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_tagedit'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'allow_detail_popup'=>true,
                'use_view_tab'=>false,));

	 }

	 function add(){
     $channeltype = $this->app->model('channeltype');
	 $type = $channeltype->getList('type_id,type_name');
     $this->pagedata['channeltype'] = $type;
     $this->pagedata['goods_filter'] = array('marketable'=>'true','goods_type'=>'normal','act_type'=>'normal','goods_kind'=>'virtual');
	 $this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channel','act'=>'index'));
	 $this->page('admin/channel/add.html');
	  }

     function edit(){
	 
	    $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channel','act'=>'index')));
        $channeltype = $this->app->model('channeltype');
	    $type = $channeltype->getList('type_id,type_name');
        $this->pagedata['channeltype'] = $type;
        $id = $this->_request->get_get('id');
        $channel = $this->app->model('channel');
	
        $data = $channel->getList('*',array('id'=>$id));

		if($data['is_active']=='true'){	
         
			$this->end(false,'不能编辑已开启的项');
		}
        $this->pagedata['channel'] = $data[0];
		$this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channel','act'=>'index'));
        $this->page('admin/channel/add.html');
         
	 }


	 function toAdd(){
		 
     $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channel','act'=>'index'))); 
	 $data = $this->get_data();
	 $channel = $this->app->model('channel');
	 if($data['id']){
	 $re = $channel->update($data,array('id'=>$data['id']));
	 }
	 else{
		
	  $re = $channel->save($data);
	 }
	 if($re){
	 $this->end(true,'保存成功');
	 }
	 else{
	 $this->end(false,'保存失败');
	 }
	  
	 }


	 public function get_data(){

      $data = $this->_request->get_post();;
      if(!$data['type_id']){
	     $this->end(false,'请选择频道页');
	  }
	  if(!$data['goods_id']){
	     $this->end(false,'请选择商品');
	  }
	  if(!$data['d_order']){
	     $this->end(false,'请填写排序');
	  }
	  if(!$data['start_time']||!$data['end_time']){
           $this->end(false,'请填写完整的时间段');
       }
      $data['start_time'] = strtotime($data['start_time'].' '.$data['_DTIME_']['H']['start_time'].':'.$data['_DTIME_']['M']['start_time']);
      $data['end_time'] = strtotime($data['end_time'].' '.$data['_DTIME_']['H']['end_time'].':'.$data['_DTIME_']['M']['end_time']);
      if($data['end_time']<=$data['start_time']){
           $this->end(false,'开始时间要大于结束时间');
       }
	 /* if(!$data['image_id']){
	       $this->end(false,'请上传图片');   
	  }*/


       return $data; 
    }

     public function openActivity(){
		$this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channel','act'=>'index')));
		$id = $this->_request->get_get('id');
		$channel = $this->app->model('channel');
		$re = $channel->update(array('is_active'=>'true'),array('id'=>$id));
		if($re){
			$this->end(true,'保存成功');
		}else{
			$this->end(false,'保存失败');
		}
	}
	 public function closeActivity(){
		$this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_channel','act'=>'index')));
		$id = $this->_request->get_get('id');
		$channel = $this->app->model('channel');
	    $re=
	    $channel->update(array('is_active'=>'false'),array('id'=>$id));
		if($re){
			$this->end(true,'保存成功');
		}else{
			$this->end(false,'保存失败');
		}
	}

   
	

}