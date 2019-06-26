<?php
class physical_finder_subject{    
    var $column_edit = '编辑';
	var $column_edit_order = COLUMN_IN_HEAD;
    function column_edit($row){
        return '<a href="index.php?app=physical&ctl=admin_subject&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['subject_id'].'" target="dialog::{title:\''.app::get('physical')->_('编辑科目').'\', width:500, height:200}">'.app::get('physical')->_('编辑').'</a>';
        
    }  
}
