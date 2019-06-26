<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class cardcoupons_ctl_admin_excards_pass_balance extends desktop_controller
{

    var $pagelimit = 10;
	var $workground = 'cardcoupons.wrokground.card';
    function index()
    {
		$this->finder('cardcoupons_mdl_cards_balance', 
		   array('title' => app::get('b2c')->_('外部卡结算报表'),
		   'use_buildin_export'=>true,
		   'use_view_tab'=>true,
		   'force_view_tab'=>true,
		   
		));
    }
	
	function balance()
    {
		$db = kernel::database();
        $transaction_status = $db->beginTransaction();
		$this->begin('index.php?app=cardcoupons&ctl=admin_excards_pass_balance&act=index');
		$balance_obj=kernel::single('cardcoupons_mdl_cards_balance');
		$items=kernel::single('cardcoupons_mdl_cards_balance_items');
		$pass_obj = kernel::single('cardcoupons_mdl_cards_pass');
		$balance = $balance_obj->getList('*',array('id'=>$_GET['id']));
		$card_id =$balance[0]['card_id'];
		$settlement_no = $balance[0]['settlement_no'];
		$balance_info = $items->getList('*',array('settlement_no'=>$settlement_no));
		if($balance_obj->update(array('status'=>'1'),array('id'=>$_GET['id']))){
			foreach($balance_info as $key=>$value){
				$res=$pass_obj->update(array('status'=>'4'),array('card_no'=>$value['card_no'],'card_pass'=>$value['card_pass'],'card_id'=>$card_id));
				if(!$res){
					$db->rollback();
					$this->end(false,'结算失败请重新结算！');
				}
			}
			$this->end(true,'结算成功！');
		}else{
			$db->rollback();
			$this->end(false,'结算失败请重新结算！');
		}
    }
	
}