<?php
class physical_tools {
	//日历输出，遍历出满和休的时间
	function calendar($store_id,$organization_id=0){
		$obj=kernel::single('base_mdl_kvstore');
		$kv_set=$obj->getList('*',array('key'=>'physical.o2oset.set','prefix'=>'system'));
		$default_set['start_time']=7;
		$default_set['end_time']=30;
		if(isset($kv_set[0]['value']['value']['start_time']) && $kv_set[0]['value']['value']['start_time'] >=0){
			$default_set['start_time']=$kv_set[0]['value']['value']['start_time'];
		}
		if(isset($kv_set[0]['value']['value']['end_time']) && $kv_set[0]['value']['value']['end_time'] >0){
			$default_set['end_time']=$kv_set[0]['value']['value']['end_time'];
		}
		$start_time	=time()+($default_set['start_time']*60*60*24);
		$end_time	=$start_time+(($default_set['end_time']-1)*60*60*24);
		$start_time	=date('Y-m-d',$start_time);
		$end_time	=date('Y-m-d',$end_time);
		$db			=kernel::database();
		$sql="select organization_id,weekday from sdb_physical_store where store_id='".$store_id."'";
		$store=$db->select($sql);
		if(!$organization_id){
			$organization_id=$store[0]['organization_id'];
		}
		$sql="select * from sdb_physical_organization_status where organization_id ='".$organization_id."'and  start_date >='".$start_time."' and end_date <='".$end_time."' ";
		$org_data	=$db->select($sql);
		$time		=array();//存储满休时间数组
		if($org_data){
			foreach ($org_data as $key=>$value){
				$start_strtotime=strtotime($value['start_date']);
				$time[$value['status']][$start_strtotime]=date('Y-n-j',$start_strtotime);;
				$end_strtotime	=strtotime($value['end_date']);
				while($start_strtotime!=$end_strtotime){
					$time[$value['status']][$start_strtotime]=date('Y-n-j',$start_strtotime);
					$start_strtotime=$start_strtotime+(60*60*24);
				}
				
			}
		}
		$sql="select * from sdb_physical_store_status where store_id='".$store_id."' and  start_date >='".$start_time."' and end_date <='".$end_time."' ";
		$store_data	=$db->select($sql);
		if($store_data){
			foreach ($store_data as $key=>$value){
				$start_strtotime=strtotime($value['start_date']);
				$end_strtotime	=strtotime($value['end_date']);
				$time[$value['status']][$start_strtotime]=date('Y-n-j',$start_strtotime);;
				while($start_strtotime!=$end_strtotime){
					$time[$value['status']][$start_strtotime]=date('Y-n-j',$start_strtotime);
					$start_strtotime=$start_strtotime+(60*60*24);
				}
				
			}
		}
		
		$result=array();
		$time[0]=explode(',',$store[0]['weekday']);
		$weekday=array('0'=>'true','1'=>'true','2'=>'true','3'=>'true','4'=>'true','5'=>'true','6'=>'true');
		foreach($time[0] as $key=>$value){
			if($weekday[$value]){
				unset($weekday[$value]);
			}
		}
		$result['0']=$weekday;
		if($time){
			$result['1']=array_values ($time['1']);
			$result['2']=array_values ($time['2']);
			$result['3']=array_values ($time['3']);
		}
		$result['st'] =strtotime($start_time);
		$result['et'] =strtotime($end_time);
		return json_encode($result);
	}
	
	
}
