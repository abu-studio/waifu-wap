<?php
class physical_ctl_admin_orders extends desktop_controller{

	var $workground = 'physical.workground.physical';

	var $pagelimit = 10;

	function __construct($app){
        parent::__construct($app);
		//状态数组
		$this->_status = array (
			1 => app::get('physical')->_('待付款'),
			2 => app::get('physical')->_('待处理'),
			3 => app::get('physical')->_('预约成功待检'),
			4 => app::get('physical')->_('预约成功逾期'),
			5 => app::get('physical')->_('预约失败'),
			6 => app::get('physical')->_('体检完成'),
			7 => app::get('physical')->_('已关闭'),
		);
		//操作名称数组
		$this->_act_name = array (
			2 => app::get('physical')->_('重新处理'),
			3 => app::get('physical')->_('预约成功'),
			5 => app::get('physical')->_('预约失败'),
			6 => app::get('physical')->_('体检完成'),
			7 => app::get('physical')->_('关闭'),
		);
    }

    function index(){
		$data['title'] = app::get('physical')->_('预约单列表');
		$data['actions'] = $custom_actions;
		$data['use_buildin_set_tag'] = true;
		$data['use_buildin_filter'] = true;
		$data['use_buildin_tagedit'] = true;
		$data['use_view_tab'] = true;
		$data['use_buildin_recycle'] = false;
        $this->finder('physical_mdl_orders', $data);
    }

	public function _views(){
		$obj_orders = &$this->app->model('orders');
		$sub_menu = array(
            0 =>array('label'=>app::get('physical')->_('全部'),'optional'=>true),
			1 =>array('label'=>app::get('physical')->_('待付款'),'optional'=>true,'filter'=>array('status'=>array('1'))),
			2 =>array('label'=>app::get('physical')->_('待处理'),'optional'=>true,'filter'=>array('status'=>array('2'))),
			3 =>array('label'=>app::get('physical')->_('预约成功待检'),'optional'=>true,'filter'=>array('status'=>array('3'))),
			4 =>array('label'=>app::get('physical')->_('预约成功逾期'),'optional'=>true,'filter'=>array('status'=>array('4'))),
			5 =>array('label'=>app::get('physical')->_('预约失败'),'optional'=>true,'filter'=>array('status'=>array('5'))),
			6 =>array('label'=>app::get('physical')->_('体检完成'),'optional'=>true,'filter'=>array('status'=>array('6'))),
			7 =>array('label'=>app::get('physical')->_('已关闭'),'optional'=>true,'filter'=>array('status'=>array('7'))),
        );
		foreach($sub_menu as $k=>$v){
			$show_menu[$k] = $v;
			$show_menu[$k]['addon'] = $obj_orders->count($v['filter']);
			$show_menu[$k]['href'] = 'index.php?app=physical&ctl=admin_orders&act=index&view='.($k).(isset($_GET['optional_view'])?'&optional_view='.$_GET['optional_view'].'&view_from=dashboard':'');
        }
        return $show_menu;
	}

	function edit($id){
        $obj_orders = &$this->app->model('orders');
        $order_info = $obj_orders->dump($id);

		$order_time_arr = explode(",",$order_info['order_times']);
		$order_info['order_time_arr'] = $order_time_arr;

		$this->pagedata['order_info']=$order_info;

        $this->display('admin/orders/form.html');
    }

	function save(){
        $this->begin('index.php?app=physical&ctl=admin_orders&act=index');
        $obj_orders = &$this->app->model('orders');

		$data=array(
			'id' => intval($_POST['id']),
			'order_times' => $_POST['order_time_1'].','.$_POST['order_time_2'].','.$_POST['order_time_3'],
			'sure_time' => '',
			'update_time' => time(),
		);
		$rs = $obj_orders->save($data);
		if($rs){
			$this->end(true,app::get('physical')->_('机构保存成功'));
		}else{
			$this->end(true,app::get('physical')->_('机构保存失败'));
		}

        
    }

	function update_status_show($id,$status){
		$order_info = $this->app -> model('orders') -> dump($id);

		$order_time_arr = explode(",",$order_info['order_times']);
		$order_info['order_time_arr'] = $order_time_arr;

		$this->pagedata['order_info']=$order_info;
		$this->pagedata['status'] = $status;
		$this->pagedata['act_name'] = $this->_act_name[$status];

        $this->display('admin/orders/status/show'.$status.'.html');
    }

	function update_status(){
		$this->begin('index.php?app=physical&ctl=admin_orders&act=index&view=0');
		$status = $_POST['status'];

		$obj_orders = &$this->app->model('orders');
		$obj_order_log = &$this->app->model('order_log');

		//获取订单原来状态
		$order_info = $obj_orders->dump(array('id'=>intval($_POST['id']),'status'));

		if(!$_POST['id'] || !$order_info){
			$this->end(false,app::get('physical')->_("此预约单信息无法获取"));
		}

		$old_status = $order_info['status'];

		//操作状态范围（如：预约单只有 预约成功逾期/预约失败 状态 才能改为 待处理）
		$act_status_arr=array(
			2=>array(4,5),
			3=>array(2),
			5=>array(2),
			6=>array(3,4),
			7=>array(4,5),
		);
		//操作名称
		$act_name = $this->_act_name[$status];

		if( !in_array( $old_status , $act_status_arr[$status] ) ){
			$this->end(false,app::get('physical')->_("此状态预约单无法执行 {$act_name} 操作"));
		}

		$data = array(
			'id' => $_POST['id'],
			'status' => $status,
			'update_time' => time(),
		);

		
		switch($_POST['status']){
			case 1:
				break;
			case 2:
				$data['sure_time'] = '';
				$data['other_time'] = null;
				break;
			case 3:
				if(!$_POST['sure_time'] && !$_POST['other_time']){
					$this->end(false,app::get('physical')->_("请选择预约时间或预约其它时间"));
				}
				if($_POST['other_time']){
					$data['other_time'] = strtotime($_POST['other_time'].' '.$_POST['_DTIME_']['H']['other_time'].':'.$_POST['_DTIME_']['M']['other_time']);
				}else{
					$data['sure_time'] = $_POST['sure_time'];
				}
				break;
			case 4:
				break;
			case 5:
				break;
			case 6:
				$data['physical_time'] = strtotime($_POST['physical_time'].' '.$_POST['_DTIME_']['H']['physical_time'].':'.$_POST['_DTIME_']['M']['physical_time']);
				break;
			case 7:
				break;
		}

		$old_status_name = $this->_status[$old_status];
		$new_status_name = $this->_status[$_POST['status']];
		$log_data=array(
			'order_id' => $_POST['id'],
			'op_id' => $_SESSION['account']['user_data']['user_id'],
			'op_name' => $_SESSION['account']['user_data']['name'],
			'alttime' => time(),
			'record' => "操作：{$act_name}；状态：{$old_status_name}=>{$new_status_name}。",
			'remarks' => $_POST['remarks'],
		);

		$rs = $obj_orders->save($data);
		if($rs){
			$log_data['result'] = 1;
			//保存日志
			$obj_order_log->save($log_data);
			$this->end(true,app::get('physical')->_('{$act_name} 操作成功'));
		}else{
			$log_data['result'] = 2;
			//保存日志
			$obj_order_log->save($log_data);
			$this->end(false,app::get('physical')->_('{$act_name} 操作失败'));
		}
    }

	public function pagination($current,$count,$get){
        $ui = new base_component_ui(&$this->app);
        $link = 'index.php?app=physical&ctl=admin_orders&act=ajax_html&id='.$get['id'].'&finder_act='.$get['page'].'&'.$get['page'].'=%d';
        $this->pagedata['pager'] = $ui->pager(array(
            'current'=>$current,
            'total'=>ceil($count/$this->pagelimit),
            'link' =>$link,
            ));
    }
    
    public function ajax_html(){
        $finder_act = $_GET['finder_act'];
        $html = $this->$finder_act($_GET['id']);
        echo $html;
    }

	function detail_log($id){
		if(!$id) return null;
        $nPage = $_GET['detail_log'] ? $_GET['detail_log'] : 1;
		$obj_log = &$this->app -> model('order_log');

		$filter = array('order_id'=>$id);
        $count = $obj_log->count($filter);
        $log_list = $obj_log->getList('*', $filter, $this->pagelimit*($nPage-1), $this->pagelimit, 'alttime desc');

        $this->pagedata['log_list'] = $log_list;

		if($_GET['page']) unset($_GET['page']);
        $_GET['page'] = 'detail_log';
        $this->pagination($nPage,$count,$_GET);

        echo $this->fetch('admin/orders/detail/log_list.html');
    }
}