<?php
class cardcoupons_mdl_cards_type extends dbeav_model{
	
	function pre_recycle($rows){
		$card_type_obj = kernel::single('cardcoupons_mdl_cards_type');
		$cardkind_arr=array();
		foreach($rows as $key=>$value){
			$card_type_info = $card_type_obj->getRow('handle',array('card_type_id'=>$value['card_type_id']));
			$cardkind_arr[]=$card_type_info['handle'];
		}

		$services=kernel::single('cardcoupons_mdl_cards_service')->getList('card_service_id',array('cardkind'=>$cardkind_arr));
		if($services){
			$this->recycle_msg = app::get('cardcoupons')->_('该类型已被卡券名目');
            return false;
		}
		return true;
	}
}