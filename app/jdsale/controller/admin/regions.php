<?php


class jdsale_ctl_admin_regions extends desktop_controller{

    var $workground = 'jdsale.workground.order';

    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){
        $this->pagedata['isEnable'] = true;

		$is_initial = app::get('jdsale')->getConf('areadata.isinitial');
        if ($is_initial == 2){
            $this->pagedata['isEnable']=false;
        }

        $this->showarea();
    }

    public function import(){
        app::get('jdsale')->setConf('areadata.isinitial','0');
        $is_initial = app::get('jdsale')->getConf('areadata.isinitial');
        if ($is_initial != 2){
            set_time_limit(0);
            $jdsale_regions_import = kernel::single('jdsale_regions_import');

            if($jdsale_regions_import->initialData()){
                app::get('jdsale')->setConf('areadata.isinitial',2);
                $this->splash('success','','京东地址库数据导入成功！');
            }else{
                $this->splash('success','','京东地址库数据导入出错！' );
            }
        }else{
            $this->splash('success','','京东地址库数据已经导入！');
        }

        $this->index();
    }
    /**
     * 展示所有地区
     * @params null
     * @return null
     */
    private function showarea()
    {
        $obj_regions_op =kernel::single('jdsale_regions_operation');
        $this->path[]=array('text'=>app::get('jdsale')->_('配送地区列表'));

        if ($obj_regions_op->getTreeSize())
        {
            //超过100条
            $this->pagedata['area'] = $obj_regions_op->getRegionById();
            $this->page('admin/delivery/area_treeList.html');
        }
        else
        {
            $obj_regions_op->getMap();
            $this->pagedata['area'] = $obj_regions_op->regions;
            $this->page('admin/delivery/area_map.html');
        }
    }

    public function showRegionTreeList($serid,$multi=false)
    {
         if ($serid)
         {
            $this->pagedata['sid'] = $serid;
         }
         else
         {
            $this->pagedata['sid'] = substr(time(),6,4);
         }

         $this->pagedata['multi'] =  $multi;
         $this->singlepage('delivery/regionSelect.html');
    }

    public function getChildNode()
    {
        //$dArea = $this->app->model('regions');
        //$obj_regions_op = kernel::service('ectools_regions_apps', array('content_path'=>'ectools_regions_operation'));
        $obj_regions_op =kernel::single('jdsale_regions_operation');
        $this->pagedata['area'] = $obj_regions_op->getRegionById($_POST['regionId']);
        $this->display('admin/delivery/area_sub_treeList.html');
    }

    public function getRegionById($pregionid)
    {
        //$oDlyType = &$this->app->model('regions');
       // $obj_regions_op = kernel::service('ectools_regions_apps', array('content_path'=>'ectools_regions_operation'));
        $obj_regions_op =kernel::single('jdsale_regions_operation');
        echo json_encode($obj_regions_op->getRegionById($pregionid));
    }


    /*
     * 调用 jdsale_regions_select
     */
    public function selRegion()
    {
        $path = $_GET['path'];
        $depth = $_GET['depth'];

        header('Content-type: text/html;charset=utf8');
        //$local = $this->app->model('regions');
        $local = kernel::single('jdsale_regions_select');
        $ret = $local->get_area_select($this->app,$path,array('depth'=>$depth));

        if($ret)
        {
            echo '&nbsp;-&nbsp;'.$ret;
        }
        else
        {
            echo '';
        }
    }



	public function migration(){	

		echo 'start';	
		kernel::single('jdsale_regions_import')->migration();		
		echo 'end';
	}
	
}
