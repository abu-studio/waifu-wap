<?php
 class cardcoupons_finder_cards_pass_update {
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

		$pass_update_obj=kernel::single('cardcoupons_mdl_cards_pass_update');
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');

		$pass_update_info = $pass_update_obj->dump(array('id'=>$id),'card_pass_ids');

		$card_pass_ids_arr = explode(",",$pass_update_info['card_pass_ids']);

		$pass_list = $pass_obj->getList("*",array('card_pass_id'=>$card_pass_ids_arr));

        $render->pagedata['pass_list']=$pass_list;
        return $render->fetch('admin/cards/pass/update/pass_list.html');
    }


	var $column_control='操作';
	var $column_control_order = COLUMN_IN_HEAD;
	function column_control ($row){
		$return='';
		if($row['ex_status'] == 'update'){
			$type_arr=array(
				'1'=>'有效期申请',
				'2'=>'有效性申请',
				'3'=>'卡冻结申请',
			);
			$return.='<a href="index.php?app=cardcoupons&ctl='.$_GET['ctl'].'&act=shenhe&p[0]='.$row['id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'].'" target="dialog::{title:\''.$type_arr[$row['type']].'审核\', width:500,height:300}">'.app::get('cardcoupons')->_('审核').'</a>';
		}
		return $return;
	}
 }
?>
