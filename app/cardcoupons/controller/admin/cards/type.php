<?php
 

class cardcoupons_ctl_admin_cards_type extends desktop_controller {

    var $workground = 'cardcoupons.wrokground.card';


    function index(){
        $actions_base['title'] = app::get('cardcoupons')->_('卡券类型');
        $actions_base['use_buildin_set_tag'] = true;
        $actions_base['use_buildin_filter'] = true;
        if($this->has_permission('exportcard')){
            $actions_base['use_buildin_export'] = true;
        }

		if($this->has_permission('add_cardtype')){
            $custom_actions[] = array(
                'label'=>app::get('b2c')->_('添加类型'),
                'icon'=>'add.gif',
                'href'=>'index.php?app=cardcoupons&ctl=admin_cards_type&act=add',
                'target'=>'dialog::{title:\''.app::get('b2c')->_('添加类型').'\',width:500,height:300}'
            );
        }
		$actions_base['actions'] = $custom_actions;

    
        $actions_base['use_view_tab'] = true;
        $this->finder('cardcoupons_mdl_cards_type',$actions_base);
    }

	function add(){
        $this->pagedata['title'] = app::get('b2c')->_('添加名目');
        header("Cache-Control:no-store");
        $this->pagedata['cardtype'] = $this->cardtype;
        $this->display('admin/cards/type/add.html');
    }

	function toAdd(){
        $this->begin();
		$msg='';
		if(!$this->cheack_data($_POST['cardtype'],$msg))$this->end(false,$msg);
        $card_type = $this->app->model('cards_type');
        $card_type_data = $_POST['cardtype'];
        $card_type_data['lasttime'] = time();
        $card_type_data['createtime'] = time();
        if($card_type->insert($card_type_data)){
            $this->end(true,"保存成功");
        }else{
            $this->end(true,"保存失败");
        }

    }

	function cheack_data($data,&$msg){
		$return=true;
		if(!$data['name']){
			$msg='名称不能为空';
			$return=false;
		}
		if(!is_numeric($data['handle']) ||strlen($data['handle'])!=2){
			$msg='句柄值只能是两位数字';
			$return=false;
		}
        //加入一个句柄值唯一性判断
        //Array ( [card_type_id] => 14 [cardkind] => 01 [name] => 中秋 [handle] => 01 [memo] => )
        $card_type_object = kernel::single("cardcoupons_mdl_cards_type");
        if($data['card_type_id']){
            $card_type_data = $card_type_object->getList("handle",array("card_type_id|noequal"=>$data['card_type_id'],'cardkind'=>$data['cardkind']));
            foreach($card_type_data as $key=>$val){
                if($val['handle'] == $data['handle']){
                    $msg='句柄值不能重复！';
                    $return=false;
                }
            }
        }else{
            $card_type_data = $card_type_object->getList("handle",array('cardkind'=>$data['cardkind']));
            foreach($card_type_data as $key=>$val){
                if($val['handle'] == $data['handle']){
                    $msg='句柄值不能重复！';
                    $return=false;
                }
            }
        }
        return $return;
	}
}
