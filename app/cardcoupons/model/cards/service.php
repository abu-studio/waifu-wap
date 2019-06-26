<?php
class cardcoupons_mdl_cards_service extends dbeav_model{
	var $has_tag = true;
	function pre_recycle($rows){
		$services_id=array();
		foreach($rows as $key=>$value){
			$services_id[]=$value['card_service_id'];
		}
		$services=kernel::single('cardcoupons_mdl_cards')->getList('service_id',array('service_id'=>$services_id));
		if($services){
			$this->recycle_msg = app::get('cardcoupons')->_('该名目已被卡券关联');
            return false;
		}
		return true;
	}
}