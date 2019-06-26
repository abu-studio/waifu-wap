<?php
/**
 * 导入京东地区数据的工具类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/11
 * Time: 16:51
 */

class jdsale_regions_import{

    /**
     * 构造方法
     * @param object 当前应用app的对象
     * @return null
     */
    function __construct($app){
        $this->app = $app;
        $this->db = kernel::database();
    }


    /**
     * 初始化导入京东的地区数据
     */
    public function initialData(){
        $is_initial = app::get('jdsale')->getConf('areadata.isinitial');
        if ($is_initial == '2'){
            return true;
        }

        $jdsale_api = kernel::single('jdsale_api_area');
        $jdsale_regions_operation = kernel::single('jdsale_regions_operation');
        //调用一级地址api接口
        $result_level_1_arr = $jdsale_api->getAllProvinces(null);
        $result_level_1 = $result_level_1_arr['result'];
        if (empty($result_level_1)){
            return false;
        }
        asort($result_level_1);

        $retval= true;
        foreach($result_level_1 as $k1 => $v1){
            $aData['area_id'] = $v1;
            $aData['local_name'] = $k1;
            if(!$jdsale_regions_operation->insertDlArea($aData,$msg)){
                //echo app::get('jdsale')->_(' 新建一级地址失败，').$msg;
                $retval = false;
            }else{
                //echo  app::get('jdsale')->_(' 新建一级地址成功，地区名称：').$k1;
            }

             //$aData['p_region_id'] ;
            //调用二级地址api接口
            $result_level_2_arr = $jdsale_api->getCitysByProvinceId(array('id'=>$v1));
            $result_level_2 = $result_level_2_arr['result'];
            asort($result_level_2);

            foreach($result_level_2 as $k2 => $v2){
                $aData2['area_id']     = $v2;
                $aData2['local_name']  = $k2;
                $aData2['p_region_id'] = $v1;
                if(!$jdsale_regions_operation->insertDlArea($aData2,$msg)){
                    //echo app::get('jdsale')->_(' 新建二级地址失败，').$msg;
                    $retval = false;
                }else{
                    //echo app::get('jdsale')->_(' 新建二级地址成功，地区名称：').$k2;
                }

                //调用三级地址api接口
                $result_level_3_arr = $jdsale_api->getCountysByCityId(array('id'=>$v2));
                $result_level_3 = $result_level_3_arr['result'];
                asort($result_level_3);
                foreach($result_level_3 as $k3 => $v3) {
                    $aData3['area_id']     = $v3;
                    $aData3['local_name']  = $k3;
                    $aData3['p_region_id'] = $v2;
                    if (!$jdsale_regions_operation->insertDlArea($aData3, $msg)) {
                        //echo app::get('jdsale')->_(' 新建三级地址失败，') . $msg;
                        $retval = false;
                    } else {
                        //echo app::get('jdsale')->_(' 新建三级地址成功，地区名称：') . $k3;
                    }
                    //调用四级地址api接口
                    $result_level_4_arr = $jdsale_api->getTownsByCountyId(array('id'=>$v3));
                    $result_level_4 = $result_level_4_arr['result'];
                    if (empty($result_level_4)){
                        unset($aData3);
                        continue;
                    }
                    asort($result_level_4);
                    foreach($result_level_4 as $k4 => $v4) {
                        $aData4['area_id']     = $v4;
                        $aData4['local_name']  = $k4;
                        $aData4['p_region_id'] = $v3;
                        if (!$jdsale_regions_operation->insertDlArea($aData4, $msg)) {
                            //echo app::get('jdsale')->_(' 新建四级地址失败，') . $msg;
                            $retval = false;
                        } else {
                            //echo app::get('jdsale')->_(' 新建四级地址成功，地区名称：') . $k4;
                        }
                        unset($aData4);
                    }
                    unset($aData3);
                }
                unset($aData2);

            }
            unset($aData);
        }

        $jdsale_regions_operation->updateRegionData();

        return $retval;

    }

    /**
     * 若有京东地址变更消息推送，更新地址数据库
     */
    public function update(){

        //updateRegionData
        //update js file?
    }

    /**
     * 根据京东的地址id迁移修改原有运费模板的相关数据
     * 在执行本方法前请备份原有sdb_b2c_dlytype表的数据
     */
    public function migration(){

        $b2c_dlyt_mdl = app::get('b2c')->model('dlytype');
        $result = $b2c_dlyt_mdl ->getList('dt_id,area_fee_conf',array());

        foreach($result as $k1 => &$v1) {
            //var_dump($v1);
            $area_fee_conf = unserialize($v1['area_fee_conf']);
            $dt_id = $v1['dt_id'];
            if ($area_fee_conf && is_array($area_fee_conf)) {
                $area_fee_conf_new = array();
                foreach ($area_fee_conf as $k2 => &$v2) {
                    $areas = explode(',', $v2['areaGroupId']);
                    //var_dump($areas);
                    $area_fee_conf_new_sub = array();
                    $ectools_regions_mdl   = app::get('ectools')->model('regions');
                    $jdsale_regions_mdl    = app::get('jdsale')->model('regions');
                    // 再次解析字符
                    foreach ($areas as &$strArea) {
                        if (strpos($strArea, '|')) {
                            //全省区域
                            $strArea    = substr($strArea, 0, strpos($strArea, '|'));
                            $old_region =
                                $ectools_regions_mdl->getRow('local_name', array(
                                    'region_id' => $strArea,
                                ));
                            $new_region =
                                $jdsale_regions_mdl->getRow('area_id', array(
                                    'local_name' => $old_region['local_name'],
                                ));
                            if ($new_region) {
                                $area_fee_conf_new_sub[] = $new_region['area_id'] . '|close';
                            }
                        } else {
                            //部分区域
                            $old_region =
                                $ectools_regions_mdl->getRow('local_name', array(
                                    'region_id' => $strArea,
                                ));
                            $new_region =
                                $jdsale_regions_mdl->getRow('area_id', array(
                                    'local_name' => $old_region['local_name'],
                                ));
                            if ($new_region) {
                                $area_fee_conf_new_sub[] = $new_region['area_id'];
                            }
                        }
                    }

                    $area_fee_conf[$k2]['areaGroupId']= implode(',', $area_fee_conf_new_sub);
                }
                $area_fee_conf_new = array('area_fee_conf' =>serialize($area_fee_conf));
            //var_dump($area_fee_conf_new);
                $b2c_dlyt_mdl->update($area_fee_conf_new,array('dt_id' => $dt_id));
            }
        }
    }
}