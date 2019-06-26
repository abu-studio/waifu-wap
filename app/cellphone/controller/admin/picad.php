<?php


  class cellphone_ctl_admin_picad extends desktop_controller{
     
	 
	 function __construct($app){
        parent::__construct($app);
        $this->router = app::get('desktop')->router();
        $this->_request = kernel::single('base_component_request');
    }

      function index(){ 
		  
		  $this->finder('cellphone_mdl_picad',
              array(
			'actions' =>array(
                  array(
                    'label' => app::get('cellphone')->_('添加'),
                    'icon' => 'add.gif',
                    'href' => 'index.php?app=cellphone&ctl=admin_picad&act=add',
                   // 'target' => "_blank",
                    ),

                  array('label' => "批量操作",
                        'icon' => 'batch.gif',
                        'group' => array(
                        
                            array('label' => app :: get('cellphone') -> _('排序'), 'icon' => 'download.gif', 'submit' => 'index.php?app=cellphone&ctl=admin_picad&act=singleBatchEdit&p[0]=dorder', 'target' => 'dialog'),
					                    ),
                        ),

                  ),
		    	'title'=>'图片广告列表',    
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_tagedit'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'allow_detail_popup'=>true,
                'use_view_tab'=>false,
                ));
	  
	  
	  
	  
	  }

     function add(){
	 $this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_picad','act'=>'index'));
	 $this->page('admin/picad/add.html');
	  }

	 function changeType(){
	    $render = $this -> app -> render();
        $select_type = $_POST['select_type'];

	   if($select_type == "goods"){
        echo $render -> fetch('admin/picad/goods.html');
	   }
	   if($select_type == "activity"){
        echo $render->fetch('admin/picad/activity.html');
	   }
	   if($select_type =="article"){
        echo $render->fetch('admin/picad/article.html');
	   }

	 
	 }


	 function toAdd(){
    
     $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_picad','act'=>'index'))); 
	 $data = $this->get_data();
	// echo "<pre>";
		//print_r($data);
		//exit;
	 $picad = $this->app->model('picad');
	 if($data['id']){
	 $re = $picad->update($data,array('id'=>$data['id']));
	 }
	 else{
	  $re = $picad->save($data);
	 }
	 if($re){
	 $this->end(true,'保存成功');
	 }
	 else{
	 $this->end(false,'保存失败');
	 }
	  
	 }

	 function edit(){
	 
	    $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_picad','act'=>'index')));
        
        $id = $this->_request->get_get('id');
        $picad = $this->app->model('picad');
        $data = $picad->getList('*',array('id'=>$id));
		//echo '<pre>';
		//print_r($data);
		//exit;
		if($data['is_active']=='true'){	
         
			$this->end(false,'不能编辑已开启的项');
		}

        $this->pagedata['item'] = $data[0];
		$this->pagedata['reUrl'] = $this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_picad','act'=>'index'));
        $this->page('admin/picad/add.html');
        
	 
	 }
	 public function get_data(){

      $data = $this->_request->get_post();;
      if(!$data['associate_type']){
	     $this->end(false,'请选择类型');
	  }
	  if(!$data['associate_id']){
	     $this->end(false,'请选择具体ID');
	  }
	  
	  if(!$data['start_time']||!$data['end_time']){
           $this->end(false,'请填写完整的时间段');
       }
      $data['start_time'] = strtotime($data['start_time'].' '.$data['_DTIME_']['H']['start_time'].':'.$data['_DTIME_']['M']['start_time']);
      $data['end_time'] = strtotime($data['end_time'].' '.$data['_DTIME_']['H']['end_time'].':'.$data['_DTIME_']['M']['end_time']);
      if($data['end_time']<=$data['start_time']){
           $this->end(false,'开始时间要大于结束时间');
       }
	  if(!$data['image_id']){
	       $this->end(false,'请上传图片');   
	  }


       return $data; 
    }


	public function openActivity(){
		$this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_picad','act'=>'index')));
		$id = $this->_request->get_get('id');
		$picad = $this->app->model('picad');
		$re = $picad->update(array('is_active'=>'true'),array('id'=>$id));
		if($re){
			$this->end(true,'保存成功');
		}else{
			$this->end(false,'保存失败');
		}
	}
	public function closeActivity(){
		$this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_picad','act'=>'index')));
		$id = $this->_request->get_get('id');
		$picad = $this->app->model('picad');
	    $re=
	    $picad->update(array('is_active'=>'false'),array('id'=>$id));
		if($re){
			$this->end(true,'保存成功');
		}else{
			$this->end(false,'保存失败');
		}
	}



	 function singleBatchEdit($editType = '') {
        // $objshopinfo = &$this->app->model('storegrade');
        $newFilter = $_POST;
        unset($newFilter['app']);
        unset($newFilter['ctl']);
        unset($newFilter['act']);
        unset($newFilter['_finder']);
        unset($newFilter['marketable']);
        unset($newFilter['_DTYPE_BOOL']);

        if ($_POST['isSelectedAll'] == '_ALL_')
            $_POST['id'][0] = '_ALL_';
        if (count($_POST['id']) == 0 && $_POST['_finder']['select'] != 'multi' && !$_POST['_finder']['id'] && !$_POST['filter']) {
            echo __('请选择记录');
            exit;
        } 
        if ($_POST['filter']) {
            $_POST['_finder'] = unserialize($_POST['filter']);
            $editType = $_POST['updateAct'];
        } 
        // 选中记录的ID经序列化后保存于页面
        $this -> pagedata['filter'] = serialize($_POST['id']);
        $this -> pagedata['catfilter'] = array('parent_id|noequal'=>0);
        $this -> pagedata['editInfo'] = array('count' => count($_POST['id']));

        $this -> display('admin/picad/batch/batchEdit' . $editType . '.html');
    } 

     function saveBatchEdit() { 

        $filter = unserialize($_POST['filter']); 
        //echo '<pre>';
		//print_r($_POST);
		//exit;
        switch($_POST['updateAct']){
		
		 case 'dorder':
        $this->begin($this->router->gen_url(array('app'=>'cellphone','ctl'=>'admin_picad','act'=>'index')));
        $picad = $this->app->model('picad');
		$result = true;
		foreach($filter as $val){
		if(!$picad->update(array('d_order'=>$_POST['orderval']),array('id'=>$val))){
        $result=false;
        $this->end(false,'操作失败');
		
		   }
		
       }
	   if($result){
	     $this->end(true,'批量操作成功');
		
	   }
       else{
	    echo $GLOBALS['php_errormsg'];
	   }
		
		break;
		
		default:
		break;

		}

       
      } 

}



