<?php
class physical_finder_project{    
    var $column_edit = '编辑';
	var $column_edit_order = COLUMN_IN_HEAD;
    function column_edit($row){
        return '<a href="index.php?app=physical&ctl=admin_project&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['project_id'].'" target="dialog::{title:\''.app::get('physical')->_('编辑项目').'\', width:500, height:350}">'.app::get('physical')->_('编辑').'</a>';
        
    }  
}
