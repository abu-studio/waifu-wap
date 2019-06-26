<?php
/**
*卡券默认设置类
*
**/ 
class cardcoupons_ctl_admin_manage extends desktop_controller{
	
	function type_cat(){
		
	}
	
	//基本设置，目前设置店铺
	function default_style(){
		$kv_obj=kernel::single('base_mdl_kvstore');
		if($_POST['store_set']=='store_set'){
			$_POST['card']['value']=$_POST['card']['value'];
            if($_POST['card']['value']['message']){
                $_POST['card']['value']['message'] = htmlspecialchars($_POST['card']['value']['message']);
            }
			$_POST['card']['key']='cardcoupons.card.set';
			$_POST['card']['prefix']='system';
			$_POST['card']['dateline']=time();
			$kv_obj->save($_POST['card']);
		}
		$card=$kv_obj->getList('*',array('key'=>'cardcoupons.card.set'));
		$card_data=$card[0]['value'];
		$card_data['id']=$card[0]['id'];
        if($card_data['message']){
            $card_data['message'] = htmlspecialchars_decode($card_data['message']);
        }
		$this->pagedata['card']=$card_data;
		$this->page('admin/manage/default_style.html');
	}
	//店铺设置
	function store_set(){
		$kv_obj=kernel::single('base_mdl_kvstore');
		if($_POST['store_set']=='store_set'){
			$_POST['store']['key']='cardcoupons.store.set';
			$_POST['store']['prefix']='system';
			$_POST['store']['dateline']=time();
			$kv_obj->save($_POST['store']);
		}
		$store=$kv_obj->getList('*',array('key'=>'cardcoupons.store.set'));
		$this->pagedata['store']=$store[0];
		$this->page('admin/manage/store_set.html');
	}
	
	
}
