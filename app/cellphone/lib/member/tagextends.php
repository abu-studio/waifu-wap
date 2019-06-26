<?php
  
class cellphone_member_tagextends
{
    public function get_tags($store_id, $app_id, $tag_type)
    {
        $data['cellphone'] = 1;
        $oTag = &app::get('cellphone')->model('tag');
        $oTagRel = &app::get('cellphone')->model('tag_rel');
        $temp = array();
        $data['tags'] = array();
        foreach($oTagRel->getList('tag_id,rel_id', array('store_id'=>$store_id,'app_id'=>$app_id,'tag_type'=>$tag_type)) as $row){
            $temp[] = $row['tag_id'];
            $data['tags'][$row['rel_id']][] = $row['tag_id'];
        }
        if(count($temp)>0){
            $mid = array();
            foreach($oTag->getList('tag_name,tag_id',array('tag_id'=>$temp),0,-1) as $row){
                $mid[$row['tag_id']] = $row['tag_name'];
            }
            foreach($data['tags'] as $key => &$value){
                foreach($value as $ckey => &$cvalue){
                    if(array_key_exists($cvalue, $mid)){
                        $cvalue = $mid[$cvalue];
                    }
                }
                
            }
        }
        return $data;
    }
}