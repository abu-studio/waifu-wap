<?php
class physical_finder_store{  
	var $detail_basic = '基本信息';
	var $detail_basic_order = COLUMN_IN_HEAD;
	var $detail_attach = '关联套餐';
	var $detail_attach_order = COLUMN_IN_HEAD;
	var $detail_time = '预约时间';
	var $detail_time_order = COLUMN_IN_HEAD;
	var $column_control = '操作';
    var $column_control_width = 100;
	var $column_control_order = COLUMN_IN_HEAD;

	function __construct($app){
        $this->app = $app;
    }

	function detail_basic($store_id){
		$render = $this->app->render();
        $store_info = app::get('physical') -> model('store') -> getInfobyid($store_id);

		$type=array("1"=>"体检");
		$store_info["type_name"] = $type[$store_info["type"]];

		$week=array(
			"1"=>"周一",
			"2"=>"周二",
			"3"=>"周三",
			"4"=>"周四",
			"5"=>"周五",
			"6"=>"周六",
			"0"=>"周日",
		);
		$week_arr=array("1","2","3","4","5","6","0");
		$weekday_html="";
		$restday_html="";
		if($store_info['weekday']){
			$weekday_arr = explode(",",$store_info['weekday']);
			$restday_arr = array_diff($week_arr,$weekday_arr);
			foreach($weekday_arr as $val){
				$weekday_html .= ",".$week[$val];
			}
			foreach($restday_arr as $val){
				$restday_html .= ",".$week[$val];
			}
		}
		$store_info["weekday_html"] = trim($weekday_html,",");
		$store_info["restday_html"] = trim($restday_html,",");

        $render->pagedata['store_info']=$store_info;
        return $render->fetch('admin/store/detail/basic.html');
    }

	function detail_attach($store_id){
		$render = $this->app->render();
        $attach_list = app::get('physical') -> model('store') -> get_attach_list($store_id);
		$render->pagedata['attach_list']=$attach_list;
        return $render->fetch('admin/store/detail/attach_list.html');
    }
	function detail_time($row){
		$render = $this->app->render();
		$render->pagedata['store_id']=$row['store_id'];
		return $render->fetch('admin/store/detail/time.html');
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
                    'href'=>'index.php?app=physical&ctl=admin_store&act=edit&&p[0]='.$row['store_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'],
                    'label'=>app::get('physical')->_('编辑店铺'),
                    'target'=>'dialog::{title:\''.app::get('physical')->_('编辑门店').'\', width:500, height:500}',
                ),
				'package_attach'=>array(
                    'href'=>'index.php?app=physical&ctl=admin_store&act=package_attach&&p[0]='.$row['store_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'],
                    'label'=>app::get('physical')->_('套餐关联'),
                    'target'=>'dialog::{title:\''.app::get('physical')->_('套餐关联').'\', width:500, height:500}',
                ),
            ),


        );

       
        $render->pagedata['arr_link'] = $arr_link;
        $render->pagedata['handle_title'] = app::get('business')->_('编辑');
        $render->pagedata['is_active'] = 'true';
        return $render->fetch('admin/actions.html');
    }
}
