<?php

class cellphone_finder_channeltype{
 
    function __construct($app){
        $this->app = $app;
        $this->router = app::get('desktop')->router();
        //$this->banner = $this->app->model('banner');
    }//End
    

 	var $column_edit = '编辑';
	//public $column_edit_width = 110;

     function column_edit($row){
        return '<a href="index.php?app=cellphone&ctl=admin_channeltype&act=edit&type_id='.$row['type_id'].'">编辑</a>';
    }
 
    var $column_picture = '缩略图';
    function column_picture($row){
        $channeltype =  app::get('cellphone')->model('channeltype');
		$g = $channeltype->db_dump(array('type_id'=>$row['type_id']),'image_id');
		$img_id= base_storager::image_path($g['image_id'],'s' );
		if(!$img_id)return '';
		return "<a href='$img_id' class='img-tip pointer' target='_blank'
		        onmouseover='bindFinderColTip(event);'>
		<span>&nbsp;pic</span></a>";
      }
 
 
 }

