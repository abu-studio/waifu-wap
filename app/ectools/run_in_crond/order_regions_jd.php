<?php
/**
 * Created by PhpStorm.
 * User: yuanshaofeng
 * Date: 2018/1/27
 * Time: 下午5:26
 */
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ERROR);
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.php');
require(ROOT_DIR . '/config/mapper.php');
require(ROOT_DIR . '/app/base/kernel.php');
if (!kernel::register_autoload()) {
    require(APP_DIR . '/base/autoload.php');
}

//获取传入参数
$method = $argv[1];

if (empty($method))
{
    die('No Params');
}


app_boot();
var_dump( $method);
switch ($method){
    case "test":
        // setup_tmp_region_table();
        echo "test";
        break;
    case "rebuild":
        //从当前region表复制建立一个临时表
        setup_tmp_region_table();
        //从接口更新临时表数据
        import_regions_jd();
        //重命名表
        rename_table();
        //更新js文件
        update_region_js();
        break;
    case "updatejs":
        update_region_js();
        break;
    default: 
        die('method error');
        break;
}

echo "ok";



function setup_tmp_region_table()
{
    $db = kernel::database();
    $sql = "drop table sdb_jdsale_regions_2";
    echo $sql . "\n";
    $db->exec($sql);
    $sql = "CREATE TABLE `sdb_jdsale_regions_2` (`region_id` INT (10) UNSIGNED NOT NULL AUTO_INCREMENT,`area_id` INT (10) UNSIGNED NOT NULL,`local_name` VARCHAR (50) NOT NULL DEFAULT '',`package` VARCHAR (20) NOT NULL DEFAULT '',`p_region_id` INT (10) UNSIGNED DEFAULT NULL,`region_path` VARCHAR (255) DEFAULT NULL,`region_grade` MEDIUMINT (8) UNSIGNED DEFAULT NULL,`p_1` VARCHAR (50) DEFAULT NULL,`p_2` VARCHAR (50) DEFAULT NULL,`ordernum` MEDIUMINT (8) UNSIGNED DEFAULT NULL,`disabled` enum ('true','false') DEFAULT 'false',PRIMARY KEY (`region_id`),UNIQUE KEY `ind_p_regions_id` (`p_region_id`,`region_grade`,`local_name`)) ENGINE=INNODB DEFAULT CHARSET=utf8;";
    echo $sql . "\n";
    $db->exec($sql);

    $sql = "insert into sdb_jdsale_regions_2 ( select * from  sdb_jdsale_regions) ";
    echo $sql . "\n";
    $db->exec($sql);
}


function rename_table()
{
    $date = date('YmdHi',time());
    $db = kernel::database();
    $sql = "alter table sdb_jdsale_regions rename sdb_jdsale_regions_bk".$date;
    echo $sql . "\n";
    $db->exec($sql);

    $sql = "alter table sdb_jdsale_regions_2 rename sdb_jdsale_regions";
    echo $sql . "\n";
    $db->exec($sql);

    $sql = "delete from sdb_jdsale_regions where `disabled` = 'true'";
    echo $sql . "\n";
    $db->exec($sql);
}

function import_regions_jd()
{
    echo "import_regions_jd \n";

    $regionLevel1List = regions_level1();
    $regionL1InMysql = regions_from_mysql(0);
    region_del_from_mysql($regionL1InMysql, $regionLevel1List);

    foreach ($regionLevel1List as $localName => $areaId) {
        echo $localName . "\n";
        $regionLevel1Id = region_update($areaId, $localName, null, ",{$areaId},", '1');
        if ($regionLevel1Id < 1) {
            echo "regionLevel1Id: " . var_export($regionLevel1Id, true);
            exit;
        }

        $regionLevel2List = regions_level2($areaId);
        $regionL2InMysql = regions_from_mysql($areaId);
        region_del_from_mysql($regionL2InMysql, $regionLevel2List);

        foreach ($regionLevel2List as $localName2 => $areaId2) {
            echo $localName . ' ' . $localName2 . "\n";
            $regionLevel2Id = region_update($areaId2, $localName2, $areaId, ",{$areaId},{$areaId2},", '2');
            if ($regionLevel2Id < 1) {
                echo "regionLevel2Id: " . var_export($regionLevel2Id, true);
                exit;
            }

            $regionLevel3List = regions_level3($areaId2);
            $regionL3InMysql = regions_from_mysql($areaId2);
            region_del_from_mysql($regionL3InMysql, $regionLevel3List);
            foreach ($regionLevel3List as $localName3 => $areaId3) {
                echo $localName . ' ' . $localName2 . ' ' . $localName3 . "\n";
                $regionLevel3Id = region_update($areaId3, $localName3, $areaId2, ",{$areaId},{$areaId2},{$areaId3},", '3');
                if ($regionLevel3Id < 1) {
                    echo "regionLevel3Id: " . var_export($regionLevel3Id, true);
                    exit;
                }

                $regionLevel4List = regions_level4($areaId3);
                $regionL4InMysql = regions_from_mysql($areaId3);
                region_del_from_mysql($regionL4InMysql, $regionLevel4List);
                if (false == $regionLevel4List) {
                    continue;
                }
                foreach ($regionLevel4List as $localName4 => $areaId4) {
                    echo $localName . ' ' . $localName2 . ' ' . $localName3 . ' ' . $localName4 . "\n";
                    $regionLevel4Id = region_update($areaId4, $localName4, $areaId3, ",{$areaId},{$areaId2},{$areaId3},{$areaId4},", '4');
                    if ($regionLevel4Id < 1) {
                        echo "regionLevel4Id: " . var_export($regionLevel4Id, true);
                        exit;
                    }
                }
            }
        }
    }

    echo("京东地址库数据导入结束！\n\n");
}

function update_region_js()
{
    $regionObj = kernel::single('jdsale_regions_operation');
    return $regionObj->updateRegionData();
}

function region_del_from_mysql($regionL1InMysql, $regionLevel1List)
{
    $db = kernel::database();
    if (is_array($regionLevel1List)) {
        $regionDiff = array_diff($regionL1InMysql, $regionLevel1List);
    } else {
        $regionDiff = $regionL1InMysql;
    }

    if (empty($regionDiff)) {
        return false;
    }

    $regionDiffStr = implode(",", $regionDiff);
    $sql = "update sdb_jdsale_regions_2 set disabled='true' where area_id in ($regionDiffStr)";
    echo $sql . "\n";
    $db->exec($sql);

    return true;
}

function region_update($areaId, $localName, $pRegionId, $regionPath, $regionLevel)
{
    $db = kernel::database();

    $sql = "select region_id,area_id from sdb_jdsale_regions_2 where area_id = '$areaId'";
    $areaRow = $db->selectrow($sql);
    if ($areaRow['region_id'] > 0 && $areaId > 0 && $areaId == $areaRow['area_id']) {
        $regionId = $areaRow['region_id'];
        $sql = "update sdb_jdsale_regions_2 set local_name='$localName',region_grade='$regionLevel' where region_id = '$regionId' and area_id = '$areaId'";
        $rs = $db->exec($sql);
    } else {
        $sql = "insert into sdb_jdsale_regions_2(area_id,local_name,package,region_path,region_grade) values ('$areaId','$localName','mainland','$regionPath','$regionLevel')";
        if ($pRegionId > 0) {
            $sql = "insert into sdb_jdsale_regions_2(area_id,local_name,package,p_region_id,region_path,region_grade) values ('$areaId','$localName','mainland',$pRegionId,'$regionPath','$regionLevel')";
        }
        $rs = $db->exec($sql);
        $regionId = $db->lastinsertid();
    }
    echo $sql . "\n";

    return $regionId;
}

function regions_level1()
{
    $jdsale_api = kernel::single('jdsale_api_area');

    //调用一级地址api接口
    $result_arr = $jdsale_api->getAllProvinces(null, 'book');
    $result = $result_arr['result'];
    if (empty($result)) {
        //空结果时再调一次
        $result_arr = $jdsale_api->getAllProvinces(null, 'book');
        $result = $result_arr['result'];
        if (empty($result)) {
            return false;
        }
    }
    asort($result);

    return $result;
}

function regions_level2($areaId)
{
    $jdsale_api = kernel::single('jdsale_api_area');

    //调用一级地址api接口
    $result_arr = $jdsale_api->getCitysByProvinceId(array('id' => $areaId), 'book');
    $result = $result_arr['result'];
    if (empty($result)) {
        $result_arr = $jdsale_api->getCitysByProvinceId(array('id' => $areaId), 'book');
        $result = $result_arr['result'];
        if (empty($result)) {
            return false;
        }
    }
    asort($result);

    return $result;
}

function regions_level3($areaId)
{
    $jdsale_api = kernel::single('jdsale_api_area');

    //调用一级地址api接口
    $result_arr = $jdsale_api->getCountysByCityId(array('id' => $areaId), 'book');
    $result = $result_arr['result'];
    if (empty($result)) {
        $result_arr = $jdsale_api->getCountysByCityId(array('id' => $areaId), 'book');
        $result = $result_arr['result'];
        if (empty($result)) {
            return false;
        }
    }
    asort($result);

    return $result;
}

function regions_level4($areaId)
{
    $jdsale_api = kernel::single('jdsale_api_area');

    //调用一级地址api接口
    $result_arr = $jdsale_api->getTownsByCountyId(array('id' => $areaId), 'book');
    $result = $result_arr['result'];
    if (empty($result)  && $result_arr['resultCode'] !='3405') {
        $result_arr = $jdsale_api->getTownsByCountyId(array('id' => $areaId), 'book');
        $result = $result_arr['result'];
        if (empty($result)) {
            return false;
        }
    }
    
    asort($result);

    return $result;
}

function regions_from_mysql($regionParentId)
{
    $db = kernel::database();
    $sql = "select area_id from sdb_jdsale_regions_2 where p_region_id is null";
    if ($regionParentId) {
        $sql = "select area_id from sdb_jdsale_regions_2 where p_region_id='{$regionParentId}'";
    }
    $regionList = $db->select($sql);
    echo $sql . "\n";

    $regionIds = array();
    if (!empty($regionList)) {
        foreach ($regionList as $item) {
            array_push($regionIds, $item['area_id']);
        }
    }

    return $regionIds;
}

function app_boot()
{
    include(APP_DIR . '/base/defined.php');

    cachemgr::init();
    cacheobject::init();
}
