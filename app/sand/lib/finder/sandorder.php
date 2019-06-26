<?php
	class sand_finder_sandorder{
		/*var $column_control = "操作";
		function column_control($row){
				  if($row['status'] == '1') return false;
				  $return = '<a href="index.php?app=sand&ctl=admin_sandorder&act=recharge&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p='.$row['log_id'].'" target="dialog::{title:\''.app::get('b2c')->_('充值').'\', width:500, height:250}">'.app::get('b2c')->_('充值').'</a>&nbsp;&nbsp;&nbsp;';
				  return $return;
		}
		*/

		var $detail_basic = "查看";
		public function detail_basic($id){
			$app = app::get('sand');
			$render = $app->render();
			$obj = app::get('sand')->model('sandorder');
			$data = $obj->getRow('*',array('log_id'=>$id));	
			$memberobj = app::get('b2c')->model('members');
			
			if($data['member_id']){
				$memberdata =$memberobj->getRow('name',array('member_id'=>$data['member_id']));
				$data['member_id'] = $memberdata['name'];
			}
			if($data['operator']){
				$memberdata =$memberobj->getRow('name',array('member_id'=>$data['operator']));
				$data['operator'] = $memberdata['name'];
			}
			$render ->pagedata['data'] = $data;
			return  $render->fetch('admin/recharge.html',$this->app->app_id);
		}
	}