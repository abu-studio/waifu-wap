<?php
 class cardcoupons_finder_cards_pass_create {
	function __construct($app){
        $this->app = $app;
    }

	var $detail_passlist = '关联卡密';
	var $detail_passlist_order = COLUMN_IN_HEAD;
	function detail_passlist($id){
		$render = $this->app->render();

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

		$pass_create_obj=kernel::single('cardcoupons_mdl_cards_pass_create');
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');

		$pass_create_info = $pass_create_obj->dump(array('id'=>$id),'batch');

		$pass_list = $pass_obj->getList("*",array('batch'=>$pass_create_info['batch']));

        $render->pagedata['pass_list']=$pass_list;
        return $render->fetch('admin/cards/pass/create/pass_list.html');
    }

	var $column_control='操作';
	var $column_control_order = COLUMN_IN_HEAD;
	function column_control ($row){
		$return='';
		if($row['ex_status'] == 'update'){
			$return.='<a href="index.php?app=cardcoupons&ctl='.$_GET['ctl'].'&act=shenhe&p[0]='.$row['id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'].'" target="dialog::{title:\''.app::get('cardcoupons')->_('卡密创建审核').'\', width:500,height:300}">'.app::get('cardcoupons')->_('审核').'</a>';
		}
		return $return;
	}
 }
?>
