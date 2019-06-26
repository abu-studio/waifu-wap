<?php
class groupbuy_mdl_groupapply4dialog extends groupbuy_mdl_groupapply{
	public function __construct($app){
		$this->app = app::get('groupbuy');
		$this->table_name = $this->app->model('groupapply')->tableName;
        parent::__construct($app);
    }

	public function get_schema(){
        $columns = $this->app->model('groupapply')->get_schema();
		$columns['columns']['name'] = array (
                    'type' => 'number',
                    'default' => 0,
                    'label' => app::get('b2c')->_('商品名称'),
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'realtype' => 'mediumint(8) unsigned',
                );
		

		$columns['default_in_list'][]='name';

		
        return $columns;
    }

	function table_name($real=false){
         $object = $this->app->model('groupapply');
        $table_name = substr(get_class($object),strlen($this->app->app_id)+5);
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }
    
	function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
		if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        if($this->use_meta){
             $meta_info = $this->prepare_select($cols);
        }
		
        $orderType = $orderType?$orderType:$this->defaultOrder;
			$sql = 'SELECT sdb_groupbuy_groupapply.*,g.name FROM `'.$this->table_name(true).'`  left join sdb_b2c_goods as g on sdb_groupbuy_groupapply.gid = g.goods_id  WHERE '.$this->_filter($filter);
			$sql = 'SELECT sdb_groupbuy_groupapply.*,g.name FROM `'.$this->table_name(true).'`  left join sdb_b2c_goods as g on sdb_groupbuy_groupapply.gid = g.goods_id  WHERE '.$this->_filter($filter);
//		print_r($sql);exit;
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
        $data = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($data, $cols);
        if($this->use_meta && count($meta_info['metacols']) && $data){
            foreach($meta_info['metacols'] as $col){
                $obj_meta = new dbeav_meta($this->table_name(true),$col,$meta_info['has_pk']);
                $obj_meta->select($data);
            }
        }
        return $data;
	}

    
}