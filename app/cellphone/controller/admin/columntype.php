<?php


 class cellphone_ctl_admin_columntype extends desktop_controller{
     
	 
	 function __construct($app){
        parent::__construct($app);
        $this->router = app::get('desktop')->router();
        $this->_request = kernel::single('base_component_request');
    }


	function index(){
	
	$this->finder('cellphone_mdl_columntype',
		array('actions' =>array(
                  array(
                    'label' => app::get('cellphone')->_('添加类型'),
                    'icon' => 'add.gif',
                    'href' => 'index.php?app=cellphone&ctl=admin_columntype&act=add',
                   // 'target' => "_blank",
                    ),
                        ),
				'base_filter'=>array('type'=>'column'),
		    	'title'=>'栏目类型列表',    
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_tagedit'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'allow_detail_popup'=>true,
                'use_view_tab'=>false,));
	}


	 function add_concentration(){
		 $cat_filter = array('parent_id'=>'0');
		 $this->pagedata['cat_filter'] = $cat_filter;
		 $this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_columntype','act'=>'concentration'));
		 $this->page('admin/columntype/add_concentration.html');
	  }

	  function add(){
		 $cat_filter = array('parent_id'=>'0');
		 $this->pagedata['cat_filter'] = $cat_filter;
		 $this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_columntype','act'=>'index'));
		 $this->page('admin/columntype/add.html');
	  }
     

     function edit(){
        $columntype_id = $this->_request->get_get('columntype_id');
        $columntype = $this->app->model('columntype');
        $data = $columntype->getList('*',array('columntype_id'=>$columntype_id));
		if($data[0]['type'] == 'column'){
			$this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_columntype','act'=>'index')));
			$this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_columntype','act'=>'index'));

		}else{
			$this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_columntype','act'=>'concentration')));
			$this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_columntype','act'=>'concentration'));
		}
        $this->pagedata['item'] = $data[0];
        $cat_filter = array('parent_id'=>'0');
        $this->pagedata['cat_filter'] = $cat_filter;
		$this->pagedata['columntype_id'] = $columntype_id;
		if($data[0]['type'] == 'column'){
			$this->page('admin/columntype/add.html');
		 }else{
			$this->page('admin/columntype/add_concentration.html');
		 }

     }

     function toAdd(){
		 $data = $this->get_data();
		 if($data['type'] == 'column'){
			$this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_columntype','act'=>'index'))); 
		 }else{
			 $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_columntype','act'=>'concentration'))); 
		 }
         
         $columntype = $this->app->model('columntype');
         if($data['columntype_id']){
            $re = $columntype->update($data,array('columntype_id'=>$data['columntype_id']));
         }else{
          $re = $columntype->save($data);
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
      if(!$data['columntype_createtime']){
         $this->end(false,'录入时间不为空');
      }
      if(!$data['d_order']){
         $this->end(false,'排序不能为空');
      }
      if($data['d_order']&&!is_numeric($data['d_order'])){
         $this->end(false,'排序必须为数字');
      }
      if(!$data['cat_id']){
           $this->end(false,'请选择分类');
      }
     $data['columntype_createtime'] = strtotime($data['columntype_createtime']);
     $item['columntype_name']= $data['columntype_name'];
     $item['columntype_createtime']= $data['columntype_createtime'];
     $item['d_order']= $data['d_order'];
     $item['columntype_description']= $data['columntype_description'];
     $item['cat_id']= $data['cat_id'];
	 $item['type']= $data['type'];
	 $item['image_id']= $data['image_id'];
	 if($data['columntype_id']){
		 $item['columntype_id']= $data['columntype_id'];
	 }
       return $item; 
    }


	function concentration(){
	
	$this->finder('cellphone_mdl_columntype',
		array('actions' =>array(
			array(
				'label' => app::get('cellphone')->_('添加精选商品'),
				'icon' => 'add.gif',
				'href' => 'index.php?app=cellphone&ctl=admin_columntype&act=add_concentration',
				// 'target' => "_blank",
			),
		),
		'base_filter'=>array('type'=>'concentration'),
		'title'=>'精选商品列表',    
		'use_buildin_set_tag'=>false,
		'use_buildin_filter'=>false,
		'use_buildin_tagedit'=>false,
		'use_buildin_export'=>false,
		'use_buildin_import'=>false,
		'allow_detail_popup'=>true,
		'use_view_tab'=>false,));
	}

}