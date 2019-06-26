<?php
class physical_finder_package_type{    
    var $column_edit = '编辑';
	var $column_edit_order = COLUMN_IN_HEAD;
    function column_edit($row){
        return '<a href="index.php?app=physical&ctl=admin_package_type&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['type_id'].'" target="dialog::{title:\''.app::get('physical')->_('编辑套餐类型').'\', width:500, height:150}">'.app::get('physical')->_('编辑').'</a>';
        
    }  
}
