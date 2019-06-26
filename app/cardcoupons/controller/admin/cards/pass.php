<?php


class cardcoupons_ctl_admin_cards_pass extends desktop_controller {

    var $workground = 'cardcoupons.wrokground.card';


    function index(){
        $actions_base['title'] = app::get('cardcoupons')->_('卡密中心');
		/*
		if($this->has_permission('updatepass')&&$_GET['view']!='1'){
			$custom_actions[] = array(
				'label'=>app::get('cardcoupons')->_('批量修改'),
				'icon'=>'update.gif',
				//'disabled'=>'false',
				'submit'=>'index.php?app=cardcoupons&ctl=admin_cards_pass&act=update',
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
		$actions_base['base_filter'] =array('source'=>'internal','ex_status'=>'true');
        $this->finder('cardcoupons_mdl_cards_pass',$actions_base);
    }
	function _views(){
		$pass_obj=kernel::single('cardcoupons_mdl_cards_pass');

		$sub_menu = array(
            0 =>array('label'=>app::get('cardcoupons')->_('全部'),'optional'=>true,
			'filter'=>array('source'=>'internal','ex_status'=>'true')),
			1 =>array('label'=>app::get('cardcoupons')->_('未发放'),'optional'=>true,
			'filter'=>array('source'=>'internal','ex_status'=>'true','status'=>'0')),
			2 =>array('label'=>app::get('cardcoupons')->_('已预售'),'optional'=>true,
			'filter'=>array('source'=>'internal','ex_status'=>'true','status'=>'-1')),
			3 =>array('label'=>app::get('cardcoupons')->_('已发放'),'optional'=>true,
			'filter'=>array('source'=>'internal','ex_status'=>'true','status'=>'1')),
			4 =>array('label'=>app::get('cardcoupons')->_('已激活'),'optional'=>true,
			'filter'=>array('source'=>'internal','ex_status'=>'true','status'=>'2')),
			5 =>array('label'=>app::get('cardcoupons')->_('已使用'),'optional'=>true,
			'filter'=>array('source'=>'internal','ex_status'=>'true','status'=>'3')),
			6 =>array('label'=>app::get('cardcoupons')->_('已结算'),'optional'=>true,
			'filter'=>array('source'=>'internal','ex_status'=>'true','status'=>'4')),
			7 =>array('label'=>app::get('cardcoupons')->_('已冻结'),'optional'=>true,
			'filter'=>array('source'=>'internal','ex_status'=>'true','status'=>'5')),
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

			$this->begin('index.php?app=cardcoupons&ctl=admin_cards_pass&act=index');
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
					$data['memo']='申请修改:'.$_POST['memo'];
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
			$this->pagedata['source']=array('source'=>'internal');
			$this->display('admin/cards/pass/update.html');
		}
	}
	/**
	 * 卡密短信发送
	 */
	public function send($card_pass_id){
		$obj_pass_log=kernel::single('cardcoupons_mdl_cards_pass_log');
		if($_POST['send']=='send'){
			$obj_orders=kernel::single('b2c_mdl_orders');
			//判断卡来源-hy-2015年10月31日11:31:56
			$card_pass_obj = kernel::single('cardcoupons_mdl_cards_pass')->getList("*",array("card_pass_id"=>$_POST['card_pass_id']));
			$source = $card_pass_obj[0]['source'];
			$begin_url = 'index.php?app=cardcoupons&ctl=admin_cards_pass&act=index';
			if($source=='external'){
				$begin_url = 'index.php?app=cardcoupons&ctl=admin_excards_pass&act=index';
			}
			
			$this->begin($begin_url);
			$time=time();
			$order=$obj_orders->dump($_POST['order_id'],'*');
            $arrMemberInfo = kernel::single("pam_mdl_account")->getList("*",array("account_id"=>$order['member_id']));
	        if($arrMemberInfo[0]['account_type'] == 'card'){
	            $RELATION_ID = '0000936810';
	        }
			$_sjson = array(
				'METHOD'=>'sendMessage',
				'PHONENO'=>$_POST['mobile'],
				'MESSAGE'=>$_POST['ship_name'].'你好，你的'.$_POST['card_name'].'卡号：'.$_POST['card_no'].'.密码：'.$_POST['card_pass'],
                'SENDUSER_TYPE'=>'HUMBAS_NO',
                'RELATION_ID'=>$RELATION_ID ? $RELATION_ID : $arrMemberInfo[0]['login_name']
            );
			$order_log = array(
                'rel_id' => $_POST['order_id'],
                'op_id' =>$_SESSION['account']['user_data']['account']['account_id'],
                'op_name' =>$_SESSION['account']['user_data']['account']['login_name'] ,
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'delivery',
                'result' => 'FAILURE',
                'log_text' => serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$_POST['card_name'].'</span>短信发送'.$_POST['card_no'].'失败','data'=>array()))),
            );
			$post_data = array('serviceNo'=>'SendMessageService',"inputParam"=>json_encode($_sjson));
			$tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
			if($tmpdata != null && gettype($tmpdata) == "object"){
				$getSubActList = SFSC_HttpClient::objectToArray($tmpdata);
				if($getSubActList['RESULT_CODE'] == "10001"){
					$order_log['log_text']=serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$_POST['card_name'].'</span>短信发送'.$_POST['card_no'].'成功','data'=>array())));
					$pass_log_data['status']='1';
				}else{
					//记录发送失败
					$order_log['log_text']=serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$_POST['card_name'].'</span>短信发送'.$_POST['card_no'].'失败','data'=>array())));
					$pass_log_data['status']='2';
				}
			}else{
				//记录调用短信接口失败
				$order_log['log_text']=serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$_POST['card_name'].'</span>短信发送'.$_POST['card_no'].'异常','data'=>array())));
				$pass_log_data['status']='3';
			}
			$order_log_re=kernel::single('b2c_mdl_order_log')->save($order_log);
			$pass_log_data['card_no']	=$_POST['card_no'];
			$pass_log_data['mobile']	=$_POST['mobile'];
			$pass_log_data['card_pass']	=$_POST['card_pass_ori'];
			$pass_log_data['memo']		=$_POST['memo'];
			$pass_log_data['time']		=$time;
			$save=$obj_pass_log->insert($pass_log_data);
			$this->end(true,'操作成功');
		}else{
			$sql="SELECT cp.card_pass_id, cp.card_no,cp.card_pass ,bo.ship_mobile,ship_name,cp.order_id from sdb_cardcoupons_cards_pass as cp JOIN sdb_b2c_orders as bo ON cp.order_id=bo.order_id where cp.`status`='1' and bo.pay_status='1' and cp.card_pass_id='".$card_pass_id."';";
			$cards_pass=kernel::database()->select($sql);
			$cards_pass=$cards_pass[0];
			unset($cards_pass[0]);

			if($cards_pass){
				//解密密码
				$cards_pass['card_pass_ori'] = $cards_pass['card_pass'];
				$cards_pass['card_pass'] = kernel::single('cardcoupons_mysqlkey')->dePwByKey($cards_pass['card_pass']);
				$cards_pass['ycard_pass']=substr($cards_pass['card_pass'],0,1).'****'.substr($cards_pass['card_pass'],-1);
				$from_time=time();
				//用作判断上次发送时间间隔是否是120s
				/*$to_time=$from_time-(60*2);
				$pass_log=$obj_pass_log->dump(array('time|between'=>array($to_time,$from_time),'card_no'=>$cards_pass['card_no']),'*');
				error_log('$pass_log:'.var_export($pass_log,1)."\n",3,ROOT_DIR.'/log.txt');
				if($pass_log){
					$this->pagedata['send_time']=$from_time-$pass_log['time'];
				}*/
				$this->pagedata['cards_pass']=$cards_pass;
				$this->display('admin/cards/pass/send.html');
			}else{
				echo "卡密不存在或未被购买";
			}
		}

	}
}
