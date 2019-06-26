<?php
class physical_finder_orders{  
	var $detail_basic = '基本信息';
	var $detail_basic_order = COLUMN_IN_HEAD;
	var $detail_pass = '卡密信息';
	var $detail_pass_order = COLUMN_IN_HEAD;
	var $detail_log = '操作日志';
	var $detail_log_order = COLUMN_IN_HEAD;

	var $pagelimit = 10;

	function __construct($app){
        $this->app = $app;
		$this->controller = app::get('physical')->controller('admin_orders');
		
		$this->_type=array("1"=>"体检");
		$this->_c_type=array(
			1 => app::get('physical')->_('身份证'),
			2 => app::get('physical')->_('军官证'),
			3 => app::get('physical')->_('团员证'),
		);
		$this->_status = array (
			1 => app::get('physical')->_('待付款'),
			2 => app::get('physical')->_('待处理'),
			3 => app::get('physical')->_('预约成功待检'),
			4 => app::get('physical')->_('预约成功逾期'),
			5 => app::get('physical')->_('预约失败'),
			6 => app::get('physical')->_('体检完成'),
			7 => app::get('physical')->_('已关闭'),
		);
    }

	function detail_basic($id){
		$render = $this->app->render();
        $order_info = app::get('physical') -> model('orders') -> getInfobyid($id);

		$order_info["type_name"] = $this->_type[$order_info["type"]];
		$order_info["c_type_name"] = $this->_c_type[$order_info["c_type"]];
		$order_info['status_name'] = $this->_status[$order_info['status']];

		$order_time_arr = explode(",",$order_info['order_times']);
		$order_info['order_time_arr'] = $order_time_arr;

		
        $render->pagedata['order_info']=$order_info;
        return $render->fetch('admin/orders/detail/basic.html');
    }

	function detail_pass($id){
		$render = $this->app->render();
        $order_info = app::get('physical') -> model('orders') -> getInfobyid($id);

		$ex_status_arr=array(
			'update'=>'待审核',
			'false'=>'审核失败',
			'true'=>'审核成功',
		);
		$render->pagedata['ex_status_arr']=$ex_status_arr;

		$status_arr=array(
			'-1'=>'已预售',
			'0'=>'未发放',
			'1'=>'已发放',
            '2'=>'已激活',
            '3'=>'已使用',
            '4'=>'已结算',
			'5'=>'已冻结',
		);
		$render->pagedata['status_arr']=$status_arr;

		$type_arr=array(
			'entity'=>'实体卡',
			'virtual'=>'电子码',
		);
		$render->pagedata['type_arr']=$type_arr;

		$disabled_arr=array(
			'false'=>'未失效',
			'true'=>'已失效',
		);
		$render->pagedata['disabled_arr']=$disabled_arr;
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');

		$pass_info = $pass_obj->getRow("*",array('card_pass_id'=>$order_info['card_pass_id']));
		$render->pagedata['pass_info']=$pass_info;

        return $render->fetch('admin/orders/detail/pass.html');
    }

	function detail_log($id){
		if(!$id) return null;
        $nPage = $_GET['detail_log'] ? $_GET['detail_log'] : 1;
		$obj_log = $this->app -> model('order_log');

		$filter = array('order_id'=>$id);
        $count = $obj_log->count($filter);
        $log_list = $obj_log->getList('*', $filter, $this->pagelimit*($nPage-1), $this->pagelimit, 'alttime desc');

		$render = $this->app->render();
        $render->pagedata['log_list'] = $log_list;

		if($_GET['page']) unset($_GET['page']);
        $_GET['page'] = 'detail_log';
        $this->controller->pagination($nPage,$count,$_GET);

        return $render->fetch('admin/orders/detail/log_list.html');
    }

	var $column_control = '操作';
	var $column_control_order = COLUMN_IN_HEAD;
    function column_control($row){
		$arr_link =array();
		switch($row['status']){
			case 1:
				break;
			case 2:
				$arr_link = array(
					'info'=>array(
						1 =>array(
							'label'=>app::get('physical')->_('预约成功'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=update_status_show&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'&p[1]=3',
							'target'=>'dialog::{title:\''.app::get('physical')->_('预约成功').'\', width:500, height:300}',
						),
						2 =>array(
							'label'=>app::get('physical')->_('预约失败'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=update_status_show&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'&p[1]=5',
							'target'=>'dialog::{title:\''.app::get('physical')->_('预约失败').'\', width:500, height:200}',
						),
					),
				);
				break;
			case 3:
				$arr_link = array(
					'info'=>array(
						1 =>array(
							'label'=>app::get('physical')->_('体检完成'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=update_status_show&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'&p[1]=6',
							'target'=>'dialog::{title:\''.app::get('physical')->_('体检完成').'\', width:500, height:300}',
						),
					),
				);
				break;
			case 4:
				$arr_link = array(
					'info'=>array(
						1 =>array(
							'label'=>app::get('physical')->_('编辑'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'],
							'target'=>'dialog::{title:\''.app::get('physical')->_('编辑预约单').'\', width:500, height:200}',
						),
						2 =>array(
							'label'=>app::get('physical')->_('体检完成'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=update_status_show&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'&p[1]=6',
							'target'=>'dialog::{title:\''.app::get('physical')->_('体检完成').'\', width:500, height:300}',
						),
						3 =>array(
							'label'=>app::get('physical')->_('重新处理'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=update_status_show&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'&p[1]=2',
							'target'=>'dialog::{title:\''.app::get('physical')->_('重新处理').'\', width:500, height:200}',
						),
						4 =>array(
							'label'=>app::get('physical')->_('关闭'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=update_status_show&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'&p[1]=7',
							'target'=>'dialog::{title:\''.app::get('physical')->_('关闭').'\', width:500, height:200}',
						),
					),
				);
				break;
			case 5:
				$arr_link = array(
					'info'=>array(
						1 =>array(
							'label'=>app::get('physical')->_('编辑'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'],
							'target'=>'dialog::{title:\''.app::get('physical')->_('编辑预约单').'\', width:500, height:200}',
						),
						2 =>array(
							'label'=>app::get('physical')->_('重新处理'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=update_status_show&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'&p[1]=2',
							'target'=>'dialog::{title:\''.app::get('physical')->_('重新处理').'\', width:500, height:200}',
						),
						3 =>array(
							'label'=>app::get('physical')->_('关闭'),
							'href'=>'index.php?app=physical&ctl=admin_orders&act=update_status_show&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['id'].'&p[1]=7',
							'target'=>'dialog::{title:\''.app::get('physical')->_('关闭').'\', width:500, height:200}',
						),
					),
				);
				break;
			case 6:
				break;
			case 7:
				break;
		}
		if($arr_link){
			$render = $this->app->render();
			$render->pagedata['arr_link'] = $arr_link;
			$render->pagedata['handle_title'] = app::get('business')->_('操作');
			$render->pagedata['is_active'] = 'true';
			return $render->fetch('admin/actions.html');
		}
    }
}
