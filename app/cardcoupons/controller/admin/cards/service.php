<?php
 

class cardcoupons_ctl_admin_cards_service extends desktop_controller{
    var $workground = 'cardcoupons.wrokground.card';
    private $cardtype=array(
        "01"=>'悠福礼品卡',
        "02"=>'悠福体检卡',
    );

    function index(){
        $actions_base['title'] = app::get('cardcoupons')->_('卡券名目');
        $actions_base['use_buildin_set_tag'] = true;
        $actions_base['use_buildin_filter'] = true;
        if($this->has_permission('exportcard')){
            $actions_base['use_buildin_export'] = true;
        }
        if($this->has_permission('importcard')){
            $actions_base['use_buildin_import'] = false;
        }
        if(!$this->has_permission('deletegoods')){
            $actions_base['use_buildin_recycle'] = false;
        }

        if($this->has_permission('add_cardservice')){
            $custom_actions[] = array(
                'label'=>app::get('b2c')->_('添加名目'),
                'icon'=>'add.gif',
                'href'=>'index.php?app=cardcoupons&ctl=admin_cards_service&act=add',
                'target'=>'dialog::{title:\''.app::get('b2c')->_('添加名目').'\',width:500,height:300}'
            );
        }
        $actions_base['actions'] = $custom_actions;
        $actions_base['use_view_tab'] = true;

        $actions_base['allow_detail_popup'] = true;
        $actions_base['use_view_tab'] = true;

        $this->finder('cardcoupons_mdl_cards_service',$actions_base);
    }


    //新增名目页面ctl
    function add(){
        $this->pagedata['title'] = app::get('b2c')->_('添加名目');
        header("Cache-Control:no-store");
        $this->pagedata['cardtype'] = $this->cardtype;
        $this->display('admin/cards/service/add.html');
    }


    function edit($card_service_id){
        if(!$card_service_id){
            return false;
        }
        $this->pagedata['card_service_id'] = $card_service_id;
        $card_type_object = $this->app->model('cards_service');
        $card_type_array = $card_type_object->getList("*",array("card_service_id"=>$card_service_id));
        $this->pagedata['cardtype_data'] = $card_type_array[0];
        $this->pagedata['title'] = app::get('b2c')->_('编辑名目');
        header("Cache-Control:no-store");
        $this->pagedata['cardtype'] = $this->cardtype;
        $this->pagedata['cardtype_findid'] = $_GET['finder_id'];
        $this->display('admin/cards/service/add.html');
    }

    function toAdd(){
        $this->begin();
		$msg='';
		if(!$this->cheack_data($_POST['cardservice'],$msg))$this->end(false,$msg);
        $card_service = $this->app->model('cards_service');
        $card_service_data = $_POST['cardservice'];
        $card_service_data['lasttime'] = time();
        $card_service_data['createtime'] = time();
        if($card_service->insert($card_service_data)){
            $this->end(true,"保存成功");
        }else{
            $this->end(true,"保存失败");
        }

    }

    function toEdit(){
        $this->begin();
		$msg='';
		if(!$this->cheack_data($_POST['cardservice'],$msg))$this->end(false,$msg);
        $carttype = $this->app->model('cards_service');
        $carttype_data = $_POST['cardservice'];
        $carttype_data['lasttime'] = time();
        if($carttype->update($carttype_data,array("card_service_id"=>$carttype_data['card_service_id']))){
            $this->end(true,"保存成功",'index.php?app=cardcoupons&ctl=admin_cards_service&act=index');
        }else{
            $this->end(false,"保存失败");
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
        //Array ( [card_service_id] => 14 [cardkind] => 01 [name] => 中秋 [handle] => 01 [memo] => )
        $card_service_object = kernel::single("cardcoupons_mdl_cards_service");
        if($data['card_service_id']){
            $card_service_data = $card_service_object->getList("handle",array("card_service_id|noequal"=>$data['card_service_id'],'cardkind'=>$data['cardkind']));
            foreach($card_service_data as $key=>$val){
                if($val['handle'] == $data['handle']){
                    $msg='句柄值不能重复！';
                    $return=false;
                }
            }
        }else{
            $card_service_data = $card_service_object->getList("handle",array('cardkind'=>$data['cardkind']));
            foreach($card_service_data as $key=>$val){
                if($val['handle'] == $data['handle']){
                    $msg='句柄值不能重复！';
                    $return=false;
                }
            }
        }
        return $return;
	}

}
