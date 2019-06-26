<?php
class physical_finder_store_status{    
    var $column_edit = '编辑';
	var $column_edit_order = COLUMN_IN_HEAD;
    function column_edit($row){
        return '<a href="index.php?app=physical&ctl=admin_store_status&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'" target="dialog::{title:\''.app::get('physical')->_('编辑预约时段').'\', width:500, height:300}">'.app::get('physical')->_('编辑').'</a>';
        
    } 
	
	var $column_start = '开始日期';
	function column_start($row){
		$store_status_info = app::get('physical') -> model('store_status') -> dump($row['id'],'start_date');
		return $store_status_info['start_date'];
	}

	var $column_end = '结束日期';
	function column_end($row){
		$store_status_info = app::get('physical') -> model('store_status') -> dump($row['id'],'end_date');
		return $store_status_info['end_date'];
	}
}
