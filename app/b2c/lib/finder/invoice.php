<?php


class b2c_finder_invoice{
    var $column_control = '操作';
    function __construct($app){
        $this->app = $app;
    }


    function column_control($row){
		$returnValue ='<a href="index.php?app=b2c&ctl=admin_invoice&act=add_send&id='.$row['id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'].'"  target="dialog::{title:\''.app::get('b2c')->_('处理信息').'\', width:500, height:300}">'.app::get('b2c')->_('处理信息').'</a>';
        return $returnValue;
    }

}
