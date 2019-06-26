<?php
 

class sand_ctl_admin_sandorder extends desktop_controller{

    var $workground = 'ectools.wrokground.order';


    function index(){
		$custom_actions[] =  array('label' => "批量操作",
                        'icon' => 'batch.gif',
                        'group' => array(
                            array('label' => app :: get('b2c') -> _('个人信息导出'), 'icon' => 'download.gif', 'submit' => 'index.php?app=sand&ctl=admin_sandorder&act=export_member','target' => '_blank'),
                            array('label' => app :: get('b2c') -> _('资金分配导出'), 'icon' => 'download.gif', 'submit' => 'index.php?app=sand&ctl=admin_sandorder&act=export_money','target' => '_blank'),                        
                            
							array('label' => app :: get('b2c') -> _('导出充值表'), 'icon' => 'download.gif', 'submit' => 'index.php?app=sand&ctl=admin_sandorder&act=export_recharge','target' => '_blank'),                        
                            ),
                        );
						
		$custom_actions[] = array(
                'label'=>app::get('b2c')->_('导入充值表'),
                'icon'=>'add.gif',
                'href'=>'index.php?app=sand&ctl=admin_sandorder&act=importRecharge',
                'target'=>'dialog::{title:\''.app::get('b2c')->_('导入充值表').'\',width:500,height:150}'
            );	


        $this->finder('sand_mdl_sandorder',array(
            'title'=>app::get('b2c')->_('杉德充值列表'),
			'allow_detail_popup'=>true,
			'use_buildin_export'=>false,
			'use_buildin_set_tag'=>false,
			'use_buildin_recycle'=>false,
			'use_buildin_filter'=>true,
			'use_view_tab'=>true,
            'actions'=>$custom_actions
            ));
    }

     function _views(){
	    $sub_menu = array();
        $mdl_redbag= $this->app->model('sandorder');
		//全部记录
            $count = $mdl_redbag->count("");
            if($count >0){
                $sub_menu[0] = array('label'=>app::get('b2c')->_('全部'),'optional'=>true,'filter'=>"",'addon'=>$count,'href'=>'index.php?app=sand&ctl=admin_sandorder&act=index');
            }
		
            $filter1 = array('status'=>array('0','2'));
            $count1 = $mdl_redbag->count($filter1);
            if($count1 >0){
                $sub_menu[1] = array('label'=>app::get('b2c')->_('未充值'),'optional'=>true,'filter'=>$filter1,'addon'=>$count1,'href'=>'index.php?app=sand&ctl=admin_sandorder&act=index&view=1');
            }
		
			$filter2 = array('status'=>'1');
			 $count2 = $mdl_redbag->count($filter2);
            if($count2 >0){
                $sub_menu[2] = array('label'=>app::get('b2c')->_('已充值'),'optional'=>true,'filter'=>$filter2,'addon'=>$count2,'href'=>'index.php?app=sand&ctl=admin_sandorder&act=index&view=2');
            }
		
			return  $sub_menu;
	}

	//调用充值页面
	function recharge(){
		$log_id = $_GET['p'];
		$sandobj = app::get('sand')->model('sandorder');
		$sanddata = $sandobj->getRow('*',array('log_id'=>$log_id));
		$this->pagedata['data'] = $sanddata;
		$this->display('admin/dorecharge.html');
	}

	//进行充值
	function torecharge(){
		$this->begin('index.php?app=sand&ctl=admin_sandorder&act=index');
		$log_id = $_POST['log_id'];
		$mark_text = $_POST['mark_text'];
		$sandobj = app::get('sand')->model('sandorder');
		$sanddata = $sandobj->getRow('*',array('log_id'=>$log_id));
		//更新订单状态
		$order_object = app::get('b2c')->model('orders');
		$order_object ->update(array('receiving_state'=>2,'ship_status'=>1,'status'=>'finish'),array('order_id'=>$sanddata['order_id']));
		//更新杉德列表状态
		$sandobj->update(array('status'=>'1','mark_text'=>$mark_text,'recharge_time'=>time()),array('log_id'=>$log_id));
		$this->end(true);
	}
	
	//个人信息导出
	function export_member(){
		$arr = $_POST;
		$sand_object = app::get('sand')->model('sandorder');
		$sand_data = $sand_object->getList('*',$arr);
		
		$title = array();
		array_push($title,'序号','成员姓名','证件类型（01:身份证 02:军官证 03:护照 04:其他 06:营业执照 08:台胞证 11:员工证）','证件号码','成员用户登录名','成员用户手机号','成员用户邮箱','成员用户登录密码','绑定银行卡卡号','绑定银行卡对应真实姓名','绑定银行卡所属银行联行行号','绑定银行卡开户行','所属机构','备注');
		$csv_data[0] = $title;
		
		$member_object = app::get('b2c')->model('members');
		$account_object = app::get('pam')->model('account');
		$i = 1;
		foreach($sand_data as $key=>$val){
			$memdata = array();
			$account_data = $account_object->getRow('*',array('account_id'=>$val['member_id']));
			$member_data = $member_object->getRow('*',array('member_id'=>$val['member_id']));
			//$MemberInfoArr = SFSC_HttpClient::doMemberMain($val['member_name']);

			$_sjson = "{'METHOD':'getSingleEmployeeByHumbasNo','HUMBAS_NO':'".$val['member_name']."'}";
			$post_data = array('serviceNo'=>'EmployeeService',"inputParam"=>$_sjson);
			$pageContents = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);
			$MemberInfoArr = SFSC_HttpClient::objectToArray($pageContents);

			array_push($memdata,$i,$MemberInfoArr['RESULT_DATA']['NAME'],'01',$MemberInfoArr['RESULT_DATA']['ID'],$val['sand_name'],$member_data['sandopen_mobile'],$member_data['email'],'efescobm','','','','','',$account_data['company_no']);
			$csv_data[] = $memdata;
			$i++;
		}
		
		$csv_string = null;
        $csv_row = array();
        foreach( $csv_data as $key => $csv_item )
        {
            $current = array();
            foreach( $csv_item AS $k=> $item )
            {
			/****************************************************************************************************************************
			*很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
			****************************************************************************************************************************/
			if($k == '3'&&$item&&$key>0){
                $item = '#'.$item;
            }
			
            $current[] = is_numeric( $item ) ? $item : '"' . str_replace( '"', '""', $item ) . '"';
            //$current[] ='"' . str_replace( '"', '""', $item ) . '"';
            }
            $csv_row[]    = implode( "," , $current );
        }
        $csv_string = implode( "\r\n", $csv_row );
        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=Personal.csv");
        header('Expires:0');
        header('Pragma:public');
        echo mb_convert_encoding($csv_string, 'GBK', 'UTF-8');
        
	}
	//资金分配导出
	function export_money(){
		$arr = $_POST;
		$sand_object = app::get('sand')->model('sandorder');
		$sand_data = $sand_object->getList('*',$arr);
		
		$title = array();
		array_push($title,'序号','成员姓名','用户名','证件号码','手机号码','转账金额','追加可使用企业额度','是否为额度重置','订单号');
		$csv_data[0] = $title;
		
		$member_object = app::get('b2c')->model('members');
		$i = 1;
		foreach($sand_data as $key=>$val){
			$memdata = array();
			$member_data = $member_object->getRow('*',array('member_id'=>$val['member_id']));
			
			$_sjson = "{'METHOD':'getSingleEmployeeByHumbasNo','HUMBAS_NO':'".$val['member_name']."'}";
			$post_data = array('serviceNo'=>'EmployeeService',"inputParam"=>$_sjson);
			$pageContents = SFSC_HttpClient::doPost(DO_SERVER_URL, $post_data);
			$MemberInfoArr = SFSC_HttpClient::objectToArray($pageContents);
						
			array_push($memdata,$i,$MemberInfoArr['RESULT_DATA']['NAME'],$val['sand_name'],$MemberInfoArr['RESULT_DATA']['ID'],$member_data['sandopen_mobile'],$val['amount'],'','',$val['order_id']);
			$csv_data[] = $memdata;
			$i++;
		}
		
		$csv_string = null;
        $csv_row = array();
        foreach( $csv_data as $key => $csv_item )
        {
            $current = array();
            foreach( $csv_item AS $k=> $item )
            {
			/****************************************************************************************************************************
			*很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
			****************************************************************************************************************************/
			if(($k == '3'||$k == '8')&&$item&&$key>0){
                $item = '#'.$item;
            }

            $current[] = is_numeric( $item ) ? $item : '"' . str_replace( '"', '""', $item ) . '"';
            //$current[] ='"' . str_replace( '"', '""', $item ) . '"';
            }
            $csv_row[]    = implode( "," , $current );
        }
        $csv_string = implode( "\r\n", $csv_row );
        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=Allocation.csv");
        header('Expires:0');
        header('Pragma:public');
        echo mb_convert_encoding($csv_string, 'GBK', 'UTF-8');
	}
	
	//导入充值列表
	function importRecharge(){
		$this->display('admin/setRecharge.html'); 
	}
	
	function downRecharge(){
		$csv_data[0]=array('订单号(order_id)','充值金额(amount)');
		$csv_data[1]=array('','');
		$csv_string = null;
        $csv_row    = array();
        foreach( $csv_data as $key => $csv_item )
        {
            /*
            if( $key === 0 )
            {
                $csv_row[]    = implode( "," , $csv_item );
                continue;
            }
            */
            $current    = array();
            foreach( $csv_item AS $item )
            {
			/****************************************************************************************************************************
			*很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
			****************************************************************************************************************************/
            $current[] = is_numeric( $item ) ? $item : '"' . str_replace( '"', '""', $item ) . '"';
            //$current[] ='"' . str_replace( '"', '""', $item ) . '"';
            }
            $csv_row[]    = implode( "," , $current );
        }

        $csv_string = implode( "\r\n", $csv_row );


        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=pass".$_GET['card_id'].".csv");
        header('Expires:0');
        header('Pragma:public');
        echo mb_convert_encoding($csv_string, 'GBK', 'UTF-8');
        //echo "\xFF\xFE".mb_convert_encoding( $csv_string, 'UCS-2LE', 'UTF-8' );
	}
	
	//根据导入的杉德数据，
	function do_importRecharge(){
		$obj = new desktop_user();
		$name =  $obj->get_login_name();
		$db = kernel::database();
		$sandorder_object = app::get('sand')->model('sandorder');
		$order_object = app::get('b2c')->model('orders');
		$transaction_status = $db->beginTransaction();
		$this->begin('index.php?app=sand&ctl=admin_sandorder&act=index');
		$csv_array=$this->read_csv($_FILES['sandRecharge']);
		if(!is_array($csv_array) || empty($csv_array)){
			$this->end(false,app::get('b2c')->_('无数据录入'));
		}else{
			foreach($csv_array as $key=>$val){
				if($val['order_id'] ==''||$val['amount'] ==''){
					$this->end(false,app::get('b2c')->_('第'.$key.'条数据订单号和充值金额都不能为空！'));
				}else{
					$result = $sandorder_object->update(array('status'=>'1','recharge_time'=>time(),'operator'=>$name),array('order_id'=>$val['order_id'],'amount'=>$val['amount']));
					if(!$result){
						$this->end(false,app::get('b2c')->_('第'.$key.'条数据错误，请核对！'));
					}else{
						$results = $order_object ->update(array('receiving_state'=>2,'ship_status'=>1,'status'=>'finish'),array('order_id'=>$val['order_id']));
						if(!$results){
							$db->rollback();
							$this->end(false,app::get('b2c')->_('操作失败'));
						}
					}
				}
			}
			$db->commit($transaction_status);
			$this->end(true,app::get('b2c')->_('操作成功'));
		}
	}
	
	
	function read_csv($file){
		$file_type = substr(strrchr($file['name'],'.'),1);
		if(empty($file['tmp_name'])){
			$this->end(false,app::get('b2c')->_('文件不能为空'));
		}
		// 检查文件格式
	   if ($file_type != 'csv'){
			$this->end(false,app::get('b2c')->_('文件格式不对,请重新上传'));
		}
		$handle = fopen($file['tmp_name'],"r");
		
		$row = 0;
		$post=array();
		$key=0;
		$m_key=array();
		setlocale(LC_CTYPE, "zh_CN.GBK");//防止以中文开头时读取的内容为空
		while ($data = fgetcsv($handle,1000,',')){
			$row++;
			if ($row == 1){
				foreach($data as $k_key=>$k_value){
					if($k_value){
						$result = array(); 
						preg_match_all("/(?:\()(.*)(?:\))/i",$k_value, $result);
						$k_value=explode(':',$k_value);
						$m_key[$k_key]=$result[1][0];
					}	
				}
			}else{
				$num = count($data);
				// 这里会依次输出每行当中每个单元格的数据
				foreach($data as $v_key=>$v_value){
					$v_value1 = iconv('GBK','UTF-8',$v_value);
					$post[$key][$m_key[$v_key]]=$v_value1;
				}
			}
		   $key++;
		}
		fclose($handle);
		return $post;
	}
	//充值导出
	function export_recharge(){
		$arr = $_POST;
		$sand_object = app::get('sand')->model('sandorder');
		$sand_data = $sand_object->getList('order_id,amount',$arr);
		
		$title = array('订单号(order_id)','充值金额(amount)');
		
		$csv_data[0] = $title;
		
		$member_object = app::get('b2c')->model('members');
		$i = 1;
		foreach($sand_data as $key=>$val){
			$csv_data[] = array($val['order_id'],$val['amount']);
		
		}
		
		$csv_string = null;
        $csv_row = array();
        foreach( $csv_data as $key => $csv_item )
        {
            $current = array();
            foreach( $csv_item AS $item )
            {
			/****************************************************************************************************************************
			*很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
			****************************************************************************************************************************/
			if(strlen($item) > 8 && eregi("^[0-9]+$",$item)){
                 $item .= "\r";
            }

            $current[] = is_numeric( $item ) ? $item : '"' . str_replace( '"', '""', $item ) . '"';
            //$current[] ='"' . str_replace( '"', '""', $item ) . '"';
            }
            $csv_row[]    = implode( "," , $current );
        }
        $csv_string = implode( "\r\n", $csv_row );
        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=Allocation.csv");
        header('Expires:0');
        header('Pragma:public');
        echo mb_convert_encoding($csv_string, 'GBK', 'UTF-8');
	}
	
}	