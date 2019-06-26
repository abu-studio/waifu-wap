<?php
class cardcoupons_finder_cards_exchangelog {
	function __construct($app){
        $this->app = $app;
    }
	var $detail_all = '全部记录';
	var $detail_success = '成功记录';
	var $detail_fail= '失败记录';
	var $column_control = '操作';
	
	function column_control($row){
		$cards=kernel::single('cardcoupons_mdl_cards_exchangelog_items');
		$exchangelog=kernel::single('cardcoupons_mdl_cards_exchangelog');
		$data =$exchangelog->getList('*',array('id'=>$row['id']));
		$batch =$data[0]['batch'];
		$card_id = $data[0]['card_id'];
		if($data[0]['status']=='1'){
			$return ='已生成结算报表';
		}else{
			$res = $cards->getList('*',array('batch'=>$batch,'status'=>'0'));
			if(!$res){
			$href="index.php?app=cardcoupons&ctl=admin_excards_pass_exchangelog&act=create_balance&card_id=".$card_id. "&batch=".$batch;
			$return = '<a href='.$href.'>'.app::get('b2c')->_('生成结算报表').'</a> ';
			}else{
			$href="index.php?app=cardcoupons&ctl=admin_excards_pass_exchangelog&act=import&card_id=".$card_id. "&batch=".$batch;
			$return ='<a href='.$href.' target="dialog::{title:\'' . app::get('cardcoupons')->_('重新导入兑换记录') . '\',width:500,height:200}">'.app::get('b2c')->_('重新导入').'</a> ';
			}
		}
		return $return;
	}
	
	function detail_all($id){
		$render = $this->app->render();
		$exchange_obj=kernel::single('cardcoupons_mdl_cards_exchangelog');
		$items=kernel::single('cardcoupons_mdl_cards_exchangelog_items');
		$card=kernel::single('cardcoupons_mdl_cards');
		$exchang = $exchange_obj->getList('*',array('id'=>$id));
		$batch = $exchang[0]['batch'];
		$cards = $items->getList('*',array('batch'=>$batch));
		$card_name=$card->getList('name',array('card_id'=>$cards[0]['card_id']));
		$render->pagedata['card_name'] = $card_name[0]['name'];
		$render->pagedata['cards'] = $cards;
		return $render->fetch('admin/cards/exchange_items.html');
    }
	
	function detail_success($id){
		$render = $this->app->render();
		$exchange_obj=kernel::single('cardcoupons_mdl_cards_exchangelog');
		$items=kernel::single('cardcoupons_mdl_cards_exchangelog_items');
		$card=kernel::single('cardcoupons_mdl_cards');
		$exchang = $exchange_obj->getList('*',array('id'=>$id));
		$batch = $exchang[0]['batch'];
		$cards = $items->getList('*',array('batch'=>$batch,'status'=>'1'));
		$card_name=$card->getList('name',array('card_id'=>$cards[0]['card_id']));
		$render->pagedata['card_name'] = $card_name[0]['name'];
		$render->pagedata['cards'] = $cards;
		return $render->fetch("admin/cards/exchange_items.html");
    }
	
	function detail_fail($id){
		$render = $this->app->render();
		$exchange_obj=kernel::single('cardcoupons_mdl_cards_exchangelog');
		$items=kernel::single('cardcoupons_mdl_cards_exchangelog_items');
		$card=kernel::single('cardcoupons_mdl_cards');
		$exchang = $exchange_obj->getList('*',array('id'=>$id));
		$batch = $exchang[0]['batch'];
		$cards = $items->getList('*',array('batch'=>$batch,'status'=>'0'));
		$card_name=$card->getList('name',array('card_id'=>$cards[0]['card_id']));
		$render->pagedata['card_name'] = $card_name[0]['name'];
		$render->pagedata['cards'] = $cards;
		return $render->fetch("admin/cards/exchange_items.html");
    }
	
}
?>