<?php
 

class jdsale_ctl_wap_regions extends b2c_frontpage{

    function __construct($app){
        parent::__construct($app);
    }

	//获取京东地址  获取同级地区
	public function getRegion(){
		$id = $_POST['dataParms'];
		$regObject = app::get('jdsale')->model('regions');
		$regDate = $regObject ->getList('area_id,local_name',array('p_region_id'=>$id));
		$html = '';
		foreach($regDate as $val){
			$html.='<li> <a href="javascript:void(0);" title="'.$val['local_name'].'" onclick="clickRegion('.$val["area_id"].',event)" data-value="'.$val['area_id'].'">'.$val['local_name'].'</a></li>';								
		}
		echo $html;
	}

	//获取京东地址  获取所有省市
	public function getProvince(){
		$id = $_POST['dataParms'];
		$regObject = app::get('jdsale')->model('regions');
		$regDate = $regObject ->getList('area_id,local_name',array('region_grade'=>$id));
		$html = '';
		foreach($regDate as $val){
			$html.='<li> <a href="javascript:void(0);" title="'.$val['local_name'].'" onclick="clickRegion('.$val["area_id"].',event)" data-value="'.$val['area_id'].'">'.$val['local_name'].'</a></li>';							
		}
		echo $html;
	}
    
	//获取京东地址  获取子级地区
	public function getChildRegion(){
		$id = $_POST['dataParms'];
		$regObject = app::get('jdsale')->model('regions');
		$regDate = $regObject ->getList('area_id,local_name',array('p_region_id'=>$id));
		$html = '';
		foreach($regDate as $val){
			$html.='<li> <a href="javascript:void(0);" title="'.$val['local_name'].'" onclick="clickRegion('.$val["area_id"].',event)" data-value="'.$val['area_id'].'">'.$val['local_name'].'</a></li>';								
		}
		
		echo $html;
	}
	
    
}
