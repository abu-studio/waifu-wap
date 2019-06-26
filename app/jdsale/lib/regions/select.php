<?php

  
/**
 * 区域层级来得到地区的信息
 * 
 * @version 0.1
 * @package jdsale.lib.regions
 */
class jdsale_regions_select
{
	/**
	 * 通过p_region_id，区域层级来得到地区的信息
	 * @params object app object
	 * @params string p_region_id
	 * @params array 参数数组 - depth
	 * @params string 当前激活的regions id
	 * @return string html结果
	 */
	public function get_area_select(&$app, $path, $params, $selected_id=null)
	{
		$params['depth'] = $params['depth']?$params['depth']:1;
        $html = '<select onchange="selectArea(this,this.value,'.($params['depth']+1).')">';
        $html.='<option value="_NULL_">'.app::get('jdsale')->_('请选择...').'</option>';

		$filter = ($path) ? array('region_grade' =>$params['depth'],'p_region_id'=>$path) : array('region_grade' =>$params['depth']);
		$obj_region = $app->model('regions');
        if ($rows = $obj_region->getList('*', $filter, 0, -1, 'ordernum ASC'))
		{
            foreach ($rows as $item)
			{
                //if ($item['region_grade']<=$app->getConf('system.area_depth'))
				if ($item['region_grade'] <= 4) {
                    //$selected = $selected_id == $item['region_id']?'selected="selected"':'';
					$selected = $selected_id == $item['area_id']?'selected="selected"':'';

					// 查找当前地区是否有子集
					//$filter = array('region_grade' =>$params['depth']+1,'p_region_id'=>$item['region_id']);
					$filter = array('region_grade' =>$params['depth']+1,'p_region_id'=>$item['area_id']);
                    if ($c_rows = $obj_region->getList('*',$filter))
					{
						//$html.= '<option has_c="true" value="'.$item['region_id'].'" '.$selected.'>'.$item['local_name'].'</option>';
						$html.= '<option has_c="true" value="'.$item['area_id'].'" '.$selected.'>'.$item['local_name'].'</option>';
                    }
					else 
					{
                        //$html.= '<option value="'.$item['region_id'].'" '.$selected.'>'.$item['local_name'].'</option>';
						$html.= '<option value="'.$item['area_id'].'" '.$selected.'>'.$item['local_name'].'</option>';
                    }
                }
				else
				{
                    $no = true;
                }
            }
			
            $html.='</select>';
            if($no) $html="";
			
            return $html;
        }
		else
		{
            return false;
        }
	}
}
