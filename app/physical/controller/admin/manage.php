<?php
 
/**
 * 体检后台默认设置类
 */
class physical_ctl_admin_manage extends desktop_controller 
{
    /**
     * @var 定义控制器属于哪个菜单区域
     */
    var $workground = 'image_ctl_admin_manage';    /**
     * 图片大小配置
     * @param nulll
     * @return string 显示配置页面内容
     */
    function imageset(){
        header("cache-control: no-store, no-cache, must-revalidate");
        $image = &app::get('image')->model('image');
		$obj=kernel::single('base_mdl_kvstore');
		$kv_image_set=$obj->getList('*',array('key'=>'physical.image.set','prefix'=>'system'));
       $allsize = array();
        if($_POST['pic']){
            $image_set = $_POST['pic'];
			$data['id']=$kv_image_set[0]['id'];
			$data['prefix']='system';
			$data['key']='physical.image.set';
			$data['value']=$image_set;
			$data['dateline']=time();
			$obj->save($data);
        $kv_image_set=$obj->getList('*',array('key'=>'physical.image.set','prefix'=>'system')); 
        }
        $def_image_set =$kv_image_set[0]['value'];
		if(!$def_image_set){
			$def_image_set=array (
          'org' => 
          array (
            'default_image' => '23b79dd6a8f437f09870fdb90e41815e',
            'width' => '300',
            'height' => '300',
            'wm_type' => 'none',
            'wm_text' => '',
            'wm_image' => '',
            'wm_opacity' => '',
            'wm_loc' => '',
            'title'=>'机构默认图',
            'watermark'=>0
          ),
          'store' => 
          array (
            'default_image' => '4b66a4c21450e0ef2ff9632b4b8e5716',
            'width' => '200',
            'height' => '200',
            'wm_type' => 'none',
            'wm_text' => '',
            'wm_image' => '',
            'wm_opacity' => '',
            'wm_loc' => '',
            'title'=>'门店默认图',
            'watermark'=>0
          ),
          /*'S' => 
          array (
            'default_image' => '17d39d7daefc0644caf5ff7fe22f516f',
            'width' => '120',
            'height' => '140',
            'title'=>'列表页缩略图'
         ),*/
    );
		}
		$minsize_set = false;
		foreach($def_image_set as $k=>$v){
			if(!$minsize_set||$v['height']<$minsize_set['height']){
				$minsize_set = $v;
			}
		}
        $this->pagedata['allsize'] = $def_image_set;
		$this->pagedata['minsize'] = $minsize_set;
        $this->pagedata['image_set'] = $def_image_set;
        $this->pagedata['this_url'] = $this->url;
        $this->page('admin/manage/imageset.html');
    }
	function o2oset(){
		$obj=kernel::single('base_mdl_kvstore');
		$default_set=$obj->getList('*',array('key'=>'physical.o2oset.set','prefix'=>'system'));
		$kv_set=$default_set[0]['value'];
		if($_POST['setting']=='setting'){
			$data['id']=$default_set[0]['id'];
			$data['prefix']='system';
			$data['key']='physical.o2oset.set';
			$data['value']=$_POST['kv'];
			$data['dateline']=time();
			$obj->save($data);
			$default_set=$obj->getList('*',array('key'=>'physical.o2oset.set','prefix'=>'system'));
			$kv_set=$default_set[0]['value'];
		}
		if(!$kv_set){
			$data=array(
				'start_time'=>30,
				'end_time'=>30,
			);
			$title['start_time']='预约开始天数';
			$title['end_time']='可提前预约天数';
		}else{
			$title=$kv_set['title'];
			$data=$kv_set['value'];
		}
		$this->pagedata['title']=$title;
		$this->pagedata['set']=$data;
		$this->pagedata['this_url'] = $this->url;
		$this->page('admin/manage/o2oset.html');
	}
}//End Class

