<?php
class cardcoupons_finder_cards_balance {
	function __construct($app){
        $this->app = $app;
    }
	var $detail_balance = '结算明细';
	var $column_control = '操作';
	var $column_control_order = COLUMN_IN_HEAD;
	
	function column_control($row){
		$balance_obj=kernel::single('cardcoupons_mdl_cards_balance');
		$balance = $balance_obj->getList('*',array('id'=>$row['id']));
		$status = $balance[0]['status'];
		if($status=='0'){
			$href="index.php?app=cardcoupons&ctl=admin_excards_pass_balance&act=balance&id=".$row['id'];
			$return ='<a href='.$href.'>'.app::get('b2c')->_('结算').'</a>';
			return $return;
		}else{
			return '';
		}
	}
	
	function detail_balance($id){
		$render = $this->app->render();
		$balance_obj=kernel::single('cardcoupons_mdl_cards_balance');
		$items=kernel::single('cardcoupons_mdl_cards_balance_items');
		$balance = $balance_obj->getList('*',array('id'=>$id));
		$settlement_no = $balance[0]['settlement_no'];
		$balance_info = $items->getList('*',array('settlement_no'=>$settlement_no));
		$render->pagedata['balance_info'] = $balance_info;
		return $render->fetch("admin/cards/balance_items.html");
    }
	
}
?>