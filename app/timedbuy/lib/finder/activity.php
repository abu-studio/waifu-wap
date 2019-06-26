<?php

class timedbuy_finder_activity{

    function __construct($app){
        $this->app = $app;
        $this->router = app::get('desktop')->router();
        $this->activity = $this->app->model('activity');
    }//End
    
    public $column_edit = '操作';
    var $detail_basic = '查看';
    public $column_edit_width = 110;

    public function column_edit($row){
        $row = $this->activity->getList('*',array('act_id'=>$row['act_id']));
        $row = $row[0];
		$html = '';
		if($row['disabled']=='false'){
			//if($row['act_open']=='false'&&$row['active_status']=='start'){
				$html .= '<a href="'. $this->router->gen_url( array('app'=>'timedbuy','ctl'=>'admin_activity','act'=>'edit','act_id'=>$row['act_id']) ) .'" >'.app::get('timedbuy')->_('编辑').'</a>&nbsp;&nbsp;';
			//}
			if($row['act_open']=='false'){
				$html .= '<a href="'. $this->router->gen_url( array('app'=>'timedbuy','ctl'=>'admin_activity','act'=>'openActivity','act_id'=>$row['act_id']) ) .'" >'.app::get('timedbuy')->_('开启').'</a>&nbsp;&nbsp;';
			}else{
				$html .= '<a href="'. $this->router->gen_url( array('app'=>'timedbuy','ctl'=>'admin_activity','act'=>'closeActivity','act_id'=>$row['act_id']) ) .'" >'.app::get('timedbuy')->_('关闭').'</a>&nbsp;&nbsp;';
			}
		}
        return $html;
    }

    function detail_basic($id){
        
        $render = $this->app->render();
        $issu_type = array('卖场型旗舰店','专卖店','专营店','品牌旗舰店');
        $act_open = array('true'=>'开启','false'=>'关闭');
        $activity = $this->activity->dump(array('act_id'=>$id),'*');
        $activity['start_time'] = date('Y-m-d H:i:s',$activity['start_time']);
        $activity['end_time'] = date('Y-m-d H:i:s',$activity['end_time']);
        $business_type = $activity['business_type'];
        $businee_type = array_filter(explode(',',$business_type));
        $catObj = app::get('b2c')->model('goods_cat');
        $cats = $catObj->getList('cat_name',array('cat_id'=>$businee_type));
        $cat_names = array();
        foreach($cats as $k=>$cat){
            $cat_names[] = $cat['cat_name'];
        }
        $activity['business_type'] = implode(',',$cat_names);
        $activity['store_type'] = explode(',',$activity['store_type']);
        foreach($activity['store_type'] as $k=>$v){
            $activity['store_type'][$k] = $issu_type[$v];
        }
        $activity['store_type'] = implode(',',$activity['store_type']);
        $activity['act_open'] = $act_open[$activity['act_open']];

        $businessObj = app::get('business')->model('storegrade');
        $grade_ids = explode(',',$activity['store_lv']);
        $grades = $businessObj->getList('grade_name',array('grade_id'=>$grade_ids));
        $grade_names = array();
        foreach($grades as $k=>$grade){
            $grade_names[] = $grade['grade_name'];
        }
        $activity['store_lv'] = implode(',',$grade_names);
        $render->pagedata['activity'] = $activity;
        return $render->fetch('finder/activity_detail.html');
    }

}