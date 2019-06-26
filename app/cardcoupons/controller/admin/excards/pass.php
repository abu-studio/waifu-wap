<?php
 

class cardcoupons_ctl_admin_excards_pass extends desktop_controller {

    var $workground = 'cardcoupons.wrokground.card';


    function index(){
        $actions_base['title'] = app::get('cardcoupons')->_('卡密中心');
		/*
		if($this->has_permission('updatepass')&&$_GET['view']!='1'){
			$custom_actions[] = array(
				'label'=>app::get('cardcoupons')->_('批量修改'),
				'icon'=>'update.gif',
				//'disabled'=>'false',
				'submit'=>'index.php?app=cardcoupons&ctl=admin_excards_pass&act=update',
				'target'=>'dialog'
			);
		}
		*/
		if($this->has_permission('exportcard')){
            $actions_base['use_buildin_export'] = true;
        }
		$actions_base['actions'] = $custom_actions;
		$actions_base['use_buildin_filter'] = true;
		$actions_base['use_buildin_tagedit'] = true;
		$actions_base['use_view_tab'] = true;
		$actions_base['use_buildin_recycle'] = false;
		$actions_base['base_filter'] =array('source'=>'external','ex_status'=>'true');
        $this->finder('cardcoupons_mdl_cards_pass',$actions_base);
    }
	function _views(){
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');

		$sub_menu = array(
            0 =>array('label'=>app::get('cardcoupons')->_('全部'),'optional'=>true,
			'filter'=>array('source'=>'external','ex_status'=>'true')),
			1 =>array('label'=>app::get('cardcoupons')->_('未发放'),'optional'=>true,
			'filter'=>array('source'=>'external','ex_status'=>'true','status'=>'0')),
			2 =>array('label'=>app::get('cardcoupons')->_('已预售'),'optional'=>true,
			'filter'=>array('source'=>'external','ex_status'=>'true','status'=>'-1')),
			3 =>array('label'=>app::get('cardcoupons')->_('已发放'),'optional'=>true,
			'filter'=>array('source'=>'external','ex_status'=>'true','status'=>'1')),
			4 =>array('label'=>app::get('cardcoupons')->_('已激活'),'optional'=>true,
			'filter'=>array('source'=>'external','ex_status'=>'true','status'=>'2')),
			5 =>array('label'=>app::get('cardcoupons')->_('已使用'),'optional'=>true,
			'filter'=>array('source'=>'external','ex_status'=>'true','status'=>'3')),
			6 =>array('label'=>app::get('cardcoupons')->_('已结算'),'optional'=>true,
			'filter'=>array('source'=>'external','ex_status'=>'true','status'=>'4')),
			7 =>array('label'=>app::get('cardcoupons')->_('已冻结'),'optional'=>true,
			'filter'=>array('source'=>'external','ex_status'=>'true','status'=>'5')),
        );

		foreach($sub_menu as $k=>$v){
			$show_menu[$k] = $v;
			$show_menu[$k]['addon'] = $pass_obj->count($v['filter']);
        }
        return $show_menu;
	}
	function update(){
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
		if($_POST['updatepass']=='updatepass'){
			
			if($_POST['card_id'])$pass['card_id']=$_POST['card_id'];
			if($_POST['from_time'])$pass['from_time']=$_POST['from_time'];
			if($_POST['to_time'])$pass['to_time']=$_POST['to_time'];
			if($_POST['type'])$pass['type']=$_POST['type'];
			$filter=json_decode($_POST['filter'],1);
			
			$this->begin('index.php?app=cardcoupons&ctl=admin_excards_pass&act=index');
			$pass_info=$pass_obj->getList('disabled,card_pass_id',$filter);
			foreach($pass_info as $key=>$value){
				$pass['disabled']['old']=$value['disabled'];
				//如果是审核失败的卡密，再次修改时原有的状态取额外参数里旧的卡密状态
				if($value['disabled']=='fail'){
					$pass['disabled']['old']=$value['params']['disabled']['old'];
				}
				if(isset($_POST['disabled']) &&$value['disabled']!=$_POST['disabled'])$pass['disabled']['new']=$_POST['disabled'];
			
				if($pass){
					$data['params']=$pass;
					$data['disabled']='update';
					$data['memo']='申请修改：'.$_POST['memo'].';';
					$data['lasttime']=time();
					$result=$pass_obj->update($data,array('card_pass_id'=>$value['card_pass_id']));
				}else{
					$result=true;
				}
			}
			if($result){
				if($obj_operatorlogs = kernel::service('operatorlog')){
					if(method_exists($obj_operatorlogs,'inlogs')) {
						$pass_id=implode(',',$filter['card_pass_id']);
						$memo = '修改卡密信息:ID('.$pass_id.')操作员备注('.$_POST['memo'].')';
						$obj_operatorlogs->inlogs($memo, '卡密', 'cardcoupons');
					}
				}
				$this->end(true,'操作成功');
			}else{
				$this->end(false,'操作失败');
			}
			
			
		}else{
			$filter=array('status'=>'0');
			if($_GET['p']){
				$filter['card_pass_id']=$_GET['p'];
			}else{
				$filter['card_pass_id']=$_POST['card_pass_id'];
				if($_POST['isSelectedAll']=='_ALL_'){
					$filter=array('status'=>'0');
				}
			}
			$count=$pass_obj->count($filter);
			$this->pagedata['filter']=json_encode($filter);
			$this->pagedata['count']=$count;
			$this->pagedata['source']=array('source'=>'external');
			$this->display('admin/excards/pass/update.html');
		}
	}
}
