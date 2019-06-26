<?php


class cardcoupons_ctl_admin_cards_pass_create extends desktop_controller {

    var $workground = 'cardcoupons.wrokground.card';


    function index(){
        $actions_base['title'] = app::get('cardcoupons')->_('卡密创建管理');
		$actions_base['actions'] = $custom_actions;
		$actions_base['use_buildin_filter'] = true;
		$actions_base['use_buildin_tagedit'] = true;
		$actions_base['use_view_tab'] = true;
		$actions_base['use_buildin_recycle'] = false;
		$actions_base['base_filter'] =array('source'=>'internal');
        $this->finder('cardcoupons_mdl_cards_pass_create',$actions_base);
    }
	function _views(){
		$pass_create_obj=kernel::single('cardcoupons_mdl_cards_pass_create');

		$sub_menu = array(
            0 =>array('label'=>app::get('cardcoupons')->_('全部'),'optional'=>true,'filter'=>array('source'=>'internal')),
			1 =>array('label'=>app::get('cardcoupons')->_('待审核'),'optional'=>true,'filter'=>array('source'=>'internal','ex_status'=>'update')),
			2 =>array('label'=>app::get('cardcoupons')->_('审核成功'),'optional'=>true,'filter'=>array('source'=>'internal','ex_status'=>'true')),
			3 =>array('label'=>app::get('cardcoupons')->_('审核失败'),'optional'=>true,'filter'=>array('source'=>'internal','ex_status'=>'false')),
        );

		foreach($sub_menu as $k=>$v){
			$show_menu[$k] = $v;
			$show_menu[$k]['addon'] = $pass_create_obj->count($v['filter']);
        }
        return $show_menu;
	}

	function shenhe($id){
		$this->begin('index.php?app=cardcoupons&ctl=admin_cards_pass_create&act=index');
		if($_POST['shenhe']=='shenhe'){
			$db = kernel::database();
			$pass_create_obj=kernel::single('cardcoupons_mdl_cards_pass_create');
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
            
			//修改卡密创建信息
			$rs = $pass_create_obj->update($data,array('id'=>$_POST['id']));
			if($rs){
				//查询卡密创建批次
				$pass_create_info = $pass_create_obj->dump(array('id'=>$_POST['id']),'card_id,batch,num');
				if($pass_create_info && $pass_create_info['card_id'] && $pass_create_info['batch'] && $pass_create_info['num']){
					//修改同批次卡密审核状态
					$rs1 = $pass_obj->update(array('ex_status'=>$_POST['ex_status']),array('batch'=>$pass_create_info['batch']));
					if($rs1){
						if($_POST['ex_status'] == "true"){
							$sql="select p.product_id,p.goods_id from ".DB_PREFIX ."cardcoupons_cards as c join ".DB_PREFIX ."b2c_products as p on c.goods_id =p.goods_id where c.card_id='".$pass_create_info['card_id']."'";
							$info=$db->selectRow($sql);
							if($info && $info['product_id'] && $info['goods_id']){
								$rs2 = $db->exec("UPDATE sdb_b2c_products SET store = case when store is not null then store else 0 end +".$pass_create_info['num']." WHERE product_id = '".$info['product_id']."'");
								if($rs2){
									$rs3 = $db->exec("UPDATE sdb_b2c_goods SET store = case when store is not null then store else 0 end +".$pass_create_info['num']." WHERE goods_id = '".$info['goods_id']."'");
									if($rs3){
										$db->commit($transaction_status);
										$this->end(true,'操作成功');
									}else{
										$db->rollback();
										$this->end(false,'操作失败6');
									}
								}else{
									$db->rollback();
									$this->end(false,'操作失败5');
								}
							}else{
								$db->rollback();
								$this->end(false,'操作失败4');
							}
						}else{
							$db->commit($transaction_status);
							$this->end(true,'操作成功');
						}
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
			$this->display('admin/cards/pass/shenhe.html');
		}
	}
}
