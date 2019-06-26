<?php
class physical_finder_package{ 
	var $detail_projects = '包含体检项目';
	var $detail_projects_order = COLUMN_IN_HEAD;
	var $detail_attach = '关联门店';
	var $detail_attach_order = COLUMN_IN_HEAD;
    var $column_control = '操作';
    var $column_control_width = 100;
	var $column_control_order = COLUMN_IN_HEAD;

    function __construct($app){
        $this->app = $app;
    }

	function detail_projects($package_id){
		$render = $this->app->render();
        $package_info = app::get('physical') -> model('package') -> dump($package_id,'project_ids');
		$arr = explode(",",$package_info['project_ids']);

		$subject_list = app::get('physical') -> model('subject') -> getList('subject_id,subject_name');
		foreach($subject_list as $val){
			$subject_arr[$val['subject_id']] = $val['subject_name'];
		}

		$project_list = app::get('physical') -> model('project') -> getList('project_name,project_code,subject_id,medical_code,price',array("project_id"=>$arr));
		foreach($project_list as $key=>$val){
			$project_list[$key]['subject_name'] = $subject_arr[$val['subject_id']];
		}

		$render->pagedata['project_list']=$project_list;
        return $render->fetch('admin/package/detail/project_list.html');
    }

	function detail_attach($package_id){
		$render = $this->app->render();
        $attach_list = app::get('physical') -> model('package') -> get_attach_list($package_id);
		$render->pagedata['attach_list']=$attach_list;
        return $render->fetch('admin/package/detail/attach_list.html');
    }
	
	public function column_control($row){
        $render = $this->app->render();
        $arr = array(
            'app'=>$_GET['app'],
            'ctl'=>$_GET['ctl'],
            'act'=>$_GET['act'],
            'finder_id'=>$_GET['_finder']['finder_id'],
            'action'=>'detail',
            'finder_name'=>$_GET['_finder']['finder_id'],
        );
        $arr_link = array(
            'info'=>array(
                'detail_edit'=>array(
                    'href'=>'index.php?app=physical&ctl=admin_package&act=edit&&p[0]='.$row['package_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'],
                    'label'=>app::get('physical')->_('编辑套餐'),
                    'target'=>'dialog::{title:\''.app::get('physical')->_('编辑套餐').'\', width:500, height:350}',
                ),
				'store_attach'=>array(
                    'href'=>'index.php?app=physical&ctl=admin_package&act=store_attach&&p[0]='.$row['package_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'],
                    'label'=>app::get('physical')->_('门店关联'),
                    'target'=>'dialog::{title:\''.app::get('physical')->_('门店关联').'\', width:800, height:500}',
                ),
            ),


        );

       
        $render->pagedata['arr_link'] = $arr_link;
        $render->pagedata['handle_title'] = app::get('business')->_('编辑');
        $render->pagedata['is_active'] = 'true';
        return $render->fetch('admin/actions.html');
    }

	var $column_goods_pic = "缩略图";
    var $column_goods_pic_order = COLUMN_IN_HEAD;
    var $column_goods_pic_order_field = 'image';
    function column_goods_pic($row){
        $obj_package =  app::get('physical')->model('package');
        $g =$obj_package->dump(array('package_id'=>$row['package_id']),'image');
        $img_src = base_storager::image_path($g['image'],'s' );
        if(!$img_src)return '';
        return "<a href='$img_src' class='img-tip pointer' target='_blank' onmouseover='bindFinderColTip(event);'><span>&nbsp;pic</span></a>";
    }
}
