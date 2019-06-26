<?php


class cardcoupons_ctl_admin_cards_pass_time extends desktop_controller {

    var $workground = 'cardcoupons.wrokground.card';


    function index(){
		$custom_actions[] = array('label'=>app::get('cardcoupons')->_('添加卡密有效期申请'),'href'=>'index.php?app=cardcoupons&ctl=admin_cards_pass_time&act=add','target'=>'dialog::{title:\''.app::get('cardcoupons')->_('添加卡密有效期申请').'\',width:500,height:400}');
        $actions_base['title'] = app::get('cardcoupons')->_('卡密有效期');
		$actions_base['actions'] = $custom_actions;
		$actions_base['use_buildin_filter'] = true;
		$actions_base['use_buildin_tagedit'] = true;
		$actions_base['use_view_tab'] = true;
		$actions_base['use_buildin_recycle'] = false;
		$actions_base['base_filter'] =array('source'=>'internal','type'=>'1');
        $this->finder('cardcoupons_mdl_cards_pass_update',$actions_base);
    }
	function _views(){
		$pass_update_obj=kernel::single('cardcoupons_mdl_cards_pass_update');

		$sub_menu = array(
            0 =>array('label'=>app::get('cardcoupons')->_('全部'),'optional'=>true,'filter'=>array('source'=>'internal','type'=>'1')),
			1 =>array('label'=>app::get('cardcoupons')->_('待审核'),'optional'=>true,'filter'=>array('source'=>'internal','type'=>'1','ex_status'=>'update')),
			2 =>array('label'=>app::get('cardcoupons')->_('审核成功'),'optional'=>true,'filter'=>array('source'=>'internal','type'=>'1','ex_status'=>'true')),
			3 =>array('label'=>app::get('cardcoupons')->_('审核失败'),'optional'=>true,'filter'=>array('source'=>'internal','type'=>'1','ex_status'=>'false')),
        );

		foreach($sub_menu as $k=>$v){
			$show_menu[$k] = $v;
			$show_menu[$k]['addon'] = $pass_update_obj->count($v['filter']);
        }
        return $show_menu;
	}

	function add(){
		$this->begin('index.php?app=cardcoupons&ctl=admin_cards_pass_time&act=index');
		if($_POST['add']=='add'){
			$db = kernel::database();
			$pass_update_obj=kernel::single('cardcoupons_mdl_cards_pass_update');
			$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
			if( !is_array($_POST['card_pass_ids']) || empty($_POST['card_pass_ids']) ){
				$this->end(false,'请选择卡密');
			}else{
				$num = count($_POST['card_pass_ids']);
				$card_pass_ids_str = implode(",",$_POST['card_pass_ids']);
			}
			$update_date=array(
				"source" => 'internal',
				"type" => '1',
				"num" => $num,
				"card_pass_ids" => $card_pass_ids_str,
				"from_time" => strtotime($_POST['from_time'].' '.$_POST['_DTIME_']['H']['from_time'].':'.$_POST['_DTIME_']['M']['from_time']),
				"to_time" => strtotime($_POST['to_time'].' '.$_POST['_DTIME_']['H']['to_time'].':'.$_POST['_DTIME_']['M']['to_time']),
				'create_id' => $_SESSION['account']['user_data']['user_id'],
				'create_name' => $_SESSION['account']['user_data']['name'],
				"create_time" => time(),
				"remarks1" => $_POST['remarks1'],
			);
			//事物处理开始
			$transaction_status = $db->beginTransaction();

			//新增卡密有效期申请
			$rs = $pass_update_obj->save($update_date);
			if($rs){
				//修改相关卡密为待审核状态
				$rs1 = $pass_obj->update(array('ex_status'=>'update'),array('card_pass_id'=>$_POST['card_pass_ids']));
				if($rs1){
					$db->commit($transaction_status);
					$this->end(true,'操作成功');
				}else{
					$db->rollback();
					$this->end(false,'操作失败2');
				}
			}else{
				$db->rollback();
				$this->end(false,'操作失败1');
			}
		}else{
			$this->display('admin/cards/pass/update/add/time.html');
		}
	}

	function shenhe($id){
		$this->begin('index.php?app=cardcoupons&ctl=admin_cards_pass_time&act=index');
		if($_POST['shenhe']=='shenhe'){
			$db = kernel::database();
			$pass_update_obj=kernel::single('cardcoupons_mdl_cards_pass_update');
			$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
			$data=array(
				'ex_status' => $_POST['ex_status'],
				'op_id' => $_SESSION['account']['user_data']['user_id'],
				'op_name' => $_SESSION['account']['user_data']['name'],
				"update_time" => time(),
				'remarks2' => $_POST['remarks2'],
			);
			//事物处理开始
			$transaction_status = $db->beginTransaction();
			
			//修改卡密有效期申请信息
			$rs = $pass_update_obj->update($data,array('id'=>$_POST['id']));
			if($rs){
				//查询申请信息中 关联卡密ID 和 有效期修改信息（开始时间 和 结束时间）
				$pass_update_info = $pass_update_obj->dump(array('id'=>$_POST['id']),'card_pass_ids,from_time,to_time');
				if($pass_update_info && $pass_update_info['card_pass_ids']){
					$card_pass_ids_arr = explode(",",$pass_update_info['card_pass_ids']);
					if($_POST['ex_status'] == "true"){
						//申请信息审核通过，关联卡密审核状态改为已审核并修改 开始时间 和 结束时间
						$pass_date=array(
							'ex_status'=>"true",
							'from_time'=>$pass_update_info['from_time'],
							'to_time'=>$pass_update_info['to_time'],
						);
					}else{
						//申请信息审核不通过，关联卡密审核状态改为已审核
						$pass_date=array('ex_status'=>"true");
					}
					//修改关联卡密
					$rs1 = $pass_obj->update($pass_date,array('card_pass_id'=>$card_pass_ids_arr));
					if($rs1){
						$db->commit($transaction_status);
						$this->end(true,'操作成功');
					}else{
						$db->rollback();
						$this->end(false,'操作失败3');
					}
				}else{
					$db->rollback();
					$this->end(false,'操作失败2');
				}
			}else{
				$db->rollback();
				$this->end(false,'操作失败1');
			}
		}else{
			$this->pagedata['id']=$id;
			$this->display('admin/cards/pass/update/shenhe/time.html');
		}
	}

	//搜索卡密
	function search_pass(){
		$type = $_POST['type'];//申请修改类型
		$filter = array();//搜索条件
		if($type == 1){
			//有效期申请
			$filter=array(
				'source'=>$_POST['source'],
				'ex_status'=>'true',
				'status'=>array('-1','0','1','2','5','9'),
			);
		}elseif($type == 2){
			//有效性申请($_POST['disabled']   true:启用，搜索已失效卡密；false:失效，搜索未失效卡密 )
			$filter=array(
				'source'=>$_POST['source'],
				'ex_status'=>'true',
				'status'=>array('-1','0','1','2','9'),
				'disabled' => $_POST['disabled'] == 'true'?'false':'true',
			);
		}elseif($type == 3){
			//解冻冻结申请($_POST['status']   1:解冻，搜索已冻结卡密；5:冻结，搜索  已发放、已激活  卡密)
			if($_POST['status'] == '1'){
				$status_arr = array('5');
			}elseif($_POST['status'] == '5'){
				$status_arr = array('1','2');
			}
			$filter=array(
				'source'=>$_POST['source'],
				'ex_status'=>'true',
				'status'=>$status_arr,
			);
		}
		
		$this->pagedata['filter']=$filter;
        $this->display('admin/cards/pass/update/search_pass.html');
    }
}
