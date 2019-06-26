<?php
class physical_finder_organization{   
	function __construct($app){
        $this->app = $app;
    }
    var $column_edit = '编辑';
	var $column_edit_order = COLUMN_IN_HEAD;
    function column_edit($row){
        return '<a href="index.php?app=physical&ctl=admin_organization&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['organization_id'].'" target="dialog::{title:\''.app::get('physical')->_('编辑机构').'\', width:500, height:400}">'.app::get('physical')->_('编辑').'</a>';
        
    }
	var $column_goods_pic = "缩略图";
    var $column_goods_pic_order = COLUMN_IN_HEAD;
    var $column_goods_pic_order_field = 'logo';
    function column_goods_pic($row){
        $obj_organization =  app::get('physical')->model('organization');
        $g =$obj_organization->dump(array('organization_id'=>$row['organization_id']),'logo');
        $img_src = base_storager::image_path($g['logo'],'s' );
        if(!$img_src)return '';
        return "<a href='$img_src' class='img-tip pointer' target='_blank' onmouseover='bindFinderColTip(event);'><span>&nbsp;pic</span></a>";
    }
}
