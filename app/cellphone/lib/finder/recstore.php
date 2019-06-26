<?php

class cellphone_finder_recstore{
 
    function __construct($app){
        $this->app = $app;
        $this->router = app::get('desktop')->router();
        $this->store = $this->app->model('recstore');
    }//End
    

 	var $column_edit = '操作';
	public $column_edit_width = 110;

    function column_edit($row){


        $row = $this->store->getList('*',array('id'=>$row['id']));
        $row = $row[0];
		$html = '';
		
		if($row['is_active']=='false'){
				$html .= '<a href="'. $this->router->gen_url( array('app'=>'cellphone','ctl'=>'admin_recstore','act'=>'openActivity','id'=>$row['id']) ) .'" >'.app::get('cellphone')->_('开启').'</a>&nbsp;&nbsp;';
		}else{
				$html .= '<a href="'. $this->router->gen_url( array('app'=>'cellphone','ctl'=>'admin_recstore','act'=>'closeActivity','id'=>$row['id']) ) .'" >'.app::get('cellphone')->_('关闭').'</a>&nbsp;&nbsp;';
			}
	 
        return $html;

    }
    
 
 }
