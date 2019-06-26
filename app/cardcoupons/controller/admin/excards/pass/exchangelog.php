<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class cardcoupons_ctl_admin_excards_pass_exchangelog extends desktop_controller
{

    var $pagelimit = 10;
	var $workground = 'cardcoupons.wrokground.card';
    function index()
    {
		$this->finder('cardcoupons_mdl_cards_exchangelog', 
		   array('title' => app::get('b2c')->_('外部卡兑换记录'),
		   'use_buildin_export'=>true,
		   'actions' => array(
				array(
					'label' => "导入兑换记录",
					'href' => 'index.php?app=cardcoupons&ctl=admin_excards_pass_exchangelog&act=import',
					'target' => 'dialog::{title:\'' . app::get('cardcoupons')->_('导入兑换记录') . '\',width:500,height:200}'
				),
			),
		   'use_view_tab'=>true,
		   'force_view_tab'=>true,
		   
		));
    }
	
	 function import()
	 {
		if($_GET['card_id']&&$_GET['batch']){
		$card=kernel::single('cardcoupons_mdl_cards');
		$card_name=$card->getList('name',array('card_id'=>$_GET['card_id']));
		$this->pagedata['card_name']=$card_name[0]['name'];
		$this->pagedata['card_id']=$_GET['card_id'];
		$this->pagedata['batch']=$_GET['batch'];
		$this->display('admin/excards/exchange.html');
		}else{
        $this->display('admin/excards/exchange.html');
		}
	 }
	 
	function create_balance()
	{	
		$db = kernel::database();
        $transaction_status = $db->beginTransaction();
		$this->begin('index.php?app=cardcoupons&ctl=admin_excards_pass_exchangelog&act=index');
		$exchange_obj = kernel::single('cardcoupons_mdl_cards_exchangelog');
		$exchange_items_obj = kernel::single('cardcoupons_mdl_cards_exchangelog_items');
		$pass_obj = kernel::single('cardcoupons_mdl_cards_pass');
		$balance_obj = kernel::single('cardcoupons_mdl_cards_balance');
		$balance_items = kernel::single('cardcoupons_mdl_cards_balance_items');
		$order_items = kernel::single('b2c_mdl_order_items');
		$batch = $_GET['batch'];
		$card_id = $_GET['card_id'];
		$settlement_no = $balance_obj ->GetBillNo();
		$cards = $exchange_items_obj->getList('*',array('batch'=>$batch));
		$amount = 0; //销售总计
		$cost = 0; //成本总计
		$num= 0;
		$exchange = $exchange_obj->getList('*',array('batch'=>$batch));
		if($exchange[0]['status']==1){
			$db->rollback();
			$this->end(false,'该批次已生成结算报表！');
		}
		foreach($cards as $key=>$value)
		{
			//根据卡号密码获取订单ID
			$pass = $pass_obj ->getList('*',array('card_no'=>$value['card_no'],'card_pass'=>$value['card_pass'],'card_id'=>$card_id));
			if(!$pass){
				$db->rollback();
				$this->end(false,'卡号'.$value['card_no'].'不存在！');
			}
			//判断该卡是否可以结算
			$status = $pass[0]['status'];
			if($status!='1'){
				$db->rollback();
				$this->end(false,'卡号'.$value['card_no'].'已结算或未发放！');
			}
			$order_id = $pass[0]['order_id'];
			if($order_id =='0'){
				$db->rollback();
				$this->end(false,'卡号'.$value['card_no'].'订单号不存在！');
			}
			$card_name = $pass[0]['card_name'];
			//根据订单ID获取卡的成本及销售价 
			$items = $order_items->getList('*',array('order_id'=>$order_id,'name'=>$card_name));
			$amount =$amount +$items[0]['price'];
			$cost = $cost +$items[0]['cost'];
			$data_items=array(
				'card_id'=>$card_id,
				'card_name'=>$card_name,
				'card_no'=>$value['card_no'],
				'card_pass'=>$value['card_pass'],
				'item_money'=>$items[0]['price'],
				'cost_money'=>$items[0]['cost'],
				'settlement_no'=>$settlement_no,
				'time'=>time()
			);
			if($balance_items ->insert($data_items)){
			$pass_obj->update(array('status'=>'3'),array('card_no'=>$value['card_no'],'card_pass'=>$value['card_pass'],'card_id'=>$card_id));
			$num++;
			}else{
				$db->rollback();
				$this->end(false,'生成结算报表失败！');
			}
		}
		$data_balance=array(
			'card_id'=>$card_id,
			'num'=>$num,
			'create_time'=>time(),
			'amount_money'=>$amount,
			'cost_money'=>$cost,
			'settlement_no'=>$settlement_no
		);
		if($balance_obj->insert($data_balance)){
			$exchange_obj->update(array('status' =>'1'),array('batch'=>$batch));
			$this->end(true,'生成结算报表成功！');
		}else{
			$db->rollback();
			$this->end(false,'生成结算报表失败！');
		}
	}

	 function get_batch_id(){
		$i = rand(0,99999);
        do{
            if(99999==$i){
                $i=0;
            }
            $i++;
            $batch = date('ymdH').str_pad($i,5,'0',STR_PAD_LEFT);
			$exchange_obj=kernel::single('cardcoupons_mdl_cards_exchangelog');
            $row = $exchange_obj->db->selectrow('SELECT batch from sdb_cardcoupons_cards_exchangelog where batch ='.$batch);
        }while($row);
        return $batch;
	}
	
	 function create(){
		$db = kernel::database();
        $transaction_status = $db->beginTransaction();
		$this->begin('index.php?app=cardcoupons&ctl=admin_excards_pass_exchangelog&act=index');
		 $pass_array=$this->read_csv($_FILES['exchange_cards']);
		 $pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
		 $exchange_items_obj=kernel::single('cardcoupons_mdl_cards_exchangelog_items');
		 if(!is_array($pass_array) || empty($pass_array)){
				$this->end(false,app::get('b2c')->_('无卡密数据录入'));
			}else{
				$batch=$this->get_batch_id();
				$card_no_arr=array();
				$card_pass_arr=array();
				foreach($pass_array as $key=>$value){
					if($value['card_pass']){
						if($value['card_no']){
							$card_no_arr[]=$value['card_no'];
						}else{
							$card_pass_arr[]=$value['card_pass'];
						}
					}else{
						//卡密不能为空
						$this->end(false,app::get('b2c')->_('卡密不能为空'));
					}
				}
				if($card_no_arr){
					/*判断录入卡号是否重复 start*/
					// 获取去掉重复数据的数组 
					$unique_arr = array_unique ( $card_no_arr ); 
					// 获取重复数据的数组 
					$repeat_arr = array_diff_assoc ( $card_no_arr, $unique_arr ); 

					if(is_array($repeat_arr) && !empty($repeat_arr)){
						$this->end(false,app::get('b2c')->_('录入卡号重复，请筛选重试'));
					}
					
					$old=$exchange_items_obj->getList('*',array('card_no'=>$card_no_arr,'card_id'=>$_POST['card_id']));
					if($old){
						foreach($old as $key=>$value){
							$old_no[]=$value['card_no'];
						}
					$old_no = array_unique ( $old_no ); 
					$msg = implode(',',$old_no);
					$this->end(false,app::get('b2c')->_('卡号'.$msg.'已导入，请筛选重试'));
					}
				}

				if($card_pass_arr){
					/*判断录入卡密是否重复 start*/
					// 获取去掉重复数据的数组 
					$unique_arr = array_unique ( $card_pass_arr ); 
					// 获取重复数据的数组 
					$repeat_arr = array_diff_assoc ( $card_pass_arr, $unique_arr ); 

					if(is_array($repeat_arr) && !empty($repeat_arr)){
						$this->end(false,app::get('b2c')->_('录入卡密重复，请筛选重试'));
					}
					/*判断录入卡密是否重复 end*/
					$old=$exchange_items_obj->getList('*',array('card_no'=>'','card_pass'=>$card_pass_arr,'card_id'=>$_POST['card_id']));
					if($old){
						foreach($old as $key=>$value){
							$old_pass[]=$value['card_pass'];
						}
					$old_pass = array_unique ( $old_pass ); 
					$msg = implode(',',$old_pass);
					$this->end(false,app::get('b2c')->_('卡密'.$msg.'已导入，请筛选重试'));
					}
				}
				
				//将批次号存入表中
				$exchange_obj=kernel::single('cardcoupons_mdl_cards_exchangelog');
				$data_ex=array();
				$data_ex['card_id']=$_POST['card_id'];
				$data_ex['batch']=$batch;
				$data_ex['num']=count($pass_array);
				$data_ex['create_time']=time();
				$result=$exchange_obj->insert($data_ex);
				if($result){
				foreach($pass_array as $key=>$value){
					$data=array();
					$res=$pass_obj->getList('*',array('card_no'=>$value['card_no'],'card_pass'=>$value['card_pass'],'card_id'=>$_POST['card_id'],'ex_status'=>'true','source'=>'external'));
					$status=$res[0]['status'];
					if(!$status&&$status!='0'){
						$data['memo']='卡券不存在';
						$data['status']='0';
					}else{
						if($status=='-1'||$status=='0'){
						$data['memo']='卡券未发放';
						$data['status']='0';
						}
						if($status=='1'){
						$data['memo']='卡券可结算';
						$data['status']='1';
						}
						if($status=='2'){
						$data['memo']='卡券已激活';
						$data['status']='0';
						}
						if($status=='3'){
						$data['memo']='卡券已使用';
						$data['status']='0';
						}
						if($status=='4'){
						$data['memo']='卡券已结算';
						$data['status']='0';
						}
						if($status=='5'){
						$data['memo']='卡券冻结中';
						$data['status']='0';
						}
					}
					$data['card_no']=$value['card_no'];
					$data['card_pass']=$value['card_pass'];
					$data['card_id']=$_POST['card_id'];
					$data['batch']=$batch;
					$data['time']=time();
					$res=$exchange_items_obj->insert($data);
					if(!$res){
						$db->rollback();
						$this->end(false,app::get('b2c')->_('操作失败'));
					}
				}
				$this->end(true,app::get('b2c')->_('操作成功'));
			}else{
				$db->rollback();
				$this->end(false,app::get('b2c')->_('操作失败'));
			}
		}
	 }
	 
	 function update(){
		 $db = kernel::database();
		 $transaction_status = $db->beginTransaction();
		 $this->begin('index.php?app=cardcoupons&ctl=admin_excards_pass_exchangelog&act=index');
		 $pass_array=$this->read_csv($_FILES['exchange_cards']);
		 $pass_obj=kernel::single('cardcoupons_mdl_cards_pass');
		 $exchange_items_obj=kernel::single('cardcoupons_mdl_cards_exchangelog_items');
		 if(!is_array($pass_array) || empty($pass_array)){
				$this->end(false,app::get('b2c')->_('无卡密数据录入'));
			}else{
				$batch=$_POST['batch'];
				$card_no_arr=array();
				$card_pass_arr=array();
				foreach($pass_array as $key=>$value){
					if($value['card_pass']){
						if($value['card_no']){
							$card_no_arr[]=$value['card_no'];
						}else{
							$card_pass_arr[]=$value['card_pass'];
						}
					}else{
						//卡密不能为空
						$this->end(false,app::get('b2c')->_('卡密不能为空'));
					}
				}
				if($card_no_arr){
					/*判断录入卡号是否重复 start*/
					// 获取去掉重复数据的数组 
					$unique_arr = array_unique ( $card_no_arr ); 
					// 获取重复数据的数组 
					$repeat_arr = array_diff_assoc ( $card_no_arr, $unique_arr ); 

					if(is_array($repeat_arr) && !empty($repeat_arr)){
						$this->end(false,app::get('b2c')->_('录入卡号重复，请筛选重试'));
					}
					$old=$exchange_items_obj->getList('*',array('batch|noequal'=>$batch,'card_no'=>$card_no_arr,'card_id'=>$_POST['card_id']));
					if($old){
						foreach($old as $key=>$value){
							$old_no[]=$value['card_no'];
						}
					$old_no = array_unique ( $old_no ); 
					$msg = implode(',',$old_no);
					$this->end(false,app::get('b2c')->_('卡号'.$msg.'已在其他批次导入，请筛选重试'));
					}
				}

				if($card_pass_arr){
					/*判断录入卡密是否重复 start*/
					// 获取去掉重复数据的数组 
					$unique_arr = array_unique ( $card_pass_arr ); 
					// 获取重复数据的数组 
					$repeat_arr = array_diff_assoc ( $card_pass_arr, $unique_arr ); 

					if(is_array($repeat_arr) && !empty($repeat_arr)){
						$this->end(false,app::get('b2c')->_('录入卡密重复，请筛选重试'));
					}
					/*判断录入卡密是否重复 end*/
					$old=$exchange_items_obj->getList('*',array('card_no'=>'','card_pass'=>$card_pass_arr,'card_id'=>$_POST['card_id'],'batch|noequal'=>$batch));
					if($old){
						foreach($old as $key=>$value){
							$old_pass[]=$value['card_pass'];
						}
					$old_pass = array_unique ( $old_pass ); 
					$msg = implode(',',$old_pass);
					$this->end(false,app::get('b2c')->_('卡密'.$msg.'已在其他批次导入，请筛选重试'));
					}
				}
				
				//更新批次信息
				$exchange_obj=kernel::single('cardcoupons_mdl_cards_exchangelog');
				$data_ex=array();
				$data_ex['num']=count($pass_array);
				$data_ex['create_time']=time();
				$result=$exchange_obj->update($data_ex,array('batch'=>$batch));
				$delete=$exchange_items_obj->delete(array('batch'=>$batch));
				if($result&&$delete){
				foreach($pass_array as $key=>$value){
					$data=array();
					$res=$pass_obj->getList('*',array('card_no'=>$value['card_no'],'card_pass'=>$value['card_pass'],'card_id'=>$_POST['card_id'],'ex_status'=>'true','source'=>'external'));
					$status=$res[0]['status'];
					if(!$status&&$status!='0'){
						$data['memo']='卡券不存在';
						$data['status']='0';
					}else{
						if($status=='-1'||$status=='0'){
						$data['memo']='卡券未发放';
						$data['status']='0';
						}
						if($status=='1'){
						$data['memo']='卡券可结算';
						$data['status']='1';
						}
						if($status=='2'){
						$data['memo']='卡券已激活';
						$data['status']='0';
						}
						if($status=='3'){
						$data['memo']='卡券已使用';
						$data['status']='0';
						}
						if($status=='4'){
						$data['memo']='卡券已结算';
						$data['status']='0';
						}
						if($status=='5'){
						$data['memo']='卡券冻结中';
						$data['status']='0';
						}
					}
					$data['card_no']=$value['card_no'];
					$data['card_pass']=$value['card_pass'];
					$data['card_id']=$_POST['card_id'];
					$data['batch']=$batch;
					$data['time']=time();
					$res=$exchange_items_obj->insert($data);
					if(!$res){
						$db->rollback();
						$this->end(false,app::get('b2c')->_('操作失败'));
					}
				}
				$this->end(true,app::get('b2c')->_('操作成功'));
			}
			else{
				$db->rollback();
				$this->end(false,app::get('b2c')->_('操作失败'));
			}
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
		/*if ($file_encoding != 'UTF-8'){
			echo '文件编码错误,请重新上传!';
			exit;
		}*/
		
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
		//$post = iconv('GB2312','UTF-8',$post);
		return $post;
	
	}
	
	function downpass(){
		$csv_data[0]=array('卡券编号(card_no)','卡密(card_pass)');
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
}	    