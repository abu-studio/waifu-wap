<?php
/**
 * ShopEx licence
 *
 * @copyright Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license http://ecos.shopex.cn/ ShopEx License
 */
class cellphone_base_interface_regions extends cellphone_cellphone {
    public function __construct($app) {
        parent :: __construct();
        $this -> app = $app;
    }

    /**
     * 得到所有地区，以便手机端生成缓存
     *
     * @params
     * @return array
     */
    public function getlist() {
        $params = $this -> params;

        if ($params['cols']) {
            $cols = $params['cols'];
        } else {
            $cols = '*';
        }

        if ($params['filter']) {
            $filter = $params['filter'];
        } else {
            $filter = array();
        }

        if ($params['offset']) {
            $offset = $params['offset'];
        } else {
            $offset = 0;
        }

        if ($params['limit']) {
            $limit = $params['limit'];
        } else {
            $limit = -1;
        }

        if ($params['orderType']) {
            $orderType = $params['orderType'];
        } else {
            $orderType = null;
        }

        $mobj = app :: get('ectools') -> model('regions');

        $aData = $mobj -> getList($cols, $filter, $offset, $limit, $orderType);

        if ($aData) {
            $this -> send(true, $aData, app :: get('b2c') -> _('地区信息'));
        } else {
            $this -> send(true, null, app :: get('b2c') -> _('没有地区信息'));
        }
    }

    /**
     * 得到地区信息 - parent region id， 层级，下级地区
     *
     * @params string region id
     * @return array 指定信息的数组
     */
    public function getregionbyid() {
        $params = $this -> params;
        // 检查应用级必填参数
        $must_params = array('regionid' => '地区ID'
            );
        $this -> check_params($must_params);

        $regionId = $params['regionid'];
        // $sql='select region_id,p_region_id,local_name,ordernum,region_path from '.$this->table_name(1).' as r where r.p_region_id'.($regionId?('='.intval($regionId)):' is null').' order by ordernum asc,region_id asc';
        // $aTemp=$this->db->select($sql);
        if ($regionId)
            $aTemp = app :: get('ectools') -> model('regions') -> getList('region_id,p_region_id,local_name,ordernum,region_path', array('p_region_id' => $regionId), 0, -1, 'ordernum ASC,region_id ASC');
        else
            $aTemp = app :: get('ectools') -> model('regions') -> getList('region_id,p_region_id,local_name,ordernum,region_path', array('region_grade' => '1'), 0, -1, 'ordernum ASC,region_id ASC');

        if (is_array($aTemp) && count($aTemp) > 0) {
            foreach($aTemp as $key => $val) {
                $aTemp[$key]['p_region_id'] = intval($val['p_region_id']);
                $aTemp[$key]['step'] = intval(substr_count($val['region_path'], ','))-1;
                $aTemp[$key]['child_count'] = $this -> getChildCount($val['region_id']);
            }
        }

        if ($aTemp) {
            $this -> send(true, $aTemp, app :: get('b2c') -> _('地区信息'));
        } else {
            $this -> send(true, null, app :: get('b2c') -> _('没有地区信息'));
        }
    }

    /**
     * 获取指定parent region id的下级地区数量
     * @params string region id
     * @return int 数量
     */
    private function getChildCount($region_id)
    {
        //$row = $this->db->selectrow('select count(*) as childCount from '.$this->table_name(1).' where p_region_id='.intval($region_id));
        $cnt =app :: get('ectools') -> model('regions')->count(array('p_region_id' => intval($region_id)));
        //return $row['childCount'];
        return $cnt;
    }

    /**
     * 得到地区的结构图
     *
     * @params int parent region id
     * @return array 结构图数组
     */
    public function getmapbyid($prId = '') {
        $params = $this -> params;
        // 检查应用级必填参数
        $must_params = array('regionid' => '地区ID'
            );
        $this -> check_params($must_params);

        $prId = $params['regionid'];

        $regions =$this->getmap($prId);

        if ($regions) {
            $this -> send(true, $regions, app :: get('b2c') -> _('地区信息'));
        } else {
            $this -> send(true, null, app :: get('b2c') -> _('没有地区信息'));
        }
    }


     public function getmap($prId = '') {

        if ($prId)
            $sql = "select region_id,region_grade,local_name,ordernum,(select count(*) from " . app :: get('ectools') -> model('regions') -> table_name(1) . " where p_region_id=r.region_id) as child_count from " . app :: get('ectools') -> model('regions') -> table_name(1) . " as r where r.p_region_id=" . intval($prId) . " order by ordernum asc,region_id";
        else
            $sql = "select region_id,region_grade,local_name,ordernum,(select count(*) from " . app :: get('ectools') -> model('regions') -> table_name(1) . " where p_region_id=r.region_id) as child_count from " . app :: get('ectools') -> model('regions') -> table_name(1) . " as r where r.p_region_id is null order by ordernum asc,region_id";

        $row = app :: get('ectools') -> model('regions') -> db -> select($sql);

        if (isset($row) && $row) {
            foreach ($row as $key => $val) {
                $regions[] = array("local_name" => $val['local_name'],
                    "region_id" => $val['region_id'],
                    "region_grade" => $val['region_grade'],
                    "ordernum" => $val['ordernum']
                    );

                if ($val['child_count'])
                    $this -> getmap($val['region_id']);
            }
        }

        return  $regions;

     }

    /**
     * 得到地区名称
     *
     * @params string regions id
     * @return array local_name的数组
     */
    public function getbyid() {
        $params = $this -> params;
        // 检查应用级必填参数
        $must_params = array('regionid' => '地区ID'
            );
        $this -> check_params($must_params);

        $regionId = $params['regionid'];

        $regions = app :: get('ectools') -> model('regions') -> getById($regionId);
        if ($regions) {
            $this -> send(true, $regions, app :: get('b2c') -> _('地区信息'));
        } else {
            $this -> send(true, null, app :: get('b2c') -> _('没有地区信息'));
        }
    }

    /**
     * 得到指定id的地区信息
     *
     * @params string region id
     * @return array 信息数组
     */
    public function getregionbyparentid() {
        $params = $this -> params;
        // 检查应用级必填参数
        $must_params = array('regionid' => '地区ID'
            );
        $this -> check_params($must_params);

        $parentId = $params['regionid'];

        $regions = app :: get('ectools') -> model('regions') -> getRegionByParentId($parentId);

        if ($regions) {
            $this -> send(true, $regions, app :: get('b2c') -> _('地区信息'));
        } else {
            $this -> send(true, null, app :: get('b2c') -> _('没有地区信息'));
        }
    }

    /**
     * 指定region id的下级信息
     *
     * @params int region id
     * @return array - 所有地区数据数组
     */
    public function getallchild() {
        $params = $this -> params;
        // 检查应用级必填参数
        $must_params = array('regionid' => '地区ID'
            );
        $this -> check_params($must_params);

        $regionId = $params['regionid'];

        $regions = app :: get('ectools') -> model('regions') -> getAllChild($regionId);

        if ($regions) {
            $this -> send(true, $regions, app :: get('b2c') -> _('地区信息'));
        } else {
            $this -> send(true, null, app :: get('b2c') -> _('没有地区信息'));
        }
    }

    /**
     * 得到指定region id同级的地区信息
     *
     * @params int region id
     * @return array 地区信息
     */
    public function getgroupregionid() {
        $params = $this -> params;
        // 检查应用级必填参数
        $must_params = array('regionid' => '地区ID'
            );
        $this -> check_params($must_params);

        $regionId = $params['regionid'];

        $regions = app :: get('ectools') -> model('regions') -> getGroupRegionId($regionId);

        if ($regions) {
            $this -> send(true, $regions, app :: get('b2c') -> _('地区信息'));
        } else {
            $this -> send(true, null, app :: get('b2c') -> _('没有地区信息'));
        }
    }

    /**
     * 得到指定region id的信息及父级的local_name
     *
     * @params int region id
     * @return array
     */
    public function getdlareabyid() {
        $params = $this -> params;
        // 检查应用级必填参数
        $must_params = array('regionid' => '地区ID'
            );
        $this -> check_params($must_params);

        $aRegionId = $params['regionid'];

        $regions = app :: get('ectools') -> model('regions') -> getDlAreaById($aRegionId);

        if ($regions) {
            $this -> send(true, $regions, app :: get('b2c') -> _('地区信息'));
        } else {
            $this -> send(true, null, app :: get('b2c') -> _('没有地区信息'));
        }
    }


}

