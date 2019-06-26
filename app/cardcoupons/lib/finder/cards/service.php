<?php
class cardcoupons_finder_cards_service {
	//var $detail_basic = '基本信息';
    var $column_control = '操作';
	function column_control($row){
		$return = '<a href="index.php?app=cardcoupons&ctl=admin_cards_service&act=edit&p[0]='.$row['card_service_id'].'&finder_id='.$_GET['_finder']['finder_id'].'" target="dialog::{title:\''.app::get('cardcoupons')->_('编辑名目').'\', width:500, height:300}">'.app::get('b2c')->_('编辑').'</a>';
		
		return $return;
	}
	
	
}